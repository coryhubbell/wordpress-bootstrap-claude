<?php
/**
 * Gutenberg Block Editor Parser
 *
 * Intelligent Gutenberg parser featuring:
 * - HTML comment block delimiter parsing
 * - JSON attribute extraction
 * - 50+ core block type support
 * - Nested block (innerBlocks) handling
 * - Reusable block support
 * - Dynamic and static block parsing
 *
 * @package WordPress_Bootstrap_Claude
 * @subpackage Translation_Bridge
 * @since 3.2.0
 */

namespace WPBC\TranslationBridge\Parsers;

use WPBC\TranslationBridge\Core\WPBC_Parser_Interface;
use WPBC\TranslationBridge\Models\WPBC_Component;
use WPBC\TranslationBridge\Utils\WPBC_HTML_Helper;

/**
 * Class WPBC_Gutenberg_Parser
 *
 * Parse Gutenberg block markup into universal components.
 */
class WPBC_Gutenberg_Parser implements WPBC_Parser_Interface {

	/**
	 * Supported Gutenberg core block types
	 *
	 * @var array<string>
	 */
	private array $supported_types = [
		'core/paragraph',
		'core/heading',
		'core/image',
		'core/gallery',
		'core/list',
		'core/quote',
		'core/pullquote',
		'core/code',
		'core/preformatted',
		'core/verse',
		'core/table',
		'core/button',
		'core/buttons',
		'core/columns',
		'core/column',
		'core/group',
		'core/row',
		'core/stack',
		'core/cover',
		'core/media-text',
		'core/separator',
		'core/spacer',
		'core/html',
		'core/shortcode',
		'core/video',
		'core/audio',
		'core/file',
		'core/embed',
		'core/social-links',
		'core/social-link',
		'core/search',
		'core/loginout',
		'core/navigation',
		'core/navigation-link',
		'core/page-list',
		'core/home-link',
		'core/site-logo',
		'core/site-title',
		'core/site-tagline',
		'core/query',
		'core/post-template',
		'core/post-title',
		'core/post-content',
		'core/post-date',
		'core/post-excerpt',
		'core/post-featured-image',
		'core/post-terms',
		'core/more',
		'core/nextpage',
		'core/block',
	];

	/**
	 * Parse Gutenberg content into universal components
	 *
	 * @param string $content Gutenberg block content.
	 * @return WPBC_Component[] Array of parsed components.
	 */
	public function parse( $content ): array {
		if ( ! is_string( $content ) || empty( $content ) ) {
			return [];
		}

		// Parse blocks from content
		$blocks = $this->parse_blocks( $content );

		$components = [];
		foreach ( $blocks as $block ) {
			$component = $this->parse_block( $block );
			if ( $component ) {
				$components[] = $component;
			}
		}

		return $components;
	}

	/**
	 * Parse blocks from Gutenberg content
	 *
	 * @param string $content Content with block delimiters.
	 * @return array Array of block data.
	 */
	private function parse_blocks( string $content ): array {
		// Use WordPress core parser if available
		if ( function_exists( 'parse_blocks' ) ) {
			return parse_blocks( $content );
		}

		// Fallback to manual parsing
		return $this->manual_parse_blocks( $content );
	}

	/**
	 * Manual block parser (fallback when WordPress functions not available)
	 *
	 * @param string $content Content to parse.
	 * @return array Parsed blocks.
	 */
	private function manual_parse_blocks( string $content ): array {
		$blocks = [];

		// Pattern to match block delimiters
		// <!-- wp:block-type {"attr":"value"} -->
		$pattern = '/<!--\s+wp:([a-z0-9\-\/]+)(\s+(\{[^}]*\}))?\s+(\/)?-->/';

		preg_match_all( $pattern, $content, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER );

		$position = 0;
		foreach ( $matches as $match ) {
			$full_match = $match[0][0];
			$offset = $match[0][1];
			$block_name = $match[1][0];
			$attrs_json = isset( $match[3][0] ) ? $match[3][0] : '{}';
			$is_self_closing = isset( $match[4][0] ) && $match[4][0] === '/';

			// Parse attributes
			$attributes = json_decode( $attrs_json, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				$attributes = [];
			}

			// Extract content if not self-closing
			$inner_html = '';
			$inner_blocks = [];

			if ( ! $is_self_closing ) {
				// Find closing tag
				$closing_pattern = '/<!--\s+\/wp:' . preg_quote( $block_name, '/' ) . '\s+-->/';
				if ( preg_match( $closing_pattern, $content, $closing_match, PREG_OFFSET_CAPTURE, $offset ) ) {
					$closing_offset = $closing_match[0][1];
					$content_start = $offset + strlen( $full_match );
					$content_length = $closing_offset - $content_start;
					$inner_html = substr( $content, $content_start, $content_length );

					// Check for nested blocks
					if ( strpos( $inner_html, '<!-- wp:' ) !== false ) {
						$inner_blocks = $this->manual_parse_blocks( $inner_html );
						// Remove block delimiters from innerHTML
						$inner_html = preg_replace( '/<!--\s+wp:[^>]+-->/', '', $inner_html );
						$inner_html = preg_replace( '/<!--\s+\/wp:[^>]+-->/', '', $inner_html );
					}
				}
			}

			$blocks[] = [
				'blockName'   => $block_name,
				'attrs'       => $attributes,
				'innerBlocks' => $inner_blocks,
				'innerHTML'   => trim( $inner_html ),
			];
		}

		return $blocks;
	}

	/**
	 * Parse single Gutenberg block
	 *
	 * @param array $block Block data.
	 * @return WPBC_Component|null Parsed component or null.
	 */
	private function parse_block( array $block ): ?WPBC_Component {
		$block_name = $block['blockName'] ?? '';

		// Skip null blocks (plain HTML/text between blocks)
		if ( empty( $block_name ) ) {
			return null;
		}

		$attributes = $block['attrs'] ?? [];
		$inner_html = $block['innerHTML'] ?? '';
		$inner_blocks = $block['innerBlocks'] ?? [];

		// Map block type to universal type
		$universal_type = $this->map_block_type( $block_name );

		// Extract content
		$content = $this->extract_block_content( $block_name, $inner_html, $attributes );

		// Normalize attributes
		$normalized_attrs = $this->normalize_attributes( $attributes );

		$component = new WPBC_Component([
			'type'       => $universal_type,
			'category'   => $this->get_category( $universal_type ),
			'attributes' => $normalized_attrs,
			'content'    => $content,
			'metadata'   => [
				'source_framework' => 'gutenberg',
				'original_type'    => $block_name,
				'gutenberg_attrs'  => $attributes,
			],
		]);

		// Parse inner blocks (nested blocks)
		if ( ! empty( $inner_blocks ) ) {
			foreach ( $inner_blocks as $inner_block ) {
				$child = $this->parse_block( $inner_block );
				if ( $child ) {
					$component->add_child( $child );
				}
			}
		}

		return $component;
	}

	/**
	 * Map Gutenberg block type to universal type
	 *
	 * @param string $block_name Gutenberg block name.
	 * @return string Universal type.
	 */
	private function map_block_type( string $block_name ): string {
		// Remove 'core/' prefix for mapping
		$short_name = str_replace( 'core/', '', $block_name );

		$type_map = [
			'paragraph'           => 'text',
			'heading'             => 'heading',
			'image'               => 'image',
			'gallery'             => 'gallery',
			'list'                => 'list',
			'quote'               => 'quote',
			'pullquote'           => 'quote',
			'code'                => 'code',
			'preformatted'        => 'code',
			'verse'               => 'text',
			'table'               => 'table',
			'button'              => 'button',
			'buttons'             => 'button-group',
			'columns'             => 'row',
			'column'              => 'column',
			'group'               => 'container',
			'row'                 => 'row',
			'stack'               => 'container',
			'cover'               => 'hero',
			'media-text'          => 'media-text',
			'separator'           => 'divider',
			'spacer'              => 'spacer',
			'html'                => 'html',
			'shortcode'           => 'shortcode',
			'video'               => 'video',
			'audio'               => 'audio',
			'file'                => 'file',
			'embed'               => 'embed',
			'social-links'        => 'social-links',
			'social-link'         => 'social-link',
			'search'              => 'search',
			'navigation'          => 'menu',
			'navigation-link'     => 'menu-item',
			'page-list'           => 'menu',
			'site-logo'           => 'image',
			'site-title'          => 'heading',
			'site-tagline'        => 'text',
			'post-title'          => 'heading',
			'post-content'        => 'text',
			'post-excerpt'        => 'text',
			'post-featured-image' => 'image',
		];

		return $type_map[ $short_name ] ?? $short_name;
	}

	/**
	 * Get component category based on type
	 *
	 * @param string $type Universal type.
	 * @return string Category.
	 */
	private function get_category( string $type ): string {
		$category_map = [
			'text'          => 'content',
			'heading'       => 'content',
			'image'         => 'media',
			'gallery'       => 'media',
			'video'         => 'media',
			'audio'         => 'media',
			'list'          => 'content',
			'quote'         => 'content',
			'code'          => 'content',
			'table'         => 'content',
			'button'        => 'interactive',
			'button-group'  => 'interactive',
			'row'           => 'layout',
			'column'        => 'layout',
			'container'     => 'layout',
			'hero'          => 'layout',
			'media-text'    => 'layout',
			'divider'       => 'decorative',
			'spacer'        => 'decorative',
			'html'          => 'custom',
			'shortcode'     => 'custom',
			'menu'          => 'navigation',
			'menu-item'     => 'navigation',
			'social-links'  => 'social',
			'social-link'   => 'social',
			'search'        => 'interactive',
		];

		return $category_map[ $type ] ?? 'general';
	}

	/**
	 * Extract content from block
	 *
	 * @param string $block_name Block name.
	 * @param string $inner_html Inner HTML.
	 * @param array  $attributes Block attributes.
	 * @return string Extracted content.
	 */
	private function extract_block_content( string $block_name, string $inner_html, array $attributes ): string {
		// For some blocks, content is in attributes
		switch ( $block_name ) {
			case 'core/heading':
			case 'core/paragraph':
				// Content is in innerHTML, but also check 'content' attribute
				return ! empty( $attributes['content'] ) ? $attributes['content'] : strip_tags( $inner_html );

			case 'core/button':
				// Button text is in innerHTML
				return strip_tags( $inner_html );

			case 'core/quote':
			case 'core/pullquote':
				// Quote content
				return strip_tags( $inner_html );

			case 'core/code':
			case 'core/preformatted':
				// Preserve code content
				return $inner_html;

			case 'core/html':
				// HTML block content
				return $attributes['content'] ?? $inner_html;

			case 'core/shortcode':
				// Shortcode content
				return $attributes['text'] ?? $inner_html;

			default:
				return strip_tags( $inner_html );
		}
	}

	/**
	 * Normalize Gutenberg attributes to universal attributes
	 *
	 * @param array $attributes Gutenberg attributes.
	 * @return array Normalized attributes.
	 */
	private function normalize_attributes( array $attributes ): array {
		$normalized = [];

		// Text color
		if ( ! empty( $attributes['textColor'] ) ) {
			$normalized['color'] = $this->resolve_color( $attributes['textColor'] );
		} elseif ( ! empty( $attributes['customTextColor'] ) ) {
			$normalized['color'] = $attributes['customTextColor'];
		}

		// Background color
		if ( ! empty( $attributes['backgroundColor'] ) ) {
			$normalized['background-color'] = $this->resolve_color( $attributes['backgroundColor'] );
		} elseif ( ! empty( $attributes['customBackgroundColor'] ) ) {
			$normalized['background-color'] = $attributes['customBackgroundColor'];
		}

		// Gradient
		if ( ! empty( $attributes['gradient'] ) ) {
			$normalized['background'] = $attributes['gradient'];
		} elseif ( ! empty( $attributes['customGradient'] ) ) {
			$normalized['background'] = $attributes['customGradient'];
		}

		// Font size
		if ( ! empty( $attributes['fontSize'] ) ) {
			$normalized['font-size'] = $this->resolve_font_size( $attributes['fontSize'] );
		} elseif ( ! empty( $attributes['customFontSize'] ) ) {
			$normalized['font-size'] = $attributes['customFontSize'] . 'px';
		}

		// Text alignment
		if ( ! empty( $attributes['align'] ) ) {
			$normalized['text-align'] = $attributes['align'];
		}

		// Width/Layout
		if ( ! empty( $attributes['width'] ) ) {
			$normalized['width'] = is_numeric( $attributes['width'] )
				? $attributes['width'] . '%'
				: $attributes['width'];
		}

		// Heading level
		if ( isset( $attributes['level'] ) ) {
			$normalized['level'] = $attributes['level'];
		}

		// Image/Media URL
		if ( ! empty( $attributes['url'] ) ) {
			$normalized['src'] = $attributes['url'];
		}

		// Alt text
		if ( ! empty( $attributes['alt'] ) ) {
			$normalized['alt'] = $attributes['alt'];
		}

		// Link URL
		if ( ! empty( $attributes['href'] ) ) {
			$normalized['href'] = $attributes['href'];
		}

		// Link target
		if ( ! empty( $attributes['linkTarget'] ) ) {
			$normalized['target'] = $attributes['linkTarget'];
		}

		// Class name
		if ( ! empty( $attributes['className'] ) ) {
			$normalized['class'] = $attributes['className'];
		}

		// Anchor (ID)
		if ( ! empty( $attributes['anchor'] ) ) {
			$normalized['id'] = $attributes['anchor'];
		}

		// Style object (inline styles)
		if ( ! empty( $attributes['style'] ) && is_array( $attributes['style'] ) ) {
			$normalized = array_merge( $normalized, $this->parse_style_object( $attributes['style'] ) );
		}

		return $normalized;
	}

	/**
	 * Parse Gutenberg style object to CSS properties
	 *
	 * @param array $style Style object.
	 * @return array CSS properties.
	 */
	private function parse_style_object( array $style ): array {
		$css = [];

		// Typography
		if ( ! empty( $style['typography'] ) ) {
			$typo = $style['typography'];

			if ( isset( $typo['fontSize'] ) ) {
				$css['font-size'] = $typo['fontSize'];
			}
			if ( isset( $typo['lineHeight'] ) ) {
				$css['line-height'] = $typo['lineHeight'];
			}
			if ( isset( $typo['fontWeight'] ) ) {
				$css['font-weight'] = $typo['fontWeight'];
			}
			if ( isset( $typo['fontFamily'] ) ) {
				$css['font-family'] = $typo['fontFamily'];
			}
		}

		// Color
		if ( ! empty( $style['color'] ) ) {
			$color = $style['color'];

			if ( isset( $color['text'] ) ) {
				$css['color'] = $color['text'];
			}
			if ( isset( $color['background'] ) ) {
				$css['background-color'] = $color['background'];
			}
		}

		// Spacing
		if ( ! empty( $style['spacing'] ) ) {
			$spacing = $style['spacing'];

			if ( isset( $spacing['padding'] ) ) {
				$css = array_merge( $css, $this->parse_spacing( 'padding', $spacing['padding'] ) );
			}
			if ( isset( $spacing['margin'] ) ) {
				$css = array_merge( $css, $this->parse_spacing( 'margin', $spacing['margin'] ) );
			}
		}

		// Border
		if ( ! empty( $style['border'] ) ) {
			$border = $style['border'];

			if ( isset( $border['radius'] ) ) {
				$css['border-radius'] = $border['radius'];
			}
			if ( isset( $border['width'] ) ) {
				$css['border-width'] = $border['width'];
			}
			if ( isset( $border['color'] ) ) {
				$css['border-color'] = $border['color'];
			}
		}

		return $css;
	}

	/**
	 * Parse spacing (padding/margin) values
	 *
	 * @param string $property Property name (padding or margin).
	 * @param mixed  $values Spacing values.
	 * @return array CSS properties.
	 */
	private function parse_spacing( string $property, $values ): array {
		$css = [];

		if ( is_string( $values ) ) {
			$css[ $property ] = $values;
		} elseif ( is_array( $values ) ) {
			foreach ( ['top', 'right', 'bottom', 'left'] as $side ) {
				if ( isset( $values[ $side ] ) ) {
					$css[ $property . '-' . $side ] = $values[ $side ];
				}
			}
		}

		return $css;
	}

	/**
	 * Resolve color name to hex/rgb value
	 *
	 * @param string $color_name Color name or value.
	 * @return string Color value.
	 */
	private function resolve_color( string $color_name ): string {
		// If it's already a hex/rgb value, return as-is
		if ( strpos( $color_name, '#' ) === 0 || strpos( $color_name, 'rgb' ) === 0 ) {
			return $color_name;
		}

		// Map common Gutenberg color names to values
		// Note: These would ideally come from theme.json
		$color_map = [
			'primary'   => '#007cba',
			'secondary' => '#005075',
			'black'     => '#000000',
			'white'     => '#ffffff',
		];

		return $color_map[ $color_name ] ?? $color_name;
	}

	/**
	 * Resolve font size name to value
	 *
	 * @param string $size_name Size name or value.
	 * @return string Font size value.
	 */
	private function resolve_font_size( string $size_name ): string {
		// If it's already a value with unit, return as-is
		if ( preg_match( '/\d+(px|em|rem)/', $size_name ) ) {
			return $size_name;
		}

		// Map common size names
		$size_map = [
			'small'  => '14px',
			'medium' => '16px',
			'large'  => '20px',
			'x-large' => '24px',
		];

		return $size_map[ $size_name ] ?? $size_name;
	}

	/**
	 * Validate Gutenberg content
	 *
	 * @param mixed $content Content to validate.
	 * @return bool True if valid.
	 */
	public function validate( $content ): bool {
		if ( ! is_string( $content ) || empty( $content ) ) {
			return false;
		}

		// Check for Gutenberg block delimiters
		return strpos( $content, '<!-- wp:' ) !== false;
	}
}
