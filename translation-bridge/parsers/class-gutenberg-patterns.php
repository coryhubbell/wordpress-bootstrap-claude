<?php
/**
 * Gutenberg Block Patterns Handler
 *
 * Manages block patterns, template parts, and reusable blocks
 *
 * @package    WordPress_Bootstrap_Claude
 * @subpackage Gutenberg
 * @version    3.2.0
 */

class WPBC_Gutenberg_Patterns {
	/**
	 * Logger instance
	 *
	 * @var WPBC_Logger
	 */
	private $logger;

	/**
	 * Pattern categories
	 *
	 * @var array
	 */
	private $categories = [
		'header'     => 'Header Patterns',
		'footer'     => 'Footer Patterns',
		'hero'       => 'Hero Sections',
		'features'   => 'Feature Sections',
		'gallery'    => 'Gallery Patterns',
		'pricing'    => 'Pricing Tables',
		'testimonials' => 'Testimonials',
		'call-to-action' => 'Call to Action',
		'contact'    => 'Contact Sections',
		'team'       => 'Team Sections',
		'stats'      => 'Statistics',
		'text'       => 'Text Patterns',
	];

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->logger = new WPBC_Logger();
	}

	/**
	 * Register a block pattern
	 *
	 * @param string $name    Pattern name (namespace/pattern-name).
	 * @param array  $config  Pattern configuration.
	 * @return bool Success.
	 */
	public function register_pattern( string $name, array $config ): bool {
		// Validate pattern config
		if ( empty( $config['title'] ) || empty( $config['content'] ) ) {
			$this->logger->warning( 'Invalid pattern config', [
				'name' => $name,
			]);
			return false;
		}

		// Register pattern with WordPress
		if ( function_exists( 'register_block_pattern' ) ) {
			register_block_pattern( $name, $config );

			$this->logger->debug( 'Block pattern registered', [
				'name'  => $name,
				'title' => $config['title'],
			]);

			return true;
		}

		return false;
	}

	/**
	 * Unregister a block pattern
	 *
	 * @param string $name Pattern name.
	 * @return bool Success.
	 */
	public function unregister_pattern( string $name ): bool {
		if ( function_exists( 'unregister_block_pattern' ) ) {
			unregister_block_pattern( $name );

			$this->logger->debug( 'Block pattern unregistered', [
				'name' => $name,
			]);

			return true;
		}

		return false;
	}

	/**
	 * Register pattern category
	 *
	 * @param string $slug  Category slug.
	 * @param string $label Category label.
	 * @return bool Success.
	 */
	public function register_category( string $slug, string $label ): bool {
		if ( function_exists( 'register_block_pattern_category' ) ) {
			register_block_pattern_category( $slug, [ 'label' => $label ] );

			$this->categories[ $slug ] = $label;

			$this->logger->debug( 'Pattern category registered', [
				'slug'  => $slug,
				'label' => $label,
			]);

			return true;
		}

		return false;
	}

	/**
	 * Get all registered patterns
	 *
	 * @return array Patterns.
	 */
	public function get_patterns(): array {
		if ( function_exists( 'WP_Block_Patterns_Registry::get_instance' ) ) {
			$registry = WP_Block_Patterns_Registry::get_instance();
			return $registry->get_all_registered();
		}

		return [];
	}

	/**
	 * Create pattern from components
	 *
	 * @param array  $components Universal components.
	 * @param string $title      Pattern title.
	 * @param array  $options    Additional options.
	 * @return array Pattern config.
	 */
	public function create_pattern_from_components( array $components, string $title, array $options = [] ): array {
		// Convert components to Gutenberg blocks
		require_once __DIR__ . '/class-gutenberg-converter.php';
		$converter = new WPBC_Gutenberg_Converter();

		$blocks_html = '';
		foreach ( $components as $component ) {
			$blocks_html .= $converter->convert( $component );
		}

		// Build pattern config
		$pattern = [
			'title'       => $title,
			'content'     => $blocks_html,
			'description' => $options['description'] ?? '',
			'categories'  => $options['categories'] ?? [ 'text' ],
			'keywords'    => $options['keywords'] ?? [],
			'viewportWidth' => $options['viewportWidth'] ?? 1200,
		];

		// Add optional properties
		if ( ! empty( $options['blockTypes'] ) ) {
			$pattern['blockTypes'] = $options['blockTypes'];
		}

		if ( isset( $options['inserter'] ) ) {
			$pattern['inserter'] = $options['inserter'];
		}

		return $pattern;
	}

	/**
	 * Export pattern to JSON
	 *
	 * @param string $pattern_name Pattern name.
	 * @return string|false JSON string or false.
	 */
	public function export_pattern( string $pattern_name ) {
		$patterns = $this->get_patterns();

		if ( ! isset( $patterns[ $pattern_name ] ) ) {
			return false;
		}

		$pattern = $patterns[ $pattern_name ];

		return wp_json_encode( $pattern, JSON_PRETTY_PRINT );
	}

	/**
	 * Import pattern from JSON
	 *
	 * @param string $json        JSON string.
	 * @param string $namespace   Pattern namespace.
	 * @return string|false Pattern name or false.
	 */
	public function import_pattern( string $json, string $namespace = 'wpbc' ): ?string {
		$pattern = json_decode( $json, true );

		if ( ! $pattern || ! isset( $pattern['title'] ) ) {
			return null;
		}

		// Generate pattern name from title
		$slug = sanitize_title( $pattern['title'] );
		$pattern_name = $namespace . '/' . $slug;

		// Register pattern
		$success = $this->register_pattern( $pattern_name, $pattern );

		return $success ? $pattern_name : null;
	}

	/**
	 * Get default patterns library
	 *
	 * @return array Pattern definitions.
	 */
	public function get_default_patterns(): array {
		return [
			'wpbc/hero-cover' => [
				'title'       => 'Hero Section with Cover',
				'description' => 'Full-width hero section with background image',
				'categories'  => [ 'hero' ],
				'content'     => '<!-- wp:cover {"url":"","dimRatio":50,"minHeight":600} -->
<div class="wp-block-cover" style="min-height:600px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><div class="wp-block-cover__inner-container">
<!-- wp:heading {"textAlign":"center","level":1} -->
<h1 class="has-text-align-center">Welcome to Our Site</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Create amazing experiences with block patterns</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link">Get Started</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div></div>
<!-- /wp:cover -->',
			],
			'wpbc/three-columns-features' => [
				'title'       => 'Three Column Features',
				'description' => 'Feature section with three columns',
				'categories'  => [ 'features' ],
				'content'     => '<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} -->
<h3>Feature One</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Description of the first amazing feature.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} -->
<h3>Feature Two</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Description of the second amazing feature.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} -->
<h3>Feature Three</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Description of the third amazing feature.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->',
			],
			'wpbc/call-to-action' => [
				'title'       => 'Call to Action',
				'description' => 'Simple CTA with heading and button',
				'categories'  => [ 'call-to-action' ],
				'content'     => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"60px","bottom":"60px"}}},"backgroundColor":"primary"} -->
<div class="wp-block-group has-primary-background-color has-background" style="padding-top:60px;padding-bottom:60px"><!-- wp:heading {"textAlign":"center","textColor":"white"} -->
<h2 class="has-text-align-center has-white-color has-text-color">Ready to Get Started?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","textColor":"white"} -->
<p class="has-text-align-center has-white-color has-text-color">Join thousands of satisfied customers today.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"white","textColor":"primary"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-primary-color has-white-background-color has-text-color has-background">Sign Up Now</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->',
			],
		];
	}

	/**
	 * Register all default patterns
	 */
	public function register_default_patterns() {
		$patterns = $this->get_default_patterns();

		foreach ( $patterns as $name => $config ) {
			$this->register_pattern( $name, $config );
		}

		$this->logger->info( 'Default patterns registered', [
			'count' => count( $patterns ),
		]);
	}

	/**
	 * Get pattern categories
	 *
	 * @return array Categories.
	 */
	public function get_categories(): array {
		return $this->categories;
	}

	/**
	 * Search patterns by keyword
	 *
	 * @param string $keyword Search keyword.
	 * @return array Matching patterns.
	 */
	public function search_patterns( string $keyword ): array {
		$all_patterns = $this->get_patterns();
		$matches = [];

		$keyword = strtolower( $keyword );

		foreach ( $all_patterns as $name => $pattern ) {
			// Search in title
			if ( stripos( $pattern['title'], $keyword ) !== false ) {
				$matches[ $name ] = $pattern;
				continue;
			}

			// Search in description
			if ( ! empty( $pattern['description'] ) && stripos( $pattern['description'], $keyword ) !== false ) {
				$matches[ $name ] = $pattern;
				continue;
			}

			// Search in keywords
			if ( ! empty( $pattern['keywords'] ) ) {
				foreach ( $pattern['keywords'] as $kw ) {
					if ( stripos( $kw, $keyword ) !== false ) {
						$matches[ $name ] = $pattern;
						break;
					}
				}
			}
		}

		return $matches;
	}
}
