<?php
/**
 * Gutenberg Reusable Blocks Handler
 *
 * Manages reusable blocks (synced patterns)
 *
 * @package    WordPress_Bootstrap_Claude
 * @subpackage Gutenberg
 * @version    3.2.0
 */

class WPBC_Gutenberg_Reusable_Blocks {
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
	}

	/**
	 * Create reusable block
	 *
	 * @param string $title   Block title.
	 * @param string $content Block content (block markup).
	 * @param array  $options Additional options.
	 * @return int|WP_Error Post ID or error.
	 */
	public function create_reusable_block( string $title, string $content, array $options = [] ) {
		// Create wp_block post
		$block_data = [
			'post_type'    => 'wp_block',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_content' => $content,
		];

		$block_id = wp_insert_post( $block_data );

		if ( is_wp_error( $block_id ) ) {
			$this->logger->error( 'Failed to create reusable block', [
				'title' => $title,
				'error' => $block_id->get_error_message(),
			]);
			return $block_id;
		}

		// Add metadata
		if ( ! empty( $options['description'] ) ) {
			update_post_meta( $block_id, 'description', $options['description'] );
		}

		if ( ! empty( $options['keywords'] ) ) {
			update_post_meta( $block_id, 'keywords', $options['keywords'] );
		}

		$this->logger->info( 'Reusable block created', [
			'title' => $title,
			'id'    => $block_id,
		]);

		return $block_id;
	}

	/**
	 * Update reusable block
	 *
	 * @param int    $block_id Block ID.
	 * @param string $content  New content.
	 * @return bool Success.
	 */
	public function update_reusable_block( int $block_id, string $content ): bool {
		$result = wp_update_post( [
			'ID'           => $block_id,
			'post_content' => $content,
		]);

		if ( is_wp_error( $result ) ) {
			$this->logger->error( 'Failed to update reusable block', [
				'id'    => $block_id,
				'error' => $result->get_error_message(),
			]);
			return false;
		}

		$this->logger->info( 'Reusable block updated', [
			'id' => $block_id,
		]);

		return true;
	}

	/**
	 * Delete reusable block
	 *
	 * @param int  $block_id Block ID.
	 * @param bool $force    Force delete (bypass trash).
	 * @return bool Success.
	 */
	public function delete_reusable_block( int $block_id, bool $force = false ): bool {
		$result = wp_delete_post( $block_id, $force );

		if ( ! $result ) {
			$this->logger->error( 'Failed to delete reusable block', [
				'id' => $block_id,
			]);
			return false;
		}

		$this->logger->info( 'Reusable block deleted', [
			'id'    => $block_id,
			'force' => $force,
		]);

		return true;
	}

	/**
	 * Get all reusable blocks
	 *
	 * @param array $args Query args.
	 * @return array Blocks.
	 */
	public function get_reusable_blocks( array $args = [] ): array {
		$defaults = [
			'post_type'      => 'wp_block',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		];

		$args = wp_parse_args( $args, $defaults );
		$query = new WP_Query( $args );

		$blocks = [];
		foreach ( $query->posts as $post ) {
			$blocks[] = [
				'id'          => $post->ID,
				'title'       => $post->post_title,
				'content'     => $post->post_content,
				'description' => get_post_meta( $post->ID, 'description', true ),
				'keywords'    => get_post_meta( $post->ID, 'keywords', true ),
				'modified'    => $post->post_modified,
			];
		}

		return $blocks;
	}

	/**
	 * Get reusable block by ID
	 *
	 * @param int $block_id Block ID.
	 * @return array|null Block data or null.
	 */
	public function get_reusable_block( int $block_id ): ?array {
		$post = get_post( $block_id );

		if ( ! $post || $post->post_type !== 'wp_block' ) {
			return null;
		}

		return [
			'id'          => $post->ID,
			'title'       => $post->post_title,
			'content'     => $post->post_content,
			'description' => get_post_meta( $post->ID, 'description', true ),
			'keywords'    => get_post_meta( $post->ID, 'keywords', true ),
			'modified'    => $post->post_modified,
		];
	}

	/**
	 * Create reusable block from components
	 *
	 * @param array  $components Universal components.
	 * @param string $title      Block title.
	 * @param array  $options    Additional options.
	 * @return int|WP_Error Block ID or error.
	 */
	public function create_from_components( array $components, string $title, array $options = [] ) {
		// Convert components to Gutenberg blocks
		require_once __DIR__ . '/class-gutenberg-converter.php';
		$converter = new WPBC_Gutenberg_Converter();

		$blocks_html = '';
		foreach ( $components as $component ) {
			$blocks_html .= $converter->convert( $component );
		}

		return $this->create_reusable_block( $title, $blocks_html, $options );
	}

	/**
	 * Export reusable block to JSON
	 *
	 * @param int $block_id Block ID.
	 * @return string|false JSON or false.
	 */
	public function export_block( int $block_id ) {
		$block = $this->get_reusable_block( $block_id );

		if ( ! $block ) {
			return false;
		}

		return wp_json_encode( $block, JSON_PRETTY_PRINT );
	}

	/**
	 * Import reusable block from JSON
	 *
	 * @param string $json Block JSON.
	 * @return int|false Block ID or false.
	 */
	public function import_block( string $json ) {
		$block = json_decode( $json, true );

		if ( ! $block || empty( $block['title'] ) ) {
			return false;
		}

		$result = $this->create_reusable_block(
			$block['title'],
			$block['content'] ?? '',
			[
				'description' => $block['description'] ?? '',
				'keywords'    => $block['keywords'] ?? [],
			]
		);

		return is_wp_error( $result ) ? false : $result;
	}

	/**
	 * Duplicate reusable block
	 *
	 * @param int    $block_id Block ID to duplicate.
	 * @param string $new_title New title (optional).
	 * @return int|WP_Error New block ID or error.
	 */
	public function duplicate_block( int $block_id, string $new_title = '' ) {
		$block = $this->get_reusable_block( $block_id );

		if ( ! $block ) {
			return new WP_Error( 'block_not_found', 'Reusable block not found' );
		}

		$title = $new_title ?: $block['title'] . ' (Copy)';

		return $this->create_reusable_block(
			$title,
			$block['content'],
			[
				'description' => $block['description'],
				'keywords'    => $block['keywords'],
			]
		);
	}

	/**
	 * Convert reusable block to pattern
	 *
	 * @param int $block_id Block ID.
	 * @return array Pattern configuration.
	 */
	public function convert_to_pattern( int $block_id ): ?array {
		$block = $this->get_reusable_block( $block_id );

		if ( ! $block ) {
			return null;
		}

		return [
			'title'       => $block['title'],
			'content'     => $block['content'],
			'description' => $block['description'],
			'keywords'    => is_array( $block['keywords'] ) ? $block['keywords'] : [],
		];
	}

	/**
	 * Find usages of reusable block in posts
	 *
	 * @param int $block_id Block ID.
	 * @return array Posts using this block.
	 */
	public function find_usages( int $block_id ): array {
		global $wpdb;

		// Search for block references in post content
		$pattern = '<!-- wp:block {"ref":' . $block_id . '}';

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_title, post_type
				 FROM {$wpdb->posts}
				 WHERE post_content LIKE %s
				 AND post_status = 'publish'",
				'%' . $wpdb->esc_like( $pattern ) . '%'
			)
		);

		$usages = [];
		foreach ( $results as $row ) {
			$usages[] = [
				'id'    => $row->ID,
				'title' => $row->post_title,
				'type'  => $row->post_type,
				'url'   => get_permalink( $row->ID ),
			];
		}

		return $usages;
	}

	/**
	 * Get block statistics
	 *
	 * @return array Statistics.
	 */
	public function get_stats(): array {
		$blocks = $this->get_reusable_blocks();

		$total_usages = 0;
		foreach ( $blocks as $block ) {
			$usages = $this->find_usages( $block['id'] );
			$total_usages += count( $usages );
		}

		return [
			'total_blocks' => count( $blocks ),
			'total_usages' => $total_usages,
		];
	}

	/**
	 * Search reusable blocks
	 *
	 * @param string $keyword Search keyword.
	 * @return array Matching blocks.
	 */
	public function search_blocks( string $keyword ): array {
		$all_blocks = $this->get_reusable_blocks();
		$matches = [];

		$keyword = strtolower( $keyword );

		foreach ( $all_blocks as $block ) {
			// Search in title
			if ( stripos( $block['title'], $keyword ) !== false ) {
				$matches[] = $block;
				continue;
			}

			// Search in description
			if ( ! empty( $block['description'] ) && stripos( $block['description'], $keyword ) !== false ) {
				$matches[] = $block;
				continue;
			}

			// Search in content
			if ( stripos( $block['content'], $keyword ) !== false ) {
				$matches[] = $block;
			}
		}

		return $matches;
	}

	/**
	 * Batch import reusable blocks from JSON array
	 *
	 * @param array $blocks Array of block data.
	 * @return array Imported block IDs.
	 */
	public function batch_import( array $blocks ): array {
		$imported = [];

		foreach ( $blocks as $block_data ) {
			if ( empty( $block_data['title'] ) ) {
				continue;
			}

			$result = $this->create_reusable_block(
				$block_data['title'],
				$block_data['content'] ?? '',
				[
					'description' => $block_data['description'] ?? '',
					'keywords'    => $block_data['keywords'] ?? [],
				]
			);

			if ( ! is_wp_error( $result ) ) {
				$imported[] = $result;
			}
		}

		$this->logger->info( 'Batch import completed', [
			'imported' => count( $imported ),
			'total'    => count( $blocks ),
		]);

		return $imported;
	}

	/**
	 * Batch export reusable blocks
	 *
	 * @param array $block_ids Block IDs to export (empty = all).
	 * @return string JSON array.
	 */
	public function batch_export( array $block_ids = [] ): string {
		if ( empty( $block_ids ) ) {
			$blocks = $this->get_reusable_blocks();
		} else {
			$blocks = [];
			foreach ( $block_ids as $id ) {
				$block = $this->get_reusable_block( $id );
				if ( $block ) {
					$blocks[] = $block;
				}
			}
		}

		return wp_json_encode( $blocks, JSON_PRETTY_PRINT );
	}
}
