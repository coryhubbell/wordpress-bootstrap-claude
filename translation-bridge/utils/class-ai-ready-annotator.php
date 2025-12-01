<?php
/**
 * AI-Ready HTML Annotator
 *
 * Adds AI-friendly attributes and documentation to any HTML output:
 * - data-ai-editable attributes for component identification
 * - data-ai-type for semantic type information
 * - Natural language documentation comments
 * - Clear modification instructions for AI assistants
 *
 * This is NOT a framework converter - it's a post-processing layer
 * that can be applied to ANY conversion output via the --ai-ready flag.
 *
 * @package DevelopmentTranslation_Bridge
 * @subpackage Translation_Bridge
 * @since 3.4.0
 */

namespace DEVTB\TranslationBridge\Utils;

/**
 * Class DEVTB_AI_Ready_Annotator
 *
 * Add AI-friendly attributes and documentation to HTML output.
 * Use with any framework conversion to make output easier for AI to understand and modify.
 */
class DEVTB_AI_Ready_Annotator {

	/**
	 * Documentation level
	 * verbose: Maximum documentation with extensive comments
	 * standard: Balanced documentation
	 * minimal: Essential attributes only, minimal comments
	 *
	 * @var string
	 */
	private string $doc_level = 'standard';

	/**
	 * Component counter for tracking
	 *
	 * @var int
	 */
	private int $component_count = 0;

	/**
	 * Options for annotation
	 *
	 * @var array
	 */
	private array $options = [];

	/**
	 * Constructor
	 *
	 * @param array $options Annotation options.
	 */
	public function __construct( array $options = [] ) {
		$this->options = array_merge( [
			'doc_level'       => 'standard',
			'add_header'      => true,
			'add_footer'      => true,
			'add_comments'    => true,
		], $options );

		$this->doc_level = $this->options['doc_level'];
	}

	/**
	 * Annotate HTML content with AI-friendly attributes
	 *
	 * Main entry point for the annotator.
	 *
	 * @param string $html HTML content to annotate.
	 * @param array  $options Optional override options.
	 * @return string Annotated HTML.
	 */
	public function annotate( string $html, array $options = [] ): string {
		$options = array_merge( $this->options, $options );
		$this->component_count = 0;

		// Don't process empty content
		if ( empty( trim( $html ) ) ) {
			return $html;
		}

		// Add AI-ready attributes to elements
		$html = $this->add_ai_attributes( $html );

		// Add documentation header
		if ( $options['add_header'] ) {
			$html = $this->generate_header() . $html;
		}

		// Add documentation footer
		if ( $options['add_footer'] ) {
			$html .= $this->generate_footer();
		}

		return $html;
	}

	/**
	 * Add AI-friendly attributes to HTML elements
	 *
	 * @param string $html HTML content.
	 * @return string HTML with AI attributes.
	 */
	private function add_ai_attributes( string $html ): string {
		$dom = new \DOMDocument();
		$previous_value = libxml_use_internal_errors( true );

		// Load HTML
		$dom->loadHTML(
			'<?xml encoding="UTF-8"><div id="ai-wrapper">' . $html . '</div>',
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);

		libxml_clear_errors();
		libxml_use_internal_errors( $previous_value );

		// Find wrapper and process children
		$wrapper = $dom->getElementById( 'ai-wrapper' );
		if ( $wrapper ) {
			$this->process_element( $wrapper );
		}

		// Get processed HTML
		$result = $dom->saveHTML( $wrapper );

		// Remove wrapper div
		$result = preg_replace( '/^<div id="ai-wrapper">/', '', $result );
		$result = preg_replace( '/<\/div>$/', '', $result );

		return $result;
	}

	/**
	 * Process a DOM element and its children
	 *
	 * @param \DOMElement $element Element to process.
	 * @return void
	 */
	private function process_element( \DOMElement $element ): void {
		// Skip wrapper
		if ( $element->getAttribute( 'id' ) === 'ai-wrapper' ) {
			foreach ( $element->childNodes as $child ) {
				if ( $child instanceof \DOMElement ) {
					$this->process_element( $child );
				}
			}
			return;
		}

		// Detect component type
		$type = $this->detect_component_type( $element );

		if ( $type ) {
			$this->component_count++;

			// Add AI attributes
			$element->setAttribute( 'data-ai-editable', $type );
			$element->setAttribute( 'data-ai-type', $this->get_ai_type_category( $type ) );

			// Add component ID for tracking
			if ( ! $element->hasAttribute( 'id' ) ) {
				$element->setAttribute( 'data-ai-id', 'ai-' . $type . '-' . $this->component_count );
			}
		}

		// Process children recursively
		foreach ( $element->childNodes as $child ) {
			if ( $child instanceof \DOMElement ) {
				$this->process_element( $child );
			}
		}
	}

	/**
	 * Detect component type from element
	 *
	 * @param \DOMElement $element DOM element.
	 * @return string|null Component type.
	 */
	private function detect_component_type( \DOMElement $element ): ?string {
		$tag = strtolower( $element->tagName );
		$classes = $element->getAttribute( 'class' );

		// Button detection
		if ( strpos( $classes, 'btn' ) !== false || $tag === 'button' ) {
			return 'button';
		}

		// Heading detection
		if ( preg_match( '/^h[1-6]$/i', $tag ) ) {
			return 'heading';
		}

		// Text/paragraph
		if ( $tag === 'p' ) {
			return 'text';
		}

		// Image
		if ( $tag === 'img' ) {
			return 'image';
		}

		// Container
		if ( strpos( $classes, 'container' ) !== false ) {
			return 'container';
		}

		// Row
		if ( strpos( $classes, 'row' ) !== false ) {
			return 'row';
		}

		// Column
		if ( preg_match( '/\bcol(-\w+)?(-\d+)?\b/', $classes ) ) {
			return 'column';
		}

		// Card
		if ( strpos( $classes, 'card' ) !== false ) {
			return 'card';
		}

		// Section
		if ( $tag === 'section' ) {
			return 'section';
		}

		// Nav
		if ( $tag === 'nav' || strpos( $classes, 'nav' ) !== false || strpos( $classes, 'navbar' ) !== false ) {
			return 'nav';
		}

		// Form
		if ( $tag === 'form' ) {
			return 'form';
		}

		// Form inputs
		if ( in_array( $tag, [ 'input', 'textarea', 'select' ], true ) ) {
			return 'form-field';
		}

		// Links (that aren't buttons)
		if ( $tag === 'a' && strpos( $classes, 'btn' ) === false ) {
			return 'link';
		}

		// Lists
		if ( in_array( $tag, [ 'ul', 'ol' ], true ) ) {
			return 'list';
		}

		// Tables
		if ( $tag === 'table' ) {
			return 'table';
		}

		// Footer
		if ( $tag === 'footer' || strpos( $classes, 'footer' ) !== false ) {
			return 'footer';
		}

		// Header
		if ( $tag === 'header' || strpos( $classes, 'header' ) !== false ) {
			return 'header';
		}

		// Hero sections
		if ( strpos( $classes, 'hero' ) !== false || strpos( $classes, 'jumbotron' ) !== false ) {
			return 'hero';
		}

		// Alert/notice
		if ( strpos( $classes, 'alert' ) !== false ) {
			return 'alert';
		}

		// Modal
		if ( strpos( $classes, 'modal' ) !== false ) {
			return 'modal';
		}

		return null;
	}

	/**
	 * Get AI type category for a component
	 *
	 * @param string $type Component type.
	 * @return string Category (layout, content, interactive, etc).
	 */
	private function get_ai_type_category( string $type ): string {
		$categories = [
			'layout'      => [ 'container', 'row', 'column', 'section', 'header', 'footer' ],
			'content'     => [ 'heading', 'text', 'image', 'list', 'table', 'card' ],
			'interactive' => [ 'button', 'link', 'form', 'form-field', 'nav', 'modal' ],
			'feedback'    => [ 'alert', 'progress', 'spinner' ],
			'hero'        => [ 'hero', 'cta' ],
		];

		foreach ( $categories as $category => $types ) {
			if ( in_array( $type, $types, true ) ) {
				return $category;
			}
		}

		return 'general';
	}

	/**
	 * Generate documentation header
	 *
	 * @return string Header comment block.
	 */
	private function generate_header(): string {
		if ( $this->doc_level === 'minimal' ) {
			return "<!-- AI-Ready HTML - Generated by DevelopmentTranslation Bridge -->\n\n";
		}

		return <<<HTML
<!--
======================================================================
                        AI-READY HTML
              Generated by DevelopmentTranslation Bridge
======================================================================

FOR AI ASSISTANTS:
This HTML has been enhanced with AI-friendly attributes for easier
understanding and modification.

ATTRIBUTES:
- data-ai-editable: Component type (button, heading, text, etc.)
- data-ai-type: Category (layout, content, interactive, etc.)
- data-ai-id: Unique identifier for tracking

MODIFICATION GUIDE:
- Text content: Directly edit text within elements
- Styling: Modify class attributes (Bootstrap classes used)
- Structure: Components are modular and can be rearranged
- Layout: Grid system uses container > row > column pattern

STYLING FRAMEWORK: Bootstrap 5.x
- Responsive breakpoints: sm (576px), md (768px), lg (992px), xl (1200px)
- Grid: 12-column system (col-1 through col-12)
- Utilities: spacing (m-*, p-*), colors (text-*, bg-*), display (d-*)

======================================================================
-->

HTML;
	}

	/**
	 * Generate documentation footer
	 *
	 * @return string Footer comment block.
	 */
	private function generate_footer(): string {
		if ( $this->doc_level === 'minimal' ) {
			return "\n<!-- End AI-Ready HTML -->\n";
		}

		$timestamp = gmdate( 'Y-m-d H:i:s' );

		return <<<HTML


<!--
======================================================================
                        END OF DOCUMENT
======================================================================

SUMMARY:
- Total annotated components: {$this->component_count}
- Generated: {$timestamp} UTC
- Annotator: AI-Ready Annotator v3.4.0

NEXT STEPS:
Ask your AI assistant to:
- "Update the heading text to..."
- "Change the button color to..."
- "Add a new section with..."
- "Improve accessibility by..."
- "Make this more mobile-friendly"

CONVERTING BACK:
This HTML can be translated to any supported framework:
  devtb translate bootstrap divi output.html
  devtb translate bootstrap elementor output.html

======================================================================
-->

HTML;
	}

	/**
	 * Set documentation level
	 *
	 * @param string $level Documentation level (verbose, standard, minimal).
	 * @return self
	 */
	public function set_doc_level( string $level ): self {
		$this->doc_level = in_array( $level, [ 'verbose', 'standard', 'minimal' ], true )
			? $level
			: 'standard';
		return $this;
	}

	/**
	 * Get component count from last annotation
	 *
	 * @return int Component count.
	 */
	public function get_component_count(): int {
		return $this->component_count;
	}
}
