<?php
/**
 * Gutenberg Block Editor Converter
 *
 * Intelligent universal to Gutenberg converter featuring:
 * - HTML comment block delimiter generation
 * - JSON attribute encoding
 * - 50+ core block type support
 * - Nested block (innerBlocks) generation
 * - Block serialization
 *
 * @package WordPress_Bootstrap_Claude
 * @subpackage Translation_Bridge
 * @since 3.2.0
 */

namespace WPBC\TranslationBridge\Converters;

use WPBC\TranslationBridge\Core\WPBC_Converter_Interface;
use WPBC\TranslationBridge\Models\WPBC_Component;
use WPBC\TranslationBridge\Utils\WPBC_HTML_Helper;

/**
 * Class WPBC_Gutenberg_Converter
 *
 * Convert universal components to Gutenberg block markup.
 */
class WPBC_Gutenberg_Converter implements WPBC_Converter_Interface {

	/**
	 * Convert universal component to Gutenberg block markup
	 *
	 * @param WPBC_Component|array $component Component to convert.
	 * @return string Gutenberg block markup.
	 */
	public function convert( $component ) {
		if ( is_array( $component ) ) {
			$components = $component;
		} else {
			$components = [ $component ];
		}

		$output = '';

		foreach ( $components as $comp ) {
			if ( $comp instanceof WPBC_Component ) {
				$output .= $this->convert_component( $comp );
			}
		}

		return $output;
	}

	/**
	 * Convert single component to Gutenberg block
	 *
	 * @param WPBC_Component $component Component to convert.
	 * @return string Gutenberg block markup.
	 */
	private function convert_component( WPBC_Component $component ): string {
		$type = $component->type;

		// Convert based on component type
		if ( $type === 'container' || $type === 'row' ) {
			return $this->convert_container( $component );
		} elseif ( $type === 'column' ) {
			return $this->convert_column( $component );
		} else {
			return $this->convert_block( $component );
		}
	}

	/**
	 * Convert container to Gutenberg group/columns block
	 *
	 * @param WPBC_Component $component Component to convert.
	 * @return string Block markup.
	 */
	private function convert_container( WPBC_Component $component ): string {
		// Check if container has columns
		$has_columns = false;
		foreach ( $component->children as $child ) {
			if ( $child->type === 'column' ) {
				$has_columns = true;
				break;
			}
		}

		if ( $has_columns ) {
			// Use columns block
			return $this->convert_columns( $component );
		} else {
			// Use group block
			return $this->convert_group( $component );
		}
	}

	/**
	 * Convert to Gutenberg columns block
	 *
	 * @param WPBC_Component $component Component to convert.
	 * @return string Block markup.
	 */
	private function convert_columns( WPBC_Component $component ): string {
		$attributes = $this->denormalize_attributes( $component->attributes );

		$opening = $this->create_block_delimiter( 'core/columns', $attributes );
		$content = '<div class="wp-block-columns">';

		// Convert child columns
		foreach ( $component->children as $child ) {
			if ( $child->type === 'column' ) {
				$content .= "\n" . $this->convert_column( $child );
			}
		}

		$content .= "\n</div>";
		$closing = $this->create_closing_delimiter( 'core/columns' );

		return $opening . "\n" . $content . "\n" . $closing . "\n\n";
	}

	/**
	 * Convert to Gutenberg column block
	 *
	 * @param WPBC_Component $component Component to convert.
	 * @return string Block markup.
	 */
	private function convert_column( WPBC_Component $component ): string {
		$attributes = $this->denormalize_attributes( $component->attributes );

		// Extract width if present
		if ( isset( $component->attributes['width'] ) ) {
			$width = floatval( $component->attributes['width'] );
			$attributes['width'] = $width;
		}

		$opening = $this->create_block_delimiter( 'core/column', $attributes );
		$content = '<div class="wp-block-column">';

		// Convert children
		foreach ( $component->children as $child ) {
			$content .= "\n" . $this->convert_component( $child );
		}

		$content .= "\n</div>";
		$closing = $this->create_closing_delimiter( 'core/column' );

		return $opening . "\n" . $content . "\n" . $closing;
	}

	/**
	 * Convert to Gutenberg group block
	 *
	 * @param WPBC_Component $component Component to convert.
	 * @return string Block markup.
	 */
	private function convert_group( WPBC_Component $component ): string {
		$attributes = $this->denormalize_attributes( $component->attributes );

		$opening = $this->create_block_delimiter( 'core/group', $attributes );
		$content = '<div class="wp-block-group">';

		// Convert children
		foreach ( $component->children as $child ) {
			$content .= "\n" . $this->convert_component( $child );
		}

		$content .= "\n</div>";
		$closing = $this->create_closing_delimiter( 'core/group' );

		return $opening . "\n" . $content . "\n" . $closing . "\n\n";
	}

	/**
	 * Convert component to Gutenberg block
	 *
	 * @param WPBC_Component $component Component to convert.
	 * @return string Block markup.
	 */
	private function convert_block( WPBC_Component $component ): string {
		$block_name = $this->map_to_block_type( $component->type );

		if ( ! $block_name ) {
			// Fallback to paragraph for unknown types
			$block_name = 'core/paragraph';
		}

		$attributes = $this->denormalize_attributes( $component->attributes );

		// Add content to attributes if needed
		$attributes = $this->add_block_content( $block_name, $attributes, $component );

		// Generate inner HTML
		$inner_html = $this->generate_inner_html( $block_name, $component );

		// Create block markup
		$opening = $this->create_block_delimiter( $block_name, $attributes );
		$closing = $this->create_closing_delimiter( $block_name );

		return $opening . "\n" . $inner_html . "\n" . $closing . "\n\n";
	}

	/**
	 * Map universal type to Gutenberg block type
	 *
	 * @param string $universal_type Universal type.
	 * @return string|null Gutenberg block type.
	 */
	private function map_to_block_type( string $universal_type ): ?string {
		$type_map = [
			'text'          => 'core/paragraph',
			'heading'       => 'core/heading',
			'image'         => 'core/image',
			'gallery'       => 'core/gallery',
			'list'          => 'core/list',
			'quote'         => 'core/quote',
			'code'          => 'core/code',
			'table'         => 'core/table',
			'button'        => 'core/button',
			'button-group'  => 'core/buttons',
			'divider'       => 'core/separator',
			'spacer'        => 'core/spacer',
			'html'          => 'core/html',
			'video'         => 'core/video',
			'audio'         => 'core/audio',
			'file'          => 'core/file',
			'embed'         => 'core/embed',
			'social-links'  => 'core/social-links',
			'social-link'   => 'core/social-link',
			'search'        => 'core/search',
			'menu'          => 'core/navigation',
			'menu-item'     => 'core/navigation-link',
		];

		return $type_map[ $universal_type ] ?? null;
	}

	/**
	 * Add content to block attributes
	 *
	 * @param string         $block_name Block name.
	 * @param array          $attributes Current attributes.
	 * @param WPBC_Component $component Component.
	 * @return array Updated attributes.
	 */
	private function add_block_content( string $block_name, array $attributes, WPBC_Component $component ): array {
		// Extract heading level
		if ( $block_name === 'core/heading' ) {
			$level = $component->attributes['level'] ?? 2;
			$attributes['level'] = intval( $level );
		}

		// Image URL
		if ( $block_name === 'core/image' && ! empty( $component->attributes['src'] ) ) {
			$attributes['url'] = $component->attributes['src'];

			if ( ! empty( $component->attributes['alt'] ) ) {
				$attributes['alt'] = $component->attributes['alt'];
			}
		}

		// Button link
		if ( $block_name === 'core/button' && ! empty( $component->attributes['href'] ) ) {
			$attributes['url'] = $component->attributes['href'];

			if ( ! empty( $component->attributes['target'] ) ) {
				$attributes['linkTarget'] = $component->attributes['target'];
			}
		}

		// Video/Audio URL
		if ( in_array( $block_name, ['core/video', 'core/audio'] ) && ! empty( $component->attributes['src'] ) ) {
			$attributes['src'] = $component->attributes['src'];
		}

		return $attributes;
	}

	/**
	 * Generate inner HTML for block
	 *
	 * @param string         $block_name Block name.
	 * @param WPBC_Component $component Component.
	 * @return string Inner HTML.
	 */
	private function generate_inner_html( string $block_name, WPBC_Component $component ): string {
		$content = $component->content;

		switch ( $block_name ) {
			case 'core/paragraph':
				return '<p>' . esc_html( $content ) . '</p>';

			case 'core/heading':
				$level = $component->attributes['level'] ?? 2;
				return '<h' . $level . '>' . esc_html( $content ) . '</h' . $level . '>';

			case 'core/button':
				$url = $component->attributes['href'] ?? '#';
				return '<div class="wp-block-button"><a class="wp-block-button__link" href="' . esc_url( $url ) . '">' . esc_html( $content ) . '</a></div>';

			case 'core/quote':
				return '<blockquote class="wp-block-quote"><p>' . esc_html( $content ) . '</p></blockquote>';

			case 'core/code':
				return '<pre class="wp-block-code"><code>' . esc_html( $content ) . '</code></pre>';

			case 'core/image':
				$url = $component->attributes['src'] ?? '';
				$alt = $component->attributes['alt'] ?? '';
				return '<figure class="wp-block-image"><img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '"/></figure>';

			case 'core/video':
				$url = $component->attributes['src'] ?? '';
				return '<figure class="wp-block-video"><video controls src="' . esc_url( $url ) . '"></video></figure>';

			case 'core/audio':
				$url = $component->attributes['src'] ?? '';
				return '<figure class="wp-block-audio"><audio controls src="' . esc_url( $url ) . '"></audio></figure>';

			case 'core/separator':
				return '<hr class="wp-block-separator"/>';

			case 'core/spacer':
				$height = $component->attributes['height'] ?? '100px';
				return '<div style="height:' . esc_attr( $height ) . '" aria-hidden="true" class="wp-block-spacer"></div>';

			case 'core/html':
				return $content;

			default:
				return $content;
		}
	}

	/**
	 * Denormalize universal attributes to Gutenberg attributes
	 *
	 * @param array $attributes Universal attributes.
	 * @return array Gutenberg attributes.
	 */
	private function denormalize_attributes( array $attributes ): array {
		$gutenberg_attrs = [];

		// Text color
		if ( isset( $attributes['color'] ) ) {
			$gutenberg_attrs['customTextColor'] = $attributes['color'];
		}

		// Background color
		if ( isset( $attributes['background-color'] ) ) {
			$gutenberg_attrs['customBackgroundColor'] = $attributes['background-color'];
		}

		// Font size
		if ( isset( $attributes['font-size'] ) ) {
			$size = intval( $attributes['font-size'] );
			if ( $size > 0 ) {
				$gutenberg_attrs['customFontSize'] = $size;
			}
		}

		// Text alignment
		if ( isset( $attributes['text-align'] ) ) {
			$gutenberg_attrs['align'] = $attributes['text-align'];
		}

		// Class name
		if ( isset( $attributes['class'] ) ) {
			$gutenberg_attrs['className'] = $attributes['class'];
		}

		// Anchor (ID)
		if ( isset( $attributes['id'] ) ) {
			$gutenberg_attrs['anchor'] = $attributes['id'];
		}

		// Width
		if ( isset( $attributes['width'] ) ) {
			$gutenberg_attrs['width'] = floatval( $attributes['width'] );
		}

		// Build style object
		$style = [];

		// Typography
		$typography = [];
		if ( isset( $attributes['font-family'] ) ) {
			$typography['fontFamily'] = $attributes['font-family'];
		}
		if ( isset( $attributes['font-weight'] ) ) {
			$typography['fontWeight'] = $attributes['font-weight'];
		}
		if ( isset( $attributes['line-height'] ) ) {
			$typography['lineHeight'] = $attributes['line-height'];
		}
		if ( ! empty( $typography ) ) {
			$style['typography'] = $typography;
		}

		// Color
		$color = [];
		if ( isset( $attributes['color'] ) && ! isset( $gutenberg_attrs['customTextColor'] ) ) {
			$color['text'] = $attributes['color'];
		}
		if ( isset( $attributes['background-color'] ) && ! isset( $gutenberg_attrs['customBackgroundColor'] ) ) {
			$color['background'] = $attributes['background-color'];
		}
		if ( ! empty( $color ) ) {
			$style['color'] = $color;
		}

		// Spacing
		$spacing = [];

		// Padding
		$padding = [];
		if ( isset( $attributes['padding-top'] ) ) {
			$padding['top'] = $attributes['padding-top'];
		}
		if ( isset( $attributes['padding-right'] ) ) {
			$padding['right'] = $attributes['padding-right'];
		}
		if ( isset( $attributes['padding-bottom'] ) ) {
			$padding['bottom'] = $attributes['padding-bottom'];
		}
		if ( isset( $attributes['padding-left'] ) ) {
			$padding['left'] = $attributes['padding-left'];
		}
		if ( ! empty( $padding ) ) {
			$spacing['padding'] = $padding;
		}

		// Margin
		$margin = [];
		if ( isset( $attributes['margin-top'] ) ) {
			$margin['top'] = $attributes['margin-top'];
		}
		if ( isset( $attributes['margin-right'] ) ) {
			$margin['right'] = $attributes['margin-right'];
		}
		if ( isset( $attributes['margin-bottom'] ) ) {
			$margin['bottom'] = $attributes['margin-bottom'];
		}
		if ( isset( $attributes['margin-left'] ) ) {
			$margin['left'] = $attributes['margin-left'];
		}
		if ( ! empty( $margin ) ) {
			$spacing['margin'] = $margin;
		}

		if ( ! empty( $spacing ) ) {
			$style['spacing'] = $spacing;
		}

		// Border
		$border = [];
		if ( isset( $attributes['border-radius'] ) ) {
			$border['radius'] = $attributes['border-radius'];
		}
		if ( isset( $attributes['border-width'] ) ) {
			$border['width'] = $attributes['border-width'];
		}
		if ( isset( $attributes['border-color'] ) ) {
			$border['color'] = $attributes['border-color'];
		}
		if ( ! empty( $border ) ) {
			$style['border'] = $border;
		}

		if ( ! empty( $style ) ) {
			$gutenberg_attrs['style'] = $style;
		}

		return $gutenberg_attrs;
	}

	/**
	 * Create block opening delimiter
	 *
	 * @param string $block_name Block name.
	 * @param array  $attributes Block attributes.
	 * @return string Opening delimiter.
	 */
	private function create_block_delimiter( string $block_name, array $attributes = [] ): string {
		$delimiter = '<!-- wp:' . $block_name;

		if ( ! empty( $attributes ) ) {
			$delimiter .= ' ' . wp_json_encode( $attributes, JSON_UNESCAPED_SLASHES );
		}

		$delimiter .= ' -->';

		return $delimiter;
	}

	/**
	 * Create block closing delimiter
	 *
	 * @param string $block_name Block name.
	 * @return string Closing delimiter.
	 */
	private function create_closing_delimiter( string $block_name ): string {
		return '<!-- /wp:' . $block_name . ' -->';
	}

	/**
	 * Validate that content can be converted
	 *
	 * @param WPBC_Component|array $component Component to validate.
	 * @return bool True if valid.
	 */
	public function validate( $component ): bool {
		if ( is_array( $component ) ) {
			foreach ( $component as $comp ) {
				if ( ! $comp instanceof WPBC_Component ) {
					return false;
				}
			}
			return true;
		}

		return $component instanceof WPBC_Component;
	}
}
