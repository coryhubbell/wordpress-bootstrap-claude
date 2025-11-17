<?php
/**
 * Gutenberg Full Site Editing (FSE) Handler
 *
 * Manages FSE templates, template parts, and global styles
 *
 * @package    WordPress_Bootstrap_Claude
 * @subpackage Gutenberg
 * @version    3.2.0
 */

class WPBC_Gutenberg_FSE {
	/**
	 * Logger instance
	 *
	 * @var WPBC_Logger
	 */
	private $logger;

	/**
	 * Template types
	 *
	 * @var array
	 */
	private $template_types = [
		'index'           => 'Index',
		'home'            => 'Home',
		'front-page'      => 'Front Page',
		'singular'        => 'Singular',
		'single'          => 'Single Post',
		'page'            => 'Page',
		'archive'         => 'Archive',
		'author'          => 'Author',
		'category'        => 'Category',
		'taxonomy'        => 'Taxonomy',
		'date'            => 'Date',
		'tag'             => 'Tag',
		'attachment'      => 'Attachment',
		'search'          => 'Search',
		'404'             => '404',
	];

	/**
	 * Template part areas
	 *
	 * @var array
	 */
	private $template_part_areas = [
		'header'    => 'Header',
		'footer'    => 'Footer',
		'sidebar'   => 'Sidebar',
		'uncategorized' => 'General',
	];

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->logger = new WPBC_Logger();
	}

	/**
	 * Create FSE template
	 *
	 * @param string $slug     Template slug.
	 * @param string $title    Template title.
	 * @param string $content  Template content (block markup).
	 * @param array  $options  Additional options.
	 * @return int|WP_Error Post ID or error.
	 */
	public function create_template( string $slug, string $title, string $content, array $options = [] ) {
		// Create template post
		$template_data = [
			'post_type'    => 'wp_template',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_content' => $content,
			'post_name'    => $slug,
			'tax_input'    => [],
		];

		// Add template type taxonomy
		if ( ! empty( $options['type'] ) ) {
			$template_data['tax_input']['wp_theme'] = [ $options['type'] ];
		}

		$template_id = wp_insert_post( $template_data );

		if ( is_wp_error( $template_id ) ) {
			$this->logger->error( 'Failed to create template', [
				'slug'  => $slug,
				'error' => $template_id->get_error_message(),
			]);
			return $template_id;
		}

		// Add template metadata
		if ( ! empty( $options['description'] ) ) {
			update_post_meta( $template_id, 'description', $options['description'] );
		}

		$this->logger->info( 'FSE template created', [
			'slug'  => $slug,
			'title' => $title,
			'id'    => $template_id,
		]);

		return $template_id;
	}

	/**
	 * Create template part
	 *
	 * @param string $slug     Part slug.
	 * @param string $title    Part title.
	 * @param string $content  Part content.
	 * @param string $area     Template part area.
	 * @param array  $options  Additional options.
	 * @return int|WP_Error Post ID or error.
	 */
	public function create_template_part( string $slug, string $title, string $content, string $area = 'uncategorized', array $options = [] ) {
		// Create template part post
		$part_data = [
			'post_type'    => 'wp_template_part',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_content' => $content,
			'post_name'    => $slug,
			'tax_input'    => [
				'wp_template_part_area' => [ $area ],
			],
		];

		$part_id = wp_insert_post( $part_data );

		if ( is_wp_error( $part_id ) ) {
			$this->logger->error( 'Failed to create template part', [
				'slug'  => $slug,
				'error' => $part_id->get_error_message(),
			]);
			return $part_id;
		}

		$this->logger->info( 'Template part created', [
			'slug'  => $slug,
			'title' => $title,
			'area'  => $area,
			'id'    => $part_id,
		]);

		return $part_id;
	}

	/**
	 * Get all templates
	 *
	 * @param array $args Query args.
	 * @return array Templates.
	 */
	public function get_templates( array $args = [] ): array {
		$defaults = [
			'post_type'      => 'wp_template',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		];

		$args = wp_parse_args( $args, $defaults );
		$query = new WP_Query( $args );

		$templates = [];
		foreach ( $query->posts as $post ) {
			$templates[] = [
				'id'      => $post->ID,
				'slug'    => $post->post_name,
				'title'   => $post->post_title,
				'content' => $post->post_content,
			];
		}

		return $templates;
	}

	/**
	 * Get all template parts
	 *
	 * @param string|null $area Filter by area.
	 * @return array Template parts.
	 */
	public function get_template_parts( ?string $area = null ): array {
		$args = [
			'post_type'      => 'wp_template_part',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		];

		if ( $area ) {
			$args['tax_query'] = [
				[
					'taxonomy' => 'wp_template_part_area',
					'field'    => 'slug',
					'terms'    => $area,
				],
			];
		}

		$query = new WP_Query( $args );

		$parts = [];
		foreach ( $query->posts as $post ) {
			$parts[] = [
				'id'      => $post->ID,
				'slug'    => $post->post_name,
				'title'   => $post->post_title,
				'content' => $post->post_content,
				'area'    => $this->get_template_part_area( $post->ID ),
			];
		}

		return $parts;
	}

	/**
	 * Get template part area
	 *
	 * @param int $part_id Template part ID.
	 * @return string Area slug.
	 */
	private function get_template_part_area( int $part_id ): string {
		$terms = wp_get_post_terms( $part_id, 'wp_template_part_area' );

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			return $terms[0]->slug;
		}

		return 'uncategorized';
	}

	/**
	 * Export template to JSON
	 *
	 * @param int $template_id Template post ID.
	 * @return string|false JSON or false.
	 */
	public function export_template( int $template_id ) {
		$post = get_post( $template_id );

		if ( ! $post || $post->post_type !== 'wp_template' ) {
			return false;
		}

		$template = [
			'slug'        => $post->post_name,
			'title'       => $post->post_title,
			'content'     => $post->post_content,
			'description' => get_post_meta( $post->ID, 'description', true ),
		];

		return wp_json_encode( $template, JSON_PRETTY_PRINT );
	}

	/**
	 * Import template from JSON
	 *
	 * @param string $json Template JSON.
	 * @return int|false Template ID or false.
	 */
	public function import_template( string $json ) {
		$template = json_decode( $json, true );

		if ( ! $template || empty( $template['slug'] ) ) {
			return false;
		}

		$result = $this->create_template(
			$template['slug'],
			$template['title'] ?? $template['slug'],
			$template['content'] ?? '',
			[
				'description' => $template['description'] ?? '',
			]
		);

		return is_wp_error( $result ) ? false : $result;
	}

	/**
	 * Create global styles configuration
	 *
	 * @param array $styles Global styles array.
	 * @return array Theme.json compatible structure.
	 */
	public function create_global_styles( array $styles ): array {
		return [
			'version'  => 2,
			'settings' => $this->build_settings( $styles['settings'] ?? [] ),
			'styles'   => $this->build_styles( $styles['styles'] ?? [] ),
		];
	}

	/**
	 * Build theme.json settings
	 *
	 * @param array $settings Settings config.
	 * @return array Settings structure.
	 */
	private function build_settings( array $settings ): array {
		$defaults = [
			'color' => [
				'palette' => [
					[
						'name'  => 'Primary',
						'slug'  => 'primary',
						'color' => '#0073aa',
					],
					[
						'name'  => 'Secondary',
						'slug'  => 'secondary',
						'color' => '#23282d',
					],
					[
						'name'  => 'White',
						'slug'  => 'white',
						'color' => '#ffffff',
					],
				],
			],
			'typography' => [
				'fontSizes' => [
					[
						'name' => 'Small',
						'slug' => 'small',
						'size' => '14px',
					],
					[
						'name' => 'Medium',
						'slug' => 'medium',
						'size' => '18px',
					],
					[
						'name' => 'Large',
						'slug' => 'large',
						'size' => '24px',
					],
				],
			],
		];

		return array_merge( $defaults, $settings );
	}

	/**
	 * Build theme.json styles
	 *
	 * @param array $styles Styles config.
	 * @return array Styles structure.
	 */
	private function build_styles( array $styles ): array {
		$defaults = [
			'color' => [
				'background' => '#ffffff',
				'text'       => '#000000',
			],
			'typography' => [
				'fontSize'   => '18px',
				'lineHeight' => '1.6',
			],
		];

		return array_merge( $defaults, $styles );
	}

	/**
	 * Export global styles to theme.json
	 *
	 * @return string JSON string.
	 */
	public function export_global_styles(): string {
		// Get current theme's theme.json if it exists
		$theme_json_path = get_stylesheet_directory() . '/theme.json';

		if ( file_exists( $theme_json_path ) ) {
			$content = file_get_contents( $theme_json_path );
			return $content;
		}

		// Generate default theme.json
		$global_styles = $this->create_global_styles( [] );

		return wp_json_encode( $global_styles, JSON_PRETTY_PRINT );
	}

	/**
	 * Create default FSE templates
	 *
	 * @return array Created template IDs.
	 */
	public function create_default_templates(): array {
		$templates = [
			[
				'slug'    => 'index',
				'title'   => 'Index',
				'content' => '<!-- wp:template-part {"slug":"header","area":"header"} /-->
<!-- wp:query {"queryId":1,"query":{"perPage":10,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date"}} -->
<div class="wp-block-query"><!-- wp:post-template -->
<!-- wp:post-title {"isLink":true} /-->
<!-- wp:post-date /-->
<!-- wp:post-excerpt /-->
<!-- /wp:post-template -->
<!-- wp:query-pagination -->
<!-- wp:query-pagination-previous /-->
<!-- wp:query-pagination-numbers /-->
<!-- wp:query-pagination-next /-->
<!-- /wp:query-pagination --></div>
<!-- /wp:query -->
<!-- wp:template-part {"slug":"footer","area":"footer"} /-->',
			],
			[
				'slug'    => 'single',
				'title'   => 'Single Post',
				'content' => '<!-- wp:template-part {"slug":"header","area":"header"} /-->
<!-- wp:group {"tagName":"main"} -->
<main class="wp-block-group"><!-- wp:post-title /-->
<!-- wp:post-date /-->
<!-- wp:post-content /--></main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","area":"footer"} /-->',
			],
			[
				'slug'    => 'page',
				'title'   => 'Page',
				'content' => '<!-- wp:template-part {"slug":"header","area":"header"} /-->
<!-- wp:group {"tagName":"main"} -->
<main class="wp-block-group"><!-- wp:post-title /-->
<!-- wp:post-content /--></main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","area":"footer"} /-->',
			],
		];

		$created = [];

		foreach ( $templates as $template ) {
			$result = $this->create_template(
				$template['slug'],
				$template['title'],
				$template['content']
			);

			if ( ! is_wp_error( $result ) ) {
				$created[] = $result;
			}
		}

		$this->logger->info( 'Default FSE templates created', [
			'count' => count( $created ),
		]);

		return $created;
	}

	/**
	 * Create default template parts
	 *
	 * @return array Created template part IDs.
	 */
	public function create_default_template_parts(): array {
		$parts = [
			[
				'slug'    => 'header',
				'title'   => 'Header',
				'area'    => 'header',
				'content' => '<!-- wp:group {"layout":{"type":"flex","justifyContent":"space-between"}} -->
<div class="wp-block-group"><!-- wp:site-logo /-->
<!-- wp:site-title /-->
<!-- wp:navigation /--></div>
<!-- /wp:group -->',
			],
			[
				'slug'    => 'footer',
				'title'   => 'Footer',
				'area'    => 'footer',
				'content' => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","bottom":"40px"}}}} -->
<div class="wp-block-group" style="padding-top:40px;padding-bottom:40px"><!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">© 2025 - Built with WordPress</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->',
			],
		];

		$created = [];

		foreach ( $parts as $part ) {
			$result = $this->create_template_part(
				$part['slug'],
				$part['title'],
				$part['content'],
				$part['area']
			);

			if ( ! is_wp_error( $result ) ) {
				$created[] = $result;
			}
		}

		$this->logger->info( 'Default template parts created', [
			'count' => count( $created ),
		]);

		return $created;
	}

	/**
	 * Get available template types
	 *
	 * @return array Template types.
	 */
	public function get_template_types(): array {
		return $this->template_types;
	}

	/**
	 * Get available template part areas
	 *
	 * @return array Template part areas.
	 */
	public function get_template_part_areas(): array {
		return $this->template_part_areas;
	}
}
