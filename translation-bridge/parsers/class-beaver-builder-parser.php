<?php
/**
 * Beaver Builder Page Builder Parser
 *
 * Intelligent Beaver Builder parser featuring:
 * - Serialized PHP array parsing (Row > Column Group > Column > Module)
 * - 30+ module type support
 * - Nested element handling
 * - Settings extraction and normalization
 * - Responsive controls parsing
 *
 * @package WordPress_Bootstrap_Claude
 * @subpackage Translation_Bridge
 * @since 3.2.0
 */

namespace WPBC\TranslationBridge\Parsers;

use WPBC\TranslationBridge\Core\WPBC_Parser_Interface;
use WPBC\TranslationBridge\Models\WPBC_Component;
use WPBC\TranslationBridge\Utils\WPBC_CSS_Helper;

/**
 * Class WPBC_Beaver_Builder_Parser
 *
 * Parse Beaver Builder serialized data into universal components.
 */
class WPBC_Beaver_Builder_Parser implements WPBC_Parser_Interface {

	/**
	 * Supported Beaver Builder module types
	 *
	 * @var array<string>
	 */
	private array $supported_types = [
		'row',
		'column-group',
		'column',
		'heading',
		'rich-text',
		'photo',
		'button',
		'button-group',
		'html',
		'separator',
		'icon',
		'icon-group',
		'gallery',
		'video',
		'audio',
		'map',
		'slideshow',
		'slider',
		'content-slider',
		'testimonials',
		'callout',
		'cta',
		'contact-form',
		'login-form',
		'subscribe-form',
		'menu',
		'search',
		'accordion',
		'tabs',
		'pricing-table',
		'sidebar',
		'countdown',
		'number-counter',
		'posts',
		'posts-carousel',
		'posts-slider',
	];

	/**
	 * Parse Beaver Builder data into universal components
	 *
	 * @param string|array $content Beaver Builder serialized or array content.
	 * @return WPBC_Component[] Array of parsed components.
	 */
	public function parse( $content ): array {
		// Handle serialized PHP string
		if ( is_string( $content ) ) {
			$content = maybe_unserialize( $content );
			if ( ! is_array( $content ) ) {
				return [];
			}
		}

		if ( ! is_array( $content ) ) {
			return [];
		}

		$components = [];

		// Beaver Builder data is an array of nodes keyed by node ID
		// We need to find root rows (nodes with parent = null)
		$root_nodes = $this->get_root_nodes( $content );

		// Parse each root node (row)
		foreach ( $root_nodes as $node_id => $node ) {
			$component = $this->parse_node( $node, $content );
			if ( $component ) {
				$components[] = $component;
			}
		}

		return $components;
	}

	/**
	 * Get root nodes (rows with no parent)
	 *
	 * @param array $nodes All nodes.
	 * @return array Root nodes.
	 */
	private function get_root_nodes( array $nodes ): array {
		$root_nodes = [];

		foreach ( $nodes as $node_id => $node ) {
			// Root nodes have null or empty parent and are typically rows
			if ( empty( $node->parent ) && $node->type === 'row' ) {
				$root_nodes[ $node_id ] = $node;
			}
		}

		// Sort by position
		uasort( $root_nodes, function( $a, $b ) {
			return ( $a->position ?? 0 ) <=> ( $b->position ?? 0 );
		});

		return $root_nodes;
	}

	/**
	 * Get child nodes of a parent
	 *
	 * @param string $parent_id Parent node ID.
	 * @param array  $all_nodes All nodes.
	 * @return array Child nodes.
	 */
	private function get_child_nodes( string $parent_id, array $all_nodes ): array {
		$children = [];

		foreach ( $all_nodes as $node_id => $node ) {
			if ( isset( $node->parent ) && $node->parent === $parent_id ) {
				$children[ $node_id ] = $node;
			}
		}

		// Sort by position
		uasort( $children, function( $a, $b ) {
			return ( $a->position ?? 0 ) <=> ( $b->position ?? 0 );
		});

		return $children;
	}

	/**
	 * Parse single Beaver Builder node
	 *
	 * @param object $node Node data.
	 * @param array  $all_nodes All nodes for recursive parsing.
	 * @return WPBC_Component|null Parsed component or null.
	 */
	private function parse_node( $node, array $all_nodes ): ?WPBC_Component {
		if ( ! is_object( $node ) ) {
			return null;
		}

		$node_type = $node->type ?? '';

		// Determine component type based on node type
		switch ( $node_type ) {
			case 'row':
				return $this->parse_row( $node, $all_nodes );

			case 'column-group':
				return $this->parse_column_group( $node, $all_nodes );

			case 'column':
				return $this->parse_column( $node, $all_nodes );

			default:
				// Must be a module
				return $this->parse_module( $node, $all_nodes );
		}
	}

	/**
	 * Parse Beaver Builder row
	 *
	 * @param object $node Row node data.
	 * @param array  $all_nodes All nodes.
	 * @return WPBC_Component|null Parsed row component.
	 */
	private function parse_row( $node, array $all_nodes ): ?WPBC_Component {
		$settings = (array) ( $node->settings ?? new \stdClass() );
		$attributes = $this->normalize_settings( $settings );

		$row = new WPBC_Component([
			'type'       => 'container',
			'category'   => 'layout',
			'attributes' => $attributes,
			'metadata'   => [
				'source_framework' => 'beaver-builder',
				'original_type'    => 'row',
				'node_id'          => $node->node ?? '',
				'bb_settings'      => $settings,
			],
		]);

		// Parse column groups (child nodes)
		$children = $this->get_child_nodes( $node->node, $all_nodes );
		foreach ( $children as $child_node ) {
			$child_component = $this->parse_node( $child_node, $all_nodes );
			if ( $child_component ) {
				$row->add_child( $child_component );
			}
		}

		return $row;
	}

	/**
	 * Parse Beaver Builder column group
	 *
	 * @param object $node Column group node data.
	 * @param array  $all_nodes All nodes.
	 * @return WPBC_Component|null Parsed column group component.
	 */
	private function parse_column_group( $node, array $all_nodes ): ?WPBC_Component {
		$settings = (array) ( $node->settings ?? new \stdClass() );
		$attributes = $this->normalize_settings( $settings );

		$column_group = new WPBC_Component([
			'type'       => 'row',
			'category'   => 'layout',
			'attributes' => $attributes,
			'metadata'   => [
				'source_framework' => 'beaver-builder',
				'original_type'    => 'column-group',
				'node_id'          => $node->node ?? '',
				'bb_settings'      => $settings,
			],
		]);

		// Parse columns (child nodes)
		$children = $this->get_child_nodes( $node->node, $all_nodes );
		foreach ( $children as $child_node ) {
			$child_component = $this->parse_node( $child_node, $all_nodes );
			if ( $child_component ) {
				$column_group->add_child( $child_component );
			}
		}

		return $column_group;
	}

	/**
	 * Parse Beaver Builder column
	 *
	 * @param object $node Column node data.
	 * @param array  $all_nodes All nodes.
	 * @return WPBC_Component|null Parsed column component.
	 */
	private function parse_column( $node, array $all_nodes ): ?WPBC_Component {
		$settings = (array) ( $node->settings ?? new \stdClass() );
		$attributes = $this->normalize_settings( $settings );

		// Extract column width (Beaver Builder uses size property)
		$width = $settings['size'] ?? 100;
		$attributes['width'] = is_numeric( $width ) ? $width . '%' : $width;

		$column = new WPBC_Component([
			'type'       => 'column',
			'category'   => 'layout',
			'attributes' => $attributes,
			'metadata'   => [
				'source_framework' => 'beaver-builder',
				'original_type'    => 'column',
				'node_id'          => $node->node ?? '',
				'bb_settings'      => $settings,
			],
		]);

		// Parse modules (child nodes)
		$children = $this->get_child_nodes( $node->node, $all_nodes );
		foreach ( $children as $child_node ) {
			$child_component = $this->parse_node( $child_node, $all_nodes );
			if ( $child_component ) {
				$column->add_child( $child_component );
			}
		}

		return $column;
	}

	/**
	 * Parse Beaver Builder module
	 *
	 * @param object $node Module node data.
	 * @param array  $all_nodes All nodes.
	 * @return WPBC_Component|null Parsed module component.
	 */
	private function parse_module( $node, array $all_nodes ): ?WPBC_Component {
		$module_type = $node->type ?? '';
		$settings = (array) ( $node->settings ?? new \stdClass() );

		// Map module type to universal type
		$universal_type = $this->map_module_type( $module_type );

		$attributes = $this->normalize_settings( $settings );

		// Extract content based on module type
		$content = $this->extract_module_content( $module_type, $settings );

		$component = new WPBC_Component([
			'type'       => $universal_type,
			'category'   => $this->get_category( $universal_type ),
			'attributes' => $attributes,
			'content'    => $content,
			'metadata'   => [
				'source_framework' => 'beaver-builder',
				'original_type'    => $module_type,
				'node_id'          => $node->node ?? '',
				'bb_settings'      => $settings,
			],
		]);

		return $component;
	}

	/**
	 * Map Beaver Builder module type to universal type
	 *
	 * @param string $module_type Beaver Builder module type.
	 * @return string Universal type.
	 */
	private function map_module_type( string $module_type ): string {
		$type_map = [
			'heading'          => 'heading',
			'rich-text'        => 'text',
			'photo'            => 'image',
			'button'           => 'button',
			'button-group'     => 'button-group',
			'html'             => 'html',
			'separator'        => 'divider',
			'icon'             => 'icon',
			'icon-group'       => 'icon-list',
			'gallery'          => 'gallery',
			'video'            => 'video',
			'audio'            => 'audio',
			'map'              => 'map',
			'slideshow'        => 'slider',
			'slider'           => 'slider',
			'content-slider'   => 'slider',
			'testimonials'     => 'testimonial',
			'callout'          => 'callout',
			'cta'              => 'call-to-action',
			'contact-form'     => 'form',
			'login-form'       => 'login',
			'subscribe-form'   => 'form',
			'menu'             => 'menu',
			'search'           => 'search',
			'accordion'        => 'accordion',
			'tabs'             => 'tabs',
			'pricing-table'    => 'pricing-table',
			'sidebar'          => 'sidebar',
			'countdown'        => 'countdown',
			'number-counter'   => 'counter',
			'posts'            => 'posts',
			'posts-carousel'   => 'posts',
			'posts-slider'     => 'posts',
		];

		return $type_map[ $module_type ] ?? $module_type;
	}

	/**
	 * Get component category based on type
	 *
	 * @param string $type Universal type.
	 * @return string Category.
	 */
	private function get_category( string $type ): string {
		$category_map = [
			'heading'          => 'content',
			'text'             => 'content',
			'button'           => 'interactive',
			'button-group'     => 'interactive',
			'image'            => 'media',
			'gallery'          => 'media',
			'video'            => 'media',
			'audio'            => 'media',
			'icon'             => 'decorative',
			'icon-list'        => 'content',
			'divider'          => 'decorative',
			'html'             => 'custom',
			'map'              => 'media',
			'slider'           => 'media',
			'testimonial'      => 'content',
			'callout'          => 'content',
			'call-to-action'   => 'interactive',
			'form'             => 'interactive',
			'login'            => 'interactive',
			'menu'             => 'navigation',
			'search'           => 'interactive',
			'accordion'        => 'interactive',
			'tabs'             => 'interactive',
			'pricing-table'    => 'content',
			'sidebar'          => 'layout',
			'countdown'        => 'interactive',
			'counter'          => 'interactive',
			'posts'            => 'content',
		];

		return $category_map[ $type ] ?? 'general';
	}

	/**
	 * Extract module content based on type
	 *
	 * @param string $module_type Module type.
	 * @param array  $settings Module settings.
	 * @return string Content.
	 */
	private function extract_module_content( string $module_type, array $settings ): string {
		switch ( $module_type ) {
			case 'heading':
				return $settings['heading'] ?? '';

			case 'rich-text':
				return $settings['text'] ?? '';

			case 'html':
				return $settings['html'] ?? '';

			case 'button':
				return $settings['text'] ?? '';

			case 'callout':
				return $settings['text'] ?? '';

			case 'cta':
				return $settings['text'] ?? '';

			case 'testimonials':
				return $settings['text'] ?? '';

			default:
				// Try common content fields
				return $settings['content'] ?? $settings['text'] ?? '';
		}
	}

	/**
	 * Normalize settings to universal attributes
	 *
	 * @param array $settings Beaver Builder settings.
	 * @return array Normalized attributes.
	 */
	private function normalize_settings( array $settings ): array {
		$attributes = [];

		// Background
		if ( ! empty( $settings['bg_color'] ) ) {
			$attributes['background-color'] = $settings['bg_color'];
		}

		if ( ! empty( $settings['bg_image'] ) ) {
			$attributes['background-image'] = $settings['bg_image'];
		}

		// Padding
		if ( isset( $settings['padding_top'] ) ) {
			$attributes['padding-top'] = $this->normalize_spacing( $settings['padding_top'] );
		}
		if ( isset( $settings['padding_bottom'] ) ) {
			$attributes['padding-bottom'] = $this->normalize_spacing( $settings['padding_bottom'] );
		}
		if ( isset( $settings['padding_left'] ) ) {
			$attributes['padding-left'] = $this->normalize_spacing( $settings['padding_left'] );
		}
		if ( isset( $settings['padding_right'] ) ) {
			$attributes['padding-right'] = $this->normalize_spacing( $settings['padding_right'] );
		}

		// Margin
		if ( isset( $settings['margin_top'] ) ) {
			$attributes['margin-top'] = $this->normalize_spacing( $settings['margin_top'] );
		}
		if ( isset( $settings['margin_bottom'] ) ) {
			$attributes['margin-bottom'] = $this->normalize_spacing( $settings['margin_bottom'] );
		}

		// Border
		if ( ! empty( $settings['border'] ) ) {
			$attributes['border'] = $settings['border'];
		}
		if ( ! empty( $settings['border_color'] ) ) {
			$attributes['border-color'] = $settings['border_color'];
		}
		if ( isset( $settings['border_radius'] ) ) {
			$attributes['border-radius'] = $this->normalize_spacing( $settings['border_radius'] );
		}

		// Text
		if ( ! empty( $settings['color'] ) ) {
			$attributes['color'] = $settings['color'];
		}
		if ( ! empty( $settings['font_family'] ) ) {
			$attributes['font-family'] = $settings['font_family'];
		}
		if ( ! empty( $settings['font_size'] ) ) {
			$attributes['font-size'] = $this->normalize_spacing( $settings['font_size'] );
		}
		if ( ! empty( $settings['font_weight'] ) ) {
			$attributes['font-weight'] = $settings['font_weight'];
		}
		if ( ! empty( $settings['text_align'] ) ) {
			$attributes['text-align'] = $settings['text_align'];
		}

		// Link (for buttons, CTAs)
		if ( ! empty( $settings['link'] ) ) {
			$attributes['href'] = $settings['link'];
		}
		if ( ! empty( $settings['link_target'] ) ) {
			$attributes['target'] = $settings['link_target'];
		}

		// Image (for photo modules)
		if ( ! empty( $settings['photo_src'] ) ) {
			$attributes['src'] = $settings['photo_src'];
		}
		if ( ! empty( $settings['alt'] ) ) {
			$attributes['alt'] = $settings['alt'];
		}

		// Alignment
		if ( ! empty( $settings['align'] ) ) {
			$attributes['align'] = $settings['align'];
		}

		// Custom classes and IDs
		if ( ! empty( $settings['class'] ) ) {
			$attributes['class'] = $settings['class'];
		}
		if ( ! empty( $settings['id'] ) ) {
			$attributes['id'] = $settings['id'];
		}

		return $attributes;
	}

	/**
	 * Normalize spacing values
	 *
	 * @param mixed $value Spacing value.
	 * @return string Normalized value with units.
	 */
	private function normalize_spacing( $value ): string {
		if ( empty( $value ) ) {
			return '0';
		}

		// If it's already a string with units, return as-is
		if ( is_string( $value ) && preg_match( '/\d+(px|em|rem|%|vh|vw)/', $value ) ) {
			return $value;
		}

		// If it's a number, assume pixels
		if ( is_numeric( $value ) ) {
			return $value . 'px';
		}

		return (string) $value;
	}

	/**
	 * Validate Beaver Builder content
	 *
	 * @param mixed $content Content to validate.
	 * @return bool True if valid.
	 */
	public function validate( $content ): bool {
		// Handle serialized string
		if ( is_string( $content ) ) {
			$content = maybe_unserialize( $content );
		}

		// Must be an array of node objects
		if ( ! is_array( $content ) || empty( $content ) ) {
			return false;
		}

		// Check if it looks like Beaver Builder data
		// Look for nodes with required properties
		foreach ( $content as $node ) {
			if ( is_object( $node ) && isset( $node->node ) && isset( $node->type ) ) {
				return true;
			}
		}

		return false;
	}
}
