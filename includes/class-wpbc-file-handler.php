<?php
/**
 * WPBC File Handler
 *
 * Handles file I/O operations for various framework formats
 *
 * @package    WordPress_Bootstrap_Claude
 * @subpackage CLI
 * @version    3.2.1
 */

class WPBC_File_Handler {

	/**
	 * Framework file extensions
	 *
	 * @var array
	 */
	private array $extensions = array(
		'bootstrap'      => 'html',
		'divi'           => 'txt',  // DIVI uses shortcodes in text files.
		'elementor'      => 'json',
		'avada'          => 'html',
		'bricks'         => 'json',
		'wpbakery'       => 'txt',  // WPBakery uses shortcodes.
		'beaver-builder' => 'txt',  // Beaver Builder uses serialized PHP.
		'gutenberg'      => 'html', // Gutenberg uses HTML comments.
		'oxygen'         => 'json', // Oxygen uses JSON.
		'claude'         => 'html',
	);

	/**
	 * Read a file with framework-specific handling
	 *
	 * @param string $file_path Path to input file.
	 * @param string $framework Framework name.
	 * @return string|array File contents.
	 * @throws Exception If file cannot be read.
	 */
	public function read_file( string $file_path, string $framework ) {
		if ( ! file_exists( $file_path ) ) {
			throw new Exception( "File not found: {$file_path}" );
		}

		if ( ! is_readable( $file_path ) ) {
			throw new Exception( "File is not readable: {$file_path}" );
		}

		$content = file_get_contents( $file_path );

		if ( false === $content ) {
			throw new Exception( "Failed to read file: {$file_path}" );
		}

		// Framework-specific parsing.
		switch ( $framework ) {
			case 'elementor':
			case 'bricks':
			case 'oxygen':
				// JSON-based frameworks.
				$decoded = json_decode( $content, true );
				if ( JSON_ERROR_NONE !== json_last_error() ) {
					throw new Exception( 'Invalid JSON in file: ' . json_last_error_msg() );
				}
				return $decoded;

			default:
				// Text/HTML-based frameworks.
				return $content;
		}
	}

	/**
	 * Write a file with framework-specific handling
	 *
	 * @param string       $file_path Path to output file.
	 * @param string|array $content   Content to write.
	 * @param string       $framework Framework name.
	 * @return void
	 * @throws Exception If file cannot be written.
	 */
	public function write_file( string $file_path, $content, string $framework ): void {
		// Create directory if it doesn't exist.
		$dir = dirname( $file_path );
		if ( ! is_dir( $dir ) ) {
			if ( ! mkdir( $dir, 0755, true ) ) {
				throw new Exception( "Failed to create directory: {$dir}" );
			}
		}

		// Framework-specific formatting.
		switch ( $framework ) {
			case 'elementor':
			case 'bricks':
			case 'oxygen':
				// JSON-based frameworks.
				if ( is_array( $content ) ) {
					$content = wp_json_encode( $content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
					if ( false === $content ) {
						throw new Exception( 'Failed to encode JSON: ' . json_last_error_msg() );
					}
				}
				break;

			default:
				// Text/HTML-based frameworks.
				if ( is_array( $content ) ) {
					$content = implode( PHP_EOL, $content );
				}
				break;
		}

		// Write file.
		$bytes = file_put_contents( $file_path, $content );

		if ( false === $bytes ) {
			throw new Exception( "Failed to write file: {$file_path}" );
		}

		// Set permissions.
		chmod( $file_path, 0644 );
	}

	/**
	 * Generate output filename based on input and frameworks
	 *
	 * @param string $input_file Input file path.
	 * @param string $source     Source framework.
	 * @param string $target     Target framework.
	 * @return string Output file path.
	 */
	public function generate_output_filename( string $input_file, string $source, string $target ): string {
		$dir       = dirname( $input_file );
		$filename  = pathinfo( $input_file, PATHINFO_FILENAME );
		$extension = $this->get_extension( $target );

		// Remove source framework suffix if present.
		$filename = preg_replace( '/-' . preg_quote( $source, '/' ) . '$/', '', $filename );

		// Add target framework suffix.
		$output_filename = $filename . '-' . $target . '.' . $extension;

		return $dir . '/' . $output_filename;
	}

	/**
	 * Get file extension for a framework
	 *
	 * @param string $framework Framework name.
	 * @return string File extension.
	 */
	public function get_extension( string $framework ): string {
		return $this->extensions[ $framework ] ?? 'html';
	}

	/**
	 * Detect framework from file content
	 *
	 * @param string $file_path File path.
	 * @return string|null Framework name or null if cannot detect.
	 */
	public function detect_framework( string $file_path ): ?string {
		if ( ! file_exists( $file_path ) ) {
			return null;
		}

		$content   = file_get_contents( $file_path );
		$extension = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );

		// Check by extension first.
		if ( 'json' === $extension ) {
			$data = json_decode( $content, true );
			if ( isset( $data['content'] ) ) {
				// Elementor format.
				return 'elementor';
			} elseif ( isset( $data['elements'] ) ) {
				// Bricks format.
				return 'bricks';
			}
		}

		// Check by content patterns.
		if ( preg_match( '/\[et_pb_/', $content ) ) {
			return 'divi';
		}

		if ( preg_match( '/\[vc_/', $content ) ) {
			return 'wpbakery';
		}

		if ( preg_match( '/\[fusion_/', $content ) ) {
			return 'avada';
		}

		if ( preg_match( '/data-claude-editable/', $content ) ) {
			return 'claude';
		}

		if ( preg_match( '/<div class="[^"]*\bcontainer\b[^"]*"/', $content ) ) {
			return 'bootstrap';
		}

		return null;
	}

	/**
	 * Validate file format for a framework
	 *
	 * @param string $file_path File path.
	 * @param string $framework Framework name.
	 * @return bool True if valid.
	 */
	public function validate_format( string $file_path, string $framework ): bool {
		try {
			$content = $this->read_file( $file_path, $framework );
			return ! empty( $content );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Create backup of a file
	 *
	 * @param string $file_path File to backup.
	 * @return string|false Backup file path or false on failure.
	 */
	public function backup_file( string $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			return false;
		}

		$backup_path = $file_path . '.backup-' . gmdate( 'Y-m-d-His' );

		if ( copy( $file_path, $backup_path ) ) {
			return $backup_path;
		}

		return false;
	}

	/**
	 * Get file information
	 *
	 * @param string $file_path File path.
	 * @return array File information.
	 */
	public function get_file_info( string $file_path ): array {
		if ( ! file_exists( $file_path ) ) {
			return array();
		}

		$info = array(
			'path'      => $file_path,
			'name'      => basename( $file_path ),
			'dir'       => dirname( $file_path ),
			'extension' => pathinfo( $file_path, PATHINFO_EXTENSION ),
			'size'      => filesize( $file_path ),
			'modified'  => filemtime( $file_path ),
			'readable'  => is_readable( $file_path ),
			'writable'  => is_writable( $file_path ),
		);

		// Try to detect framework.
		$info['framework'] = $this->detect_framework( $file_path );

		return $info;
	}

	/**
	 * List files in directory matching pattern
	 *
	 * @param string $directory Directory path.
	 * @param string $pattern   File pattern (e.g., "*.html").
	 * @return array List of file paths.
	 */
	public function list_files( string $directory, string $pattern = '*' ): array {
		if ( ! is_dir( $directory ) ) {
			return array();
		}

		$files = glob( $directory . '/' . $pattern );
		return $files ? $files : array();
	}

	/**
	 * Check if path is safe (no directory traversal)
	 *
	 * @param string $path File path.
	 * @return bool True if safe.
	 */
	public function is_safe_path( string $path ): bool {
		// Normalize the path to handle URL encoding and various traversal techniques.
		$path = urldecode( $path );

		// Check for various directory traversal patterns.
		if ( preg_match( '/\.\.|%2e%2e|%252e%252e/i', $path ) ) {
			return false;
		}

		// Check for null bytes.
		if ( strpos( $path, "\0" ) !== false ) {
			return false;
		}

		$real_path = realpath( $path );

		// Path doesn't exist yet (for new files) - check parent directory.
		if ( false === $real_path ) {
			$real_path = realpath( dirname( $path ) );
			if ( false === $real_path ) {
				return false;
			}
		}

		// Verify the resolved path is within the expected base directory.
		// This prevents symlink attacks and ensures files stay within project.
		$base_dir = realpath( WPBC_ROOT );
		if ( $base_dir && strpos( $real_path, $base_dir ) !== 0 ) {
			return false;
		}

		return true;
	}

	/**
	 * Sanitize filename
	 *
	 * @param string $filename Filename to sanitize.
	 * @return string Sanitized filename.
	 */
	public function sanitize_filename( string $filename ): string {
		// Remove any path components.
		$filename = basename( $filename );

		// Remove special characters.
		$filename = preg_replace( '/[^a-zA-Z0-9._-]/', '-', $filename );

		// Remove multiple dashes.
		$filename = preg_replace( '/-+/', '-', $filename );

		return $filename;
	}
}
