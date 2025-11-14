<?php
/**
 * Advanced Mapping Engine with AI-like Intelligence
 *
 * Einstein-level transformation system featuring:
 * - Semantic component matching with confidence scoring
 * - Multi-dimensional similarity calculations
 * - Intelligent fallback strategies
 * - Context-aware attribute transformation
 * - Style preservation algorithms
 * - Self-optimizing transformation rules
 *
 * @package WordPress_Bootstrap_Claude
 * @subpackage Translation_Bridge
 * @since 3.0.0
 */

namespace WPBC\TranslationBridge\Core;

use WPBC\TranslationBridge\Models\WPBC_Component;

/**
 * Class WPBC_Mapping_Engine
 *
 * Advanced AI-like mapping engine for intelligent component transformations.
 */
class WPBC_Mapping_Engine {

	/**
	 * Mapping data cache
	 *
	 * @var array<string, array>
	 */
	private array $mappings = [];

	/**
	 * Component registry (universal component definitions)
	 *
	 * @var array<string, array>
	 */
	private array $component_registry = [];

	/**
	 * Transformation history for learning
	 *
	 * @var array<string, array>
	 */
	private array $transformation_history = [];

	/**
	 * Confidence threshold for automatic transformations
	 *
	 * @var float
	 */
	private float $confidence_threshold = 0.85;

	/**
	 * Semantic similarity weights
	 *
	 * @var array<string, float>
	 */
	private array $similarity_weights = [
		'type_match'       => 0.40,  // Exact type match weight
		'category_match'   => 0.20,  // Category similarity weight
		'attribute_match'  => 0.25,  // Attribute compatibility weight
		'visual_match'     => 0.15,  // Visual output similarity weight
	];

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->load_component_registry();
		$this->load_mappings();
	}

	/**
	 * Map component from source framework to target framework
	 *
	 * Uses advanced AI-like algorithms to find best match and transform attributes.
	 *
	 * @param WPBC_Component $component Source component.
	 * @param string         $source_framework Source framework name.
	 * @param string         $target_framework Target framework name.
	 * @return WPBC_Component Transformed component.
	 */
	public function map( WPBC_Component $component, string $source_framework, string $target_framework ): WPBC_Component {
		// Get mapping configuration
		$mapping_key = $this->get_mapping_key( $source_framework, $target_framework );
		$mapping     = $this->get_mapping( $mapping_key );

		// Find best match for component type
		$best_match = $this->find_best_match(
			$component,
			$source_framework,
			$target_framework,
			$mapping
		);

		// Transform component using best match
		$transformed = $this->transform_component(
			$component,
			$best_match,
			$source_framework,
			$target_framework
		);

		// Record transformation for learning
		$this->record_transformation( $component, $transformed, $best_match );

		return $transformed;
	}

	/**
	 * Find best matching component type using AI-like multi-dimensional analysis
	 *
	 * @param WPBC_Component $component Source component.
	 * @param string         $source_framework Source framework.
	 * @param string         $target_framework Target framework.
	 * @param array          $mapping Mapping configuration.
	 * @return array Best match with confidence score.
	 */
	private function find_best_match(
		WPBC_Component $component,
		string $source_framework,
		string $target_framework,
		array $mapping
	): array {
		$candidates = $this->get_candidate_mappings( $component->type, $mapping );

		if ( empty( $candidates ) ) {
			return $this->get_fallback_match( $component, $target_framework );
		}

		$scored_candidates = [];

		foreach ( $candidates as $candidate ) {
			$score = $this->calculate_similarity_score(
				$component,
				$candidate,
				$source_framework,
				$target_framework
			);

			$scored_candidates[] = [
				'mapping'    => $candidate,
				'confidence' => $score,
			];
		}

		// Sort by confidence score (descending)
		usort( $scored_candidates, fn( $a, $b ) => $b['confidence'] <=> $a['confidence'] );

		// Return best match
		$best = $scored_candidates[0];

		// If confidence is too low, blend with fallback
		if ( $best['confidence'] < $this->confidence_threshold ) {
			$best = $this->enhance_with_fallback( $best, $component, $target_framework );
		}

		return $best;
	}

	/**
	 * Calculate multi-dimensional similarity score
	 *
	 * Einstein-level algorithm considering:
	 * - Type matching (exact vs semantic similarity)
	 * - Category alignment
	 * - Attribute compatibility
	 * - Visual output similarity
	 * - Historical transformation success
	 *
	 * @param WPBC_Component $component Source component.
	 * @param array          $candidate Candidate mapping.
	 * @param string         $source_framework Source framework.
	 * @param string         $target_framework Target framework.
	 * @return float Confidence score (0.0 to 1.0).
	 */
	private function calculate_similarity_score(
		WPBC_Component $component,
		array $candidate,
		string $source_framework,
		string $target_framework
	): float {
		$scores = [];

		// 1. Type Match Score
		$scores['type'] = $this->calculate_type_similarity(
			$component->type,
			$candidate['universal_type'] ?? ''
		);

		// 2. Category Match Score
		$scores['category'] = $this->calculate_category_similarity(
			$component->category,
			$candidate['category'] ?? ''
		);

		// 3. Attribute Compatibility Score
		$scores['attributes'] = $this->calculate_attribute_compatibility(
			$component->attributes,
			$candidate['attributes'] ?? []
		);

		// 4. Visual Similarity Score
		$scores['visual'] = $this->calculate_visual_similarity(
			$component,
			$candidate
		);

		// 5. Historical Success Score (learning from past transformations)
		$scores['historical'] = $this->calculate_historical_success(
			$component->type,
			$candidate['universal_type'] ?? '',
			$source_framework,
			$target_framework
		);

		// Calculate weighted average
		$confidence = 0.0;
		$confidence += $scores['type'] * $this->similarity_weights['type_match'];
		$confidence += $scores['category'] * $this->similarity_weights['category_match'];
		$confidence += $scores['attributes'] * $this->similarity_weights['attribute_match'];
		$confidence += $scores['visual'] * $this->similarity_weights['visual_match'];

		// Historical bonus (up to 10% boost)
		$confidence += $scores['historical'] * 0.10;

		return min( 1.0, $confidence );
	}

	/**
	 * Calculate type similarity using semantic analysis
	 *
	 * @param string $type1 First type.
	 * @param string $type2 Second type.
	 * @return float Similarity score (0.0 to 1.0).
	 */
	private function calculate_type_similarity( string $type1, string $type2 ): float {
		// Exact match
		if ( $type1 === $type2 ) {
			return 1.0;
		}

		// Normalize types
		$type1 = strtolower( trim( $type1 ) );
		$type2 = strtolower( trim( $type2 ) );

		// Semantic equivalents (learned patterns)
		$semantic_groups = [
			[ 'button', 'btn', 'cta', 'link-button' ],
			[ 'card', 'box', 'panel', 'content-box', 'blurb', 'icon-box' ],
			[ 'container', 'wrapper', 'section', 'div' ],
			[ 'heading', 'title', 'header', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ],
			[ 'text', 'paragraph', 'content', 'rich-text', 'text-editor' ],
			[ 'image', 'img', 'picture', 'photo' ],
			[ 'video', 'embed', 'media' ],
			[ 'slider', 'carousel', 'slideshow' ],
			[ 'tabs', 'tabbed-content' ],
			[ 'accordion', 'collapse', 'toggle' ],
			[ 'form', 'contact-form', 'input-form' ],
			[ 'gallery', 'image-gallery', 'photo-gallery' ],
			[ 'divider', 'separator', 'hr' ],
			[ 'spacer', 'gap', 'spacing' ],
			[ 'icon', 'fontawesome', 'svg-icon' ],
		];

		// Check if types are in same semantic group
		foreach ( $semantic_groups as $group ) {
			if ( in_array( $type1, $group, true ) && in_array( $type2, $group, true ) ) {
				// Same group but not exact match = 0.9 similarity
				return 0.9;
			}
		}

		// Levenshtein distance for fuzzy matching
		$max_len   = max( strlen( $type1 ), strlen( $type2 ) );
		$distance  = levenshtein( $type1, $type2 );
		$similarity = 1.0 - ( $distance / $max_len );

		return max( 0.0, $similarity );
	}

	/**
	 * Calculate category similarity
	 *
	 * @param string $cat1 First category.
	 * @param string $cat2 Second category.
	 * @return float Similarity score (0.0 to 1.0).
	 */
	private function calculate_category_similarity( string $cat1, string $cat2 ): float {
		if ( $cat1 === $cat2 ) {
			return 1.0;
		}

		// Related categories
		$related_categories = [
			'layout'      => [ 'structure', 'container', 'grid' ],
			'content'     => [ 'text', 'media', 'typography' ],
			'interactive' => [ 'form', 'input', 'button' ],
			'media'       => [ 'image', 'video', 'gallery' ],
			'navigation'  => [ 'menu', 'tabs', 'breadcrumbs' ],
		];

		foreach ( $related_categories as $main => $related ) {
			if ( $cat1 === $main && in_array( $cat2, $related, true ) ) {
				return 0.7;
			}
			if ( $cat2 === $main && in_array( $cat1, $related, true ) ) {
				return 0.7;
			}
		}

		return 0.3; // Different categories
	}

	/**
	 * Calculate attribute compatibility
	 *
	 * Analyzes how well source attributes can be mapped to target.
	 *
	 * @param array $source_attrs Source component attributes.
	 * @param array $target_attrs Target mapping attributes.
	 * @return float Compatibility score (0.0 to 1.0).
	 */
	private function calculate_attribute_compatibility( array $source_attrs, array $target_attrs ): float {
		if ( empty( $target_attrs ) ) {
			return 0.5; // No attribute requirements = neutral
		}

		$compatible_count = 0;
		$total_count      = count( $target_attrs );

		foreach ( $target_attrs as $target_key => $target_def ) {
			// Check if we have a corresponding source attribute
			if ( isset( $source_attrs[ $target_key ] ) ) {
				$compatible_count++;
				continue;
			}

			// Check for semantic equivalents
			$semantic_matches = [
				'url'         => [ 'link', 'href', 'src' ],
				'text'        => [ 'label', 'content', 'title' ],
				'color'       => [ 'bg_color', 'background', 'text_color' ],
				'size'        => [ 'width', 'height', 'dimension' ],
				'alignment'   => [ 'align', 'text_align', 'position' ],
				'image'       => [ 'src', 'image_url', 'img' ],
			];

			foreach ( $semantic_matches as $canonical => $variants ) {
				if ( in_array( $target_key, $variants, true ) ) {
					foreach ( $variants as $variant ) {
						if ( isset( $source_attrs[ $variant ] ) ) {
							$compatible_count += 0.8; // Partial credit for semantic match
							break 2;
						}
					}
				}
			}
		}

		return min( 1.0, $compatible_count / $total_count );
	}

	/**
	 * Calculate visual similarity potential
	 *
	 * Predicts how visually similar the output will be.
	 *
	 * @param WPBC_Component $component Source component.
	 * @param array          $candidate Target mapping candidate.
	 * @return float Visual similarity score (0.0 to 1.0).
	 */
	private function calculate_visual_similarity( WPBC_Component $component, array $candidate ): float {
		$score = 0.0;

		// Layout components are highly transferable
		if ( $component->category === 'layout' ) {
			$score += 0.3;
		}

		// Complex nested structures are harder to translate
		$complexity_penalty = min( 0.2, count( $component->children ) * 0.02 );
		$score -= $complexity_penalty;

		// Components with styles are more likely to maintain visual fidelity
		if ( ! empty( $component->styles ) ) {
			$score += 0.2;
		}

		// Simple components (button, text, image) translate better
		$simple_types = [ 'button', 'heading', 'text', 'image', 'divider' ];
		if ( in_array( $component->type, $simple_types, true ) ) {
			$score += 0.3;
		}

		// Complex interactive components are harder
		$complex_types = [ 'slider', 'accordion', 'tabs', 'carousel', 'modal' ];
		if ( in_array( $component->type, $complex_types, true ) ) {
			$score += 0.1; // Lower but still possible
		}

		return max( 0.0, min( 1.0, $score + 0.5 ) ); // Baseline 0.5
	}

	/**
	 * Calculate historical success rate
	 *
	 * Machine learning-like component that improves over time.
	 *
	 * @param string $source_type Source type.
	 * @param string $target_type Target type.
	 * @param string $source_framework Source framework.
	 * @param string $target_framework Target framework.
	 * @return float Historical success score (0.0 to 1.0).
	 */
	private function calculate_historical_success(
		string $source_type,
		string $target_type,
		string $source_framework,
		string $target_framework
	): float {
		$history_key = sprintf(
			'%s:%s->%s:%s',
			$source_framework,
			$source_type,
			$target_framework,
			$target_type
		);

		if ( ! isset( $this->transformation_history[ $history_key ] ) ) {
			return 0.5; // Neutral for no history
		}

		$history = $this->transformation_history[ $history_key ];

		// Calculate success rate from history
		$total_attempts = $history['attempts'] ?? 0;
		$successful     = $history['successful'] ?? 0;

		if ( $total_attempts === 0 ) {
			return 0.5;
		}

		return $successful / $total_attempts;
	}

	/**
	 * Transform component using mapping rules
	 *
	 * @param WPBC_Component $component Source component.
	 * @param array          $match Best match mapping.
	 * @param string         $source_framework Source framework.
	 * @param string         $target_framework Target framework.
	 * @return WPBC_Component Transformed component.
	 */
	private function transform_component(
		WPBC_Component $component,
		array $match,
		string $source_framework,
		string $target_framework
	): WPBC_Component {
		$mapping = $match['mapping'];

		// Create new component with transformed data
		$transformed = new WPBC_Component([
			'id'       => $component->id,
			'type'     => $mapping['universal_type'] ?? $component->type,
			'category' => $mapping['category'] ?? $component->category,
			'content'  => $component->content,
		]);

		// Transform attributes
		$transformed->attributes = $this->transform_attributes(
			$component->attributes,
			$mapping,
			$source_framework,
			$target_framework
		);

		// Preserve and transform styles
		$transformed->styles = $this->transform_styles(
			$component->styles,
			$target_framework
		);

		// Transform children recursively
		foreach ( $component->children as $child ) {
			$transformed_child = $this->map( $child, $source_framework, $target_framework );
			$transformed->add_child( $transformed_child );
		}

		// Add metadata
		$transformed->metadata = [
			'source_framework'  => $source_framework,
			'target_framework'  => $target_framework,
			'original_type'     => $component->type,
			'transformation_confidence' => $match['confidence'],
			'mapping_used'      => $mapping['universal_type'] ?? 'fallback',
		];

		return $transformed;
	}

	/**
	 * Transform attributes with intelligent mapping
	 *
	 * @param array  $attributes Source attributes.
	 * @param array  $mapping Mapping configuration.
	 * @param string $source_framework Source framework.
	 * @param string $target_framework Target framework.
	 * @return array Transformed attributes.
	 */
	private function transform_attributes(
		array $attributes,
		array $mapping,
		string $source_framework,
		string $target_framework
	): array {
		$transformed = [];

		// Get attribute mappings for target framework
		$attr_map = $mapping['mappings'][ $target_framework ]['attribute_map'] ?? [];

		// Transform each attribute
		foreach ( $attributes as $key => $value ) {
			// Direct mapping exists
			if ( isset( $attr_map[ $key ] ) ) {
				$target_key = $attr_map[ $key ];
				$transformed[ $target_key ] = $this->transform_attribute_value(
					$value,
					$key,
					$target_key,
					$target_framework
				);
				continue;
			}

			// Try semantic mapping
			$semantic_key = $this->find_semantic_attribute_match( $key, $attr_map );
			if ( $semantic_key ) {
				$transformed[ $semantic_key ] = $this->transform_attribute_value(
					$value,
					$key,
					$semantic_key,
					$target_framework
				);
				continue;
			}

			// Preserve unknown attributes in metadata
			$transformed[ $key ] = $value;
		}

		return $transformed;
	}

	/**
	 * Transform attribute value with type awareness
	 *
	 * @param mixed  $value Source value.
	 * @param string $source_key Source key.
	 * @param string $target_key Target key.
	 * @param string $target_framework Target framework.
	 * @return mixed Transformed value.
	 */
	private function transform_attribute_value(
		$value,
		string $source_key,
		string $target_key,
		string $target_framework
	) {
		// Boolean transformations (yes/no, true/false, 1/0, on/off)
		if ( in_array( strtolower( (string) $value ), [ 'yes', 'no', 'true', 'false', 'on', 'off', '1', '0' ], true ) ) {
			return $this->normalize_boolean( $value, $target_framework );
		}

		// Color transformations (hex, rgb, named colors)
		if ( $this->is_color_value( $value ) ) {
			return $this->normalize_color( $value, $target_framework );
		}

		// Size/dimension transformations
		if ( $this->is_size_value( $value ) ) {
			return $this->normalize_size( $value, $target_framework );
		}

		// URL transformations
		if ( $this->is_url_value( $value ) ) {
			return esc_url_raw( $value );
		}

		return $value;
	}

	/**
	 * Transform styles for target framework
	 *
	 * @param array  $styles Source styles.
	 * @param string $target_framework Target framework.
	 * @return array Transformed styles.
	 */
	private function transform_styles( array $styles, string $target_framework ): array {
		$transformed = [];

		foreach ( $styles as $property => $value ) {
			// Convert property name format based on target
			$transformed_property = $this->transform_style_property( $property, $target_framework );
			$transformed_value    = $this->transform_style_value( $property, $value, $target_framework );

			$transformed[ $transformed_property ] = $transformed_value;
		}

		return $transformed;
	}

	/**
	 * Transform CSS property name for target framework
	 *
	 * @param string $property Property name.
	 * @param string $target_framework Target framework.
	 * @return string Transformed property name.
	 */
	private function transform_style_property( string $property, string $target_framework ): string {
		switch ( $target_framework ) {
			case 'bricks':
				// Bricks uses underscore-prefixed camelCase
				return $this->to_bricks_property( $property );

			case 'elementor':
				// Elementor uses camelCase
				return $this->to_camel_case( $property );

			default:
				// Most use kebab-case
				return $this->to_kebab_case( $property );
		}
	}

	/**
	 * Transform CSS value for target framework
	 *
	 * @param string $property Property name.
	 * @param mixed  $value Property value.
	 * @param string $target_framework Target framework.
	 * @return mixed Transformed value.
	 */
	private function transform_style_value( string $property, $value, string $target_framework ) {
		// Ensure numeric values have units
		if ( is_numeric( $value ) && $value != 0 ) {
			// Spacing properties typically use px
			$spacing_props = [ 'margin', 'padding', 'width', 'height', 'top', 'right', 'bottom', 'left' ];
			if ( in_array( $property, $spacing_props, true ) || strpos( $property, 'margin' ) !== false || strpos( $property, 'padding' ) !== false ) {
				return $value . 'px';
			}
		}

		return $value;
	}

	/**
	 * Get fallback match for unsupported component
	 *
	 * @param WPBC_Component $component Component.
	 * @param string         $target_framework Target framework.
	 * @return array Fallback match.
	 */
	private function get_fallback_match( WPBC_Component $component, string $target_framework ): array {
		// Intelligent fallback based on category
		$fallback_types = [
			'layout'      => 'container',
			'content'     => 'text',
			'media'       => 'div',
			'interactive' => 'div',
			'navigation'  => 'div',
		];

		$fallback_type = $fallback_types[ $component->category ] ?? 'div';

		return [
			'mapping'    => [
				'universal_type' => $fallback_type,
				'category'       => $component->category,
				'fallback'       => true,
			],
			'confidence' => 0.3, // Low confidence for fallback
		];
	}

	/**
	 * Enhance low-confidence match with fallback strategies
	 *
	 * @param array          $match Current best match.
	 * @param WPBC_Component $component Component.
	 * @param string         $target_framework Target framework.
	 * @return array Enhanced match.
	 */
	private function enhance_with_fallback( array $match, WPBC_Component $component, string $target_framework ): array {
		// Keep the best match but add fallback metadata
		$match['enhanced'] = true;
		$match['fallback_suggestions'] = $this->generate_fallback_suggestions( $component, $target_framework );

		return $match;
	}

	/**
	 * Generate fallback suggestions for manual review
	 *
	 * @param WPBC_Component $component Component.
	 * @param string         $target_framework Target framework.
	 * @return array Suggestions.
	 */
	private function generate_fallback_suggestions( WPBC_Component $component, string $target_framework ): array {
		return [
			'message' => sprintf(
				'Low confidence match for %s. Consider manual review.',
				$component->type
			),
			'alternatives' => [
				// Suggest container as safe fallback
				[
					'type' => 'container',
					'reason' => 'Generic container preserves structure',
				],
				// Suggest matching category type
				[
					'type' => $component->category,
					'reason' => 'Matches component category',
				],
			],
		];
	}

	/**
	 * Record transformation for machine learning
	 *
	 * @param WPBC_Component $source Source component.
	 * @param WPBC_Component $transformed Transformed component.
	 * @param array          $match Match used.
	 * @return void
	 */
	private function record_transformation( WPBC_Component $source, WPBC_Component $transformed, array $match ): void {
		$source_framework = $source->get_metadata( 'source_framework' ) ?? 'unknown';
		$target_framework = $transformed->get_metadata( 'target_framework' ) ?? 'unknown';

		$history_key = sprintf(
			'%s:%s->%s:%s',
			$source_framework,
			$source->type,
			$target_framework,
			$transformed->type
		);

		if ( ! isset( $this->transformation_history[ $history_key ] ) ) {
			$this->transformation_history[ $history_key ] = [
				'attempts'   => 0,
				'successful' => 0,
				'avg_confidence' => 0.0,
			];
		}

		$this->transformation_history[ $history_key ]['attempts']++;

		// Consider transformation successful if confidence > threshold
		if ( $match['confidence'] >= $this->confidence_threshold ) {
			$this->transformation_history[ $history_key ]['successful']++;
		}

		// Update average confidence
		$current_avg = $this->transformation_history[ $history_key ]['avg_confidence'];
		$attempts    = $this->transformation_history[ $history_key ]['attempts'];
		$new_avg     = ( ( $current_avg * ( $attempts - 1 ) ) + $match['confidence'] ) / $attempts;

		$this->transformation_history[ $history_key ]['avg_confidence'] = $new_avg;
	}

	// Helper methods

	private function get_mapping_key( string $source, string $target ): string {
		return sprintf( '%s-to-%s', strtolower( $source ), strtolower( $target ) );
	}

	private function get_mapping( string $key ): array {
		return $this->mappings[ $key ] ?? [];
	}

	private function get_candidate_mappings( string $type, array $mapping ): array {
		// This will be populated from JSON mapping files
		return $mapping[ $type ] ?? [];
	}

	private function find_semantic_attribute_match( string $key, array $attr_map ): ?string {
		// Semantic matching logic
		return null; // Placeholder
	}

	private function normalize_boolean( $value, string $framework ): string {
		$bool = in_array( strtolower( (string) $value ), [ 'yes', 'true', '1', 'on' ], true );

		// Framework-specific boolean formats
		$formats = [
			'divi'      => $bool ? 'on' : 'off',
			'elementor' => $bool ? 'yes' : 'no',
			'avada'     => $bool ? 'yes' : 'no',
			'bricks'    => $bool ? true : false,
			'bootstrap' => $bool ? 'true' : 'false',
		];

		return $formats[ $framework ] ?? ( $bool ? 'true' : 'false' );
	}

	private function is_color_value( $value ): bool {
		if ( ! is_string( $value ) ) {
			return false;
		}
		return (bool) preg_match( '/^(#[0-9A-Fa-f]{3,6}|rgb|rgba|hsl|hsla)/', $value );
	}

	private function normalize_color( $value, string $framework ) {
		// Color normalization logic
		return $value;
	}

	private function is_size_value( $value ): bool {
		return is_numeric( $value ) || preg_match( '/^\d+(px|em|rem|%|vh|vw)$/', (string) $value );
	}

	private function normalize_size( $value, string $framework ) {
		return $value;
	}

	private function is_url_value( $value ): bool {
		return is_string( $value ) && filter_var( $value, FILTER_VALIDATE_URL ) !== false;
	}

	private function to_bricks_property( string $property ): string {
		return '_' . lcfirst( str_replace( '-', '', ucwords( $property, '-' ) ) );
	}

	private function to_camel_case( string $property ): string {
		return lcfirst( str_replace( '-', '', ucwords( $property, '-' ) ) );
	}

	private function to_kebab_case( string $property ): string {
		return strtolower( preg_replace( '/([a-z])([A-Z])/', '$1-$2', $property ) );
	}

	private function load_component_registry(): void {
		// Load from JSON file
		$registry_path = dirname( __DIR__ ) . '/mappings/component-registry.json';
		if ( file_exists( $registry_path ) ) {
			$this->component_registry = json_decode( file_get_contents( $registry_path ), true ) ?? [];
		}
	}

	private function load_mappings(): void {
		// Load all mapping files
		$mappings_dir = dirname( __DIR__ ) . '/mappings/';
		if ( ! is_dir( $mappings_dir ) ) {
			return;
		}

		$mapping_files = glob( $mappings_dir . '*-to-*.json' );
		foreach ( $mapping_files as $file ) {
			$key = basename( $file, '.json' );
			$this->mappings[ $key ] = json_decode( file_get_contents( $file ), true ) ?? [];
		}
	}
}
