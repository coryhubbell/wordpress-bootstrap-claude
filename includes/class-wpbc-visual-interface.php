<?php
/**
 * WPBC Visual Interface
 *
 * Integrates the React-based visual editor into WordPress admin
 *
 * @package    WordPress_Bootstrap_Claude
 * @subpackage Admin
 * @version    3.2.1
 */

class WPBC_Visual_Interface {
	/**
	 * Logger instance
	 *
	 * @var WPBC_Logger
	 */
	private $logger;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->logger = new WPBC_Logger();

		// Add admin menu
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );

		// Enqueue scripts
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		// Intercept page render before WordPress admin template loads
		add_action( 'admin_init', [ $this, 'maybe_render_page' ] );
	}

	/**
	 * Check if we should render the Visual Interface page early
	 * This runs on admin_init, before WordPress outputs admin template
	 */
	public function maybe_render_page() {
		// Check if we're on the Visual Interface page
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'wpbc-visual-interface' ) {
			return;
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have permission to access this page.', 'wpbc' ) );
		}

		// Render the page and exit before WordPress admin template loads
		$this->render_page();
	}

	/**
	 * Add admin menu page
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Visual Interface', 'wpbc' ),
			__( 'Visual Interface', 'wpbc' ),
			'manage_options',
			'wpbc-visual-interface',
			[ $this, 'render_page' ],
			'dashicons-editor-code',
			30
		);

		$this->logger->info( 'Visual interface menu added' );
	}

	/**
	 * Enqueue scripts and styles for visual interface
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		// Only load on our page
		if ( $hook !== 'toplevel_page_wpbc-visual-interface' ) {
			return;
		}

		// Determine if we're in development mode
		$is_dev = defined( 'WP_DEBUG' ) && WP_DEBUG;

		if ( $is_dev ) {
			// Development mode - scripts loaded directly in render_page() with type="module"
			$this->logger->debug( 'Development mode - scripts will be loaded in render_page()' );
		} else {
			// Production mode - load from built assets
			$this->enqueue_prod_scripts();

			// Localize script with WordPress data (production only)
			wp_localize_script(
				'wpbc-visual-interface',
				'wpbcData',
				[
					'restUrl'   => rest_url( 'wpbc/v2/' ),
					'nonce'     => wp_create_nonce( 'wp_rest' ),
					'userId'    => get_current_user_id(),
					'siteUrl'   => get_site_url(),
					'adminUrl'  => admin_url(),
					'version'   => WPBC_THEME_VERSION,
				]
			);
		}
	}

	/**
	 * Check if Vite dev server is running
	 *
	 * @param int $port Vite dev server port.
	 * @return bool True if Vite is running.
	 */
	private function is_vite_running( int $port = 3000 ): bool {
		// Try host machine first (Docker Desktop)
		$hosts = [ 'host.docker.internal', 'localhost' ];

		foreach ( $hosts as $host ) {
			$connection = @fsockopen( $host, $port, $errno, $errstr, 1 );
			if ( is_resource( $connection ) ) {
				fclose( $connection );
				$this->logger->debug( "Vite dev server detected on {$host}:{$port}" );
				return true;
			}
		}

		$this->logger->debug( "Vite dev server not detected on port {$port}" );
		return false;
	}

	/**
	 * Enqueue development scripts from Vite dev server
	 *
	 * @param int $port Vite dev server port.
	 */
	private function enqueue_dev_scripts( int $port ) {
		// Vite client
		wp_enqueue_script(
			'wpbc-vite-client',
			"http://localhost:{$port}/@vite/client",
			[],
			null,
			false
		);
		wp_script_add_data( 'wpbc-vite-client', 'type', 'module' );

		// Main entry point
		wp_enqueue_script(
			'wpbc-visual-interface',
			"http://localhost:{$port}/src/main.tsx",
			[ 'wpbc-vite-client' ],
			null,
			false
		);
		wp_script_add_data( 'wpbc-visual-interface', 'type', 'module' );
	}

	/**
	 * Enqueue production scripts from built assets
	 */
	private function enqueue_prod_scripts() {
		$dist_path = get_template_directory() . '/admin/dist';
		$dist_url  = get_template_directory_uri() . '/admin/dist';

		// Load manifest file
		$manifest_file = $dist_path . '/.vite/manifest.json';

		if ( ! file_exists( $manifest_file ) ) {
			$this->logger->error( 'Manifest file not found', [
				'path' => $manifest_file,
			] );
			return;
		}

		$manifest = json_decode( file_get_contents( $manifest_file ), true );

		if ( ! $manifest ) {
			$this->logger->error( 'Failed to parse manifest file' );
			return;
		}

		// Get main entry point from manifest
		$main_entry = $manifest['index.html'] ?? null;

		if ( ! $main_entry ) {
			$this->logger->error( 'Main entry not found in manifest' );
			return;
		}

		// Enqueue CSS
		if ( isset( $main_entry['css'] ) ) {
			foreach ( $main_entry['css'] as $index => $css_file ) {
				wp_enqueue_style(
					"wpbc-visual-interface-{$index}",
					$dist_url . '/' . $css_file,
					[],
					null
				);
			}
		}

		// Enqueue JS
		if ( isset( $main_entry['file'] ) ) {
			wp_enqueue_script(
				'wpbc-visual-interface',
				$dist_url . '/' . $main_entry['file'],
				[],
				null,
				true
			);
			wp_script_add_data( 'wpbc-visual-interface', 'type', 'module' );
		}

		$this->logger->info( 'Production scripts enqueued', [
			'entry' => $main_entry['file'] ?? null,
		] );
	}

	/**
	 * Render admin page
	 */
	public function render_page() {
		// Clear any previous output
		if ( ob_get_level() ) {
			ob_end_clean();
		}

		// Determine if we're in dev mode
		$is_dev = defined( 'WP_DEBUG' ) && WP_DEBUG;
		$vite_port = 3000;

		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title><?php _e( 'Visual Interface', 'wpbc' ); ?> - <?php bloginfo( 'name' ); ?></title>
			<?php do_action( 'admin_print_styles' ); ?>
		</head>
		<body class="wpbc-visual-interface">
			<div id="root"></div>

			<!-- WordPress Data -->
			<script>
				window.wpbcData = <?php echo json_encode( [
					'restUrl'   => rest_url( 'wpbc/v2/' ),
					'nonce'     => wp_create_nonce( 'wp_rest' ),
					'userId'    => get_current_user_id(),
					'siteUrl'   => get_site_url(),
					'adminUrl'  => admin_url(),
					'version'   => '3.2.1',
				] ); ?>;
			</script>

			<?php if ( $is_dev ) : ?>
				<!-- Development Mode: Vite Dev Server -->
				<script type="module" src="http://localhost:<?php echo $vite_port; ?>/@vite/client"></script>
				<script type="module" src="http://localhost:<?php echo $vite_port; ?>/src/main.tsx"></script>
			<?php else : ?>
				<!-- Production Mode: Built Assets -->
				<?php
			$dist_path = get_template_directory() . '/admin/dist';
			$dist_url  = get_template_directory_uri() . '/admin/dist';
			$manifest_file = $dist_path . '/.vite/manifest.json';

			if ( file_exists( $manifest_file ) ) {
				$manifest = json_decode( file_get_contents( $manifest_file ), true );
				$main_entry = $manifest['index.html'] ?? null;

				if ( $main_entry ) {
					// Load CSS
					if ( isset( $main_entry['css'] ) ) {
						foreach ( $main_entry['css'] as $css_file ) {
							echo '<link rel="stylesheet" href="' . esc_url( $dist_url . '/' . $css_file ) . '">' . "\n";
						}
					}

					// Load JS
					if ( isset( $main_entry['file'] ) ) {
						echo '<script type="module" src="' . esc_url( $dist_url . '/' . $main_entry['file'] ) . '"></script>' . "\n";
					}
				}
			}
			?>
			<?php endif; ?>
		</body>
		</html>
		<?php
		exit; // Prevent WordPress admin footer
	}

	/**
	 * Get visual interface status
	 *
	 * @return array Status information.
	 */
	public function get_status(): array {
		$dist_path = get_template_directory() . '/admin/dist';
		$built = file_exists( $dist_path . '/.vite/manifest.json' );

		return [
			'enabled'    => true,
			'built'      => $built,
			'dev_mode'   => defined( 'WP_DEBUG' ) && WP_DEBUG && $this->is_vite_running( 3000 ),
			'dist_path'  => $dist_path,
		];
	}
}
