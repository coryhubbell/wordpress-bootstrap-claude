<?php
/**
 * WPBC CLI Command Handler
 *
 * Routes and executes CLI commands for the Translation Bridge
 *
 * @package    WordPress_Bootstrap_Claude
 * @subpackage CLI
 * @version    3.2.1
 */

class WPBC_CLI {

	/**
	 * Command line arguments
	 *
	 * @var array
	 */
	private array $args;

	/**
	 * Parsed command
	 *
	 * @var string
	 */
	private string $command = 'help';

	/**
	 * Command options
	 *
	 * @var array
	 */
	private array $options = array();

	/**
	 * Command parameters
	 *
	 * @var array
	 */
	private array $params = array();

	/**
	 * Supported frameworks
	 *
	 * Uses WPBC_Config if available, otherwise falls back to local array.
	 *
	 * @var array
	 */
	private array $frameworks = array(
		'bootstrap'      => 'Bootstrap 5.3.3',
		'divi'           => 'DIVI Builder',
		'elementor'      => 'Elementor',
		'avada'          => 'Avada Fusion Builder',
		'bricks'         => 'Bricks Builder',
		'wpbakery'       => 'WPBakery Page Builder',
		'beaver-builder' => 'Beaver Builder',
		'gutenberg'      => 'Gutenberg Block Editor',
		'oxygen'         => 'Oxygen Builder',
		'claude'         => 'Claude AI-Optimized',
	);

	/**
	 * Logger instance
	 *
	 * @var WPBC_Logger
	 */
	private WPBC_Logger $logger;

	/**
	 * File handler instance
	 *
	 * @var WPBC_File_Handler
	 */
	private WPBC_File_Handler $file_handler;

	/**
	 * Constructor
	 *
	 * @param array $args Command line arguments.
	 */
	public function __construct( array $args ) {
		$this->args         = $args;
		$this->logger       = new WPBC_Logger();
		$this->file_handler = new WPBC_File_Handler();
		$this->parse_arguments();
	}

	/**
	 * Validate that a framework is supported
	 *
	 * @param string $framework   Framework identifier.
	 * @param string $param_name  Parameter name for error message.
	 * @return bool True if valid, false and outputs error if not.
	 */
	private function validate_framework( string $framework, string $param_name = 'framework' ): bool {
		if ( isset( $this->frameworks[ $framework ] ) ) {
			return true;
		}

		$this->error( "Unknown {$param_name} framework: {$framework}" );
		$this->list_frameworks();
		return false;
	}

	/**
	 * Validate that an input file exists
	 *
	 * @param string $file_path Path to the input file.
	 * @return bool True if exists, false and outputs error if not.
	 */
	private function validate_input_file( string $file_path ): bool {
		if ( file_exists( $file_path ) ) {
			return true;
		}

		$this->error( "Input file not found: {$file_path}" );
		return false;
	}

	/**
	 * Get the current command name
	 *
	 * @return string The current command.
	 */
	public function get_command(): string {
		return $this->command;
	}

	/**
	 * Get parsed parameters
	 *
	 * @return array The parameters array.
	 */
	public function get_params(): array {
		return $this->params;
	}

	/**
	 * Get parsed options
	 *
	 * @return array The options array.
	 */
	public function get_options(): array {
		return $this->options;
	}

	/**
	 * Parse command line arguments
	 *
	 * @return void
	 */
	private function parse_arguments(): void {
		$positional = array();
		$i          = 0;
		$count      = count( $this->args );

		while ( $i < $count ) {
			$arg = $this->args[ $i ];

			// Check if it's an option.
			if ( 0 === strpos( $arg, '--' ) ) {
				// Long option (--option or --option=value).
				if ( false !== strpos( $arg, '=' ) ) {
					list( $key, $value )   = explode( '=', substr( $arg, 2 ), 2 );
					$this->options[ $key ] = $value;
				} else {
					$key = substr( $arg, 2 );
					// Check if next arg is a value or another option.
					if ( $i + 1 < $count && 0 !== strpos( $this->args[ $i + 1 ], '-' ) ) {
						$this->options[ $key ] = $this->args[ $i + 1 ];
						$i++;
					} else {
						$this->options[ $key ] = true;
					}
				}
			} elseif ( 0 === strpos( $arg, '-' ) && 2 === strlen( $arg ) ) {
				// Short option (-o or -o value).
				$key = substr( $arg, 1 );
				if ( $i + 1 < $count && 0 !== strpos( $this->args[ $i + 1 ], '-' ) ) {
					$this->options[ $key ] = $this->args[ $i + 1 ];
					$i++;
				} else {
					$this->options[ $key ] = true;
				}
			} else {
				// Positional argument.
				$positional[] = $arg;
			}

			$i++;
		}

		// First positional is the command.
		$this->command = ! empty( $positional ) ? array_shift( $positional ) : 'help';
		$this->params  = $positional;
	}

	/**
	 * Execute the CLI command
	 *
	 * @return int Exit code (0 = success, non-zero = error).
	 */
	public function execute(): int {
		// Handle global options first.
		if ( $this->has_option( 'version', 'v' ) ) {
			return $this->show_version();
		}

		if ( $this->has_option( 'help', 'h' ) || 'help' === $this->command ) {
			return $this->show_help();
		}

		// Route to command handler.
		$method = 'command_' . str_replace( '-', '_', $this->command );

		if ( method_exists( $this, $method ) ) {
			return $this->$method();
		}

		$this->error( "Unknown command: {$this->command}" );
		$this->info( "Run 'wpbc help' to see available commands." );
		return 1;
	}

	/**
	 * Command: translate
	 *
	 * Translate from one framework to another.
	 * Usage: wpbc translate <source-framework> <target-framework> <input-file> [options]
	 *
	 * @return int Exit code.
	 */
	private function command_translate(): int {
		if ( count( $this->params ) < 3 ) {
			$this->error( "Insufficient arguments for 'translate' command" );
			$this->info( 'Usage: wpbc translate <source-framework> <target-framework> <input-file> [options]' );
			$this->info( 'Example: wpbc translate bootstrap divi hero.html' );
			return 1;
		}

		$source     = strtolower( $this->params[0] );
		$target     = strtolower( $this->params[1] );
		$input_file = $this->params[2];

		// Validate frameworks using helper.
		if ( ! $this->validate_framework( $source, 'source' ) ) {
			return 1;
		}

		if ( ! $this->validate_framework( $target, 'target' ) ) {
			return 1;
		}

		// Validate input file using helper.
		if ( ! $this->validate_input_file( $input_file ) ) {
			return 1;
		}

        // Determine output file
        $output_file = $this->get_option('output', 'o');
        if (!$output_file) {
            $output_file = $this->file_handler->generate_output_filename($input_file, $source, $target);
        }

        // Check dry run
        $dry_run = $this->has_option('dry-run', 'n');

        try {
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("  Translation Bridge™ - Framework Translation");
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("Source:     {$this->frameworks[$source]} ({$source})");
            $this->info("Target:     {$this->frameworks[$target]} ({$target})");
            $this->info("Input:      {$input_file}");
            $this->info("Output:     {$output_file}");
            if ($dry_run) {
                $this->warning("Mode:       DRY RUN (no files will be written)");
            }
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            echo PHP_EOL;

            // Read input file
            $this->info("📖 Reading input file...");
            $input_content = $this->file_handler->read_file($input_file, $source);

            // Initialize Translation Bridge
            $this->info("🔄 Initializing Translation Bridge...");
            require_once WPBC_TRANSLATION_BRIDGE . '/core/class-translator.php';
            require_once WPBC_TRANSLATION_BRIDGE . '/core/class-parser-factory.php';
            require_once WPBC_TRANSLATION_BRIDGE . '/core/class-converter-factory.php';

            try {
                $translator = new \WPBC\TranslationBridge\Core\WPBC_Translator();
            } catch (\Exception $e) {
                $this->error("Failed to initialize Translation Bridge: " . $e->getMessage());
                return 1;
            }

            if (!$translator || !is_object($translator)) {
                $this->error("Failed to create translator instance");
                return 1;
            }

            // Perform translation
            $this->info("⚙️  Translating {$source} → {$target}...");
            $start_time = microtime(true);

            $result = $translator->translate($input_content, $source, $target);

            $elapsed = round(microtime(true) - $start_time, 2);

            if (!$result) {
                $this->error("Translation failed");
                return 1;
            }

            $this->success("✓ Translation completed in {$elapsed}s");

            // Get statistics
            $stats = $translator->get_stats();
            if (!empty($stats)) {
                echo PHP_EOL;
                $this->info("📊 Translation Statistics:");
                $this->info("   Components parsed:    " . ($stats['components_parsed'] ?? 'N/A'));
                $this->info("   Components converted: " . ($stats['components_converted'] ?? 'N/A'));
                $this->info("   Warnings:            " . ($stats['warnings'] ?? 0));
            }

            // Write output or display
            if (!$dry_run) {
                echo PHP_EOL;
                $this->info("💾 Writing output file...");
                $this->file_handler->write_file($output_file, $result, $target);
                $this->success("✓ Output saved to: {$output_file}");

                // Show file size
                $size = filesize($output_file);
                $size_formatted = $this->format_bytes($size);
                $this->info("   File size: {$size_formatted}");
            } else {
                echo PHP_EOL;
                $this->warning("DRY RUN - Output not written");
                $this->info("Preview (first 500 characters):");
                echo PHP_EOL;
                echo $this->dim(substr($result, 0, 500));
                if (strlen($result) > 500) {
                    echo $this->dim("...");
                }
                echo PHP_EOL;
            }

            // Claude AI instructions if target is claude
            if ($target === 'claude') {
                echo PHP_EOL;
                $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
                $this->info("  🤖 Claude AI-Optimized HTML Generated");
                $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
                $this->info("This HTML is optimized for Claude AI editing.");
                $this->info("All editable elements have data-claude-editable attributes.");
                echo PHP_EOL;
                $this->info("💡 Next steps with Claude Code CLI:");
                $this->info("   1. Open the output file in your editor");
                $this->info("   2. Use natural language commands like:");
                $this->info("      • \"Change the button text to 'Get Started'\"");
                $this->info("      • \"Make the heading larger and blue\"");
                $this->info("      • \"Add a newsletter signup form\"");
                $this->info("   3. Convert back to {$source}:");
                $this->info("      wpbc translate claude {$source} {$output_file}");
                $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            }

            echo PHP_EOL;
            $this->success("🎉 Translation complete!");
            return 0;

        } catch (Exception $e) {
            echo PHP_EOL;
            $this->error("Translation failed: " . $e->getMessage());
            if ($this->has_option('debug', 'd')) {
                echo PHP_EOL;
                $this->dim("Stack trace:");
                $this->dim($e->getTraceAsString());
            }
            return 1;
        }
    }

	/**
	 * Command: translate-all
	 *
	 * Translate to all frameworks.
	 * Usage: wpbc translate-all <source-framework> <input-file> [options]
	 *
	 * @return int Exit code.
	 */
	private function command_translate_all(): int {
		if ( count( $this->params ) < 2 ) {
			$this->error( "Insufficient arguments for 'translate-all' command" );
			$this->info( 'Usage: wpbc translate-all <source-framework> <input-file> [options]' );
			$this->info( 'Example: wpbc translate-all bootstrap hero.html' );
			return 1;
		}

		$source     = strtolower( $this->params[0] );
		$input_file = $this->params[1];

		// Validate framework using helper.
		if ( ! $this->validate_framework( $source, 'source' ) ) {
			return 1;
		}

		// Validate input file using helper.
		if ( ! $this->validate_input_file( $input_file ) ) {
			return 1;
		}

        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("  Translation Bridge™ - Translate to All Frameworks");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("Source:     {$this->frameworks[$source]} ({$source})");
        $this->info("Input:      {$input_file}");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        echo PHP_EOL;

        $output_dir = $this->get_option('output-dir', 'd');
        if (!$output_dir) {
            $output_dir = dirname($input_file) . '/translations';
        }

        // Create output directory
        if (!is_dir($output_dir)) {
            mkdir($output_dir, 0755, true);
            $this->info("📁 Created output directory: {$output_dir}");
        }

        $total = 0;
        $successful = 0;
        $failed = 0;
        $start_time = microtime(true);
        $target_count = count($this->frameworks) - 1; // Exclude source framework

        // Translate to each framework
        foreach ($this->frameworks as $target => $name) {
            if ($target === $source) {
                continue; // Skip same framework
            }

            $total++;
            echo PHP_EOL;
            $this->info("[{$total}/{$target_count}] Translating to {$name}...");

            // Generate output filename
            $basename = pathinfo($input_file, PATHINFO_FILENAME);
            $output_file = $output_dir . '/' . $basename . '-' . $target . '.' . $this->file_handler->get_extension($target);

            try {
                // Read input
                $input_content = $this->file_handler->read_file($input_file, $source);

                // Translate
                require_once WPBC_TRANSLATION_BRIDGE . '/core/class-translator.php';
                $translator = new \WPBC\TranslationBridge\Core\WPBC_Translator();
                $result = $translator->translate($input_content, $source, $target);

                if ($result) {
                    // Write output
                    $this->file_handler->write_file($output_file, $result, $target);
                    $this->success("   ✓ {$name}: {$output_file}");
                    $successful++;
                } else {
                    $this->error("   ✗ {$name}: Translation failed");
                    $failed++;
                }
            } catch (Exception $e) {
                $this->error("   ✗ {$name}: " . $e->getMessage());
                $failed++;
            }
        }

        $elapsed = round(microtime(true) - $start_time, 2);

        echo PHP_EOL;
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("📊 Batch Translation Summary");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("Total:      {$total} translations");
        $this->success("Successful: {$successful}");
        if ($failed > 0) {
            $this->error("Failed:     {$failed}");
        }
        $this->info("Time:       {$elapsed}s");
        $this->info("Output:     {$output_dir}");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        echo PHP_EOL;

        return $failed > 0 ? 1 : 0;
    }

    /**
     * Command: list-frameworks
     *
     * List all supported frameworks
     */
    private function command_list_frameworks()
    {
        $count = count($this->frameworks);
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("  Supported Frameworks ({$count} Total)");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        echo PHP_EOL;

        foreach ($this->frameworks as $key => $name) {
            $this->info("  {$key}");
            $this->dim("    {$name}");
        }

        echo PHP_EOL;
        $pairs = $count * ($count - 1);
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("Translation Pairs: {$pairs} (any framework to any other)");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        echo PHP_EOL;

        return 0;
    }

	/**
	 * Command: validate
	 *
	 * Validate a framework file.
	 *
	 * @return int Exit code.
	 */
	private function command_validate(): int {
		if ( count( $this->params ) < 2 ) {
			$this->error( "Insufficient arguments for 'validate' command" );
			$this->info( 'Usage: wpbc validate <framework> <input-file>' );
			$this->info( 'Example: wpbc validate bootstrap hero.html' );
			return 1;
		}

		$framework  = strtolower( $this->params[0] );
		$input_file = $this->params[1];

		// Validate framework using helper.
		if ( ! $this->validate_framework( $framework ) ) {
			return 1;
		}

		// Validate input file using helper.
		if ( ! $this->validate_input_file( $input_file ) ) {
			return 1;
		}

        $this->info("🔍 Validating {$this->frameworks[$framework]} file...");
        $this->info("File: {$input_file}");
        echo PHP_EOL;

        try {
            // Read and parse
            $input_content = $this->file_handler->read_file($input_file, $framework);

            require_once WPBC_TRANSLATION_BRIDGE . '/core/class-parser-factory.php';
            $parser = \WPBC\TranslationBridge\Core\WPBC_Parser_Factory::create($framework);
            $components = $parser->parse($input_content);

            if (empty($components)) {
                $this->warning("⚠️  No components found in file");
                return 1;
            }

            $this->success("✓ File is valid");
            $this->info("Components found: " . count($components));

            // Show component breakdown
            if ($this->has_option('verbose', 'v')) {
                echo PHP_EOL;
                $this->info("Component Breakdown:");
                $types = [];
                foreach ($components as $component) {
                    $type = $component->type ?? 'unknown';
                    $types[$type] = ($types[$type] ?? 0) + 1;
                }
                foreach ($types as $type => $count) {
                    $this->info("  {$type}: {$count}");
                }
            }

            echo PHP_EOL;
            return 0;

        } catch (Exception $e) {
            $this->error("✗ Validation failed: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Show version information
     */
    private function show_version()
    {
        $framework_count = count($this->frameworks);
        $translation_pairs = $framework_count * ($framework_count - 1);

        echo $this->bold("WPBC - WordPress Bootstrap Claude") . PHP_EOL;
        echo "Version: " . WPBC_VERSION . PHP_EOL;
        echo "Translation Bridge™ - Universal Framework Translator" . PHP_EOL;
        echo PHP_EOL;
        echo "Supported Frameworks: {$framework_count}" . PHP_EOL;
        echo "Translation Pairs: {$translation_pairs}" . PHP_EOL;
        echo PHP_EOL;
        return 0;
    }

    /**
     * Show help information
     */
    private function show_help()
    {
        $command = !empty($this->params) ? $this->params[0] : null;

        if ($command) {
            return $this->show_command_help($command);
        }

        echo $this->bold("WPBC - WordPress Bootstrap Claude CLI") . PHP_EOL;
        echo "Translation Bridge™ - Universal Framework Translator" . PHP_EOL;
        echo PHP_EOL;
        echo $this->bold("USAGE:") . PHP_EOL;
        echo "  wpbc <command> [arguments] [options]" . PHP_EOL;
        echo PHP_EOL;
        echo $this->bold("COMMANDS:") . PHP_EOL;
        echo "  " . $this->bold("translate") . " <source> <target> <file>" . PHP_EOL;
        echo "    Translate from one framework to another" . PHP_EOL;
        echo PHP_EOL;
        echo "  " . $this->bold("translate-all") . " <source> <file>" . PHP_EOL;
        echo "    Translate to all frameworks (generates 9 files)" . PHP_EOL;
        echo PHP_EOL;
        echo "  " . $this->bold("list-frameworks") . PHP_EOL;
        echo "    List all supported frameworks" . PHP_EOL;
        echo PHP_EOL;
        echo "  " . $this->bold("validate") . " <framework> <file>" . PHP_EOL;
        echo "    Validate a framework file" . PHP_EOL;
        echo PHP_EOL;
        echo "  " . $this->bold("help") . " [command]" . PHP_EOL;
        echo "    Show help for a specific command" . PHP_EOL;
        echo PHP_EOL;
        echo $this->bold("GLOBAL OPTIONS:") . PHP_EOL;
        echo "  -h, --help       Show help information" . PHP_EOL;
        echo "  -v, --version    Show version information" . PHP_EOL;
        echo "  -d, --debug      Enable debug mode" . PHP_EOL;
        echo "  -q, --quiet      Suppress non-error output" . PHP_EOL;
        echo PHP_EOL;
        echo $this->bold("EXAMPLES:") . PHP_EOL;
        echo "  # Translate Bootstrap to DIVI" . PHP_EOL;
        echo "  wpbc translate bootstrap divi hero.html" . PHP_EOL;
        echo PHP_EOL;
        echo "  # Translate to all frameworks" . PHP_EOL;
        echo "  wpbc translate-all bootstrap hero.html" . PHP_EOL;
        echo PHP_EOL;
        echo "  # Convert to Claude AI-optimized HTML" . PHP_EOL;
        echo "  wpbc translate elementor claude page.json" . PHP_EOL;
        echo PHP_EOL;
        echo "  # Validate a file" . PHP_EOL;
        echo "  wpbc validate bootstrap hero.html" . PHP_EOL;
        echo PHP_EOL;
        echo "For more information: wpbc help <command>" . PHP_EOL;
        echo PHP_EOL;

        return 0;
    }

    /**
     * Show help for a specific command
     *
     * @param string $command Command name
     */
    private function show_command_help($command)
    {
        // Command-specific help would go here
        $this->info("Help for command: {$command}");
        $this->info("(Detailed help coming soon)");
        return 0;
    }

    /**
     * List available frameworks
     */
    private function list_frameworks()
    {
        $this->info("Available frameworks:");
        foreach ($this->frameworks as $key => $name) {
            $this->info("  - {$key} ({$name})");
        }
    }

    /**
     * Check if an option exists
     *
     * @param string $long  Long option name
     * @param string $short Short option name
     * @return bool
     */
    private function has_option($long, $short = null)
    {
        return isset($this->options[$long]) || ($short && isset($this->options[$short]));
    }

    /**
     * Get option value
     *
     * @param string $long  Long option name
     * @param string $short Short option name
     * @return mixed Option value or null
     */
    private function get_option($long, $short = null)
    {
        if (isset($this->options[$long])) {
            return $this->options[$long];
        }
        if ($short && isset($this->options[$short])) {
            return $this->options[$short];
        }
        return null;
    }

    /**
     * Format bytes to human readable
     *
     * @param int $bytes Bytes
     * @return string Formatted string
     */
    private function format_bytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    // Output formatting methods

    private function success($message)
    {
        if (!$this->has_option('quiet', 'q')) {
            echo "\033[32m{$message}\033[0m" . PHP_EOL;
        }
    }

    private function error($message)
    {
        fwrite(STDERR, "\033[31m{$message}\033[0m" . PHP_EOL);
    }

    private function warning($message)
    {
        if (!$this->has_option('quiet', 'q')) {
            echo "\033[33m{$message}\033[0m" . PHP_EOL;
        }
    }

    private function info($message)
    {
        if (!$this->has_option('quiet', 'q')) {
            echo $message . PHP_EOL;
        }
    }

    private function dim($message)
    {
        if (!$this->has_option('quiet', 'q')) {
            return "\033[2m{$message}\033[0m";
        }
        return $message;
    }

    private function bold($message)
    {
        return "\033[1m{$message}\033[0m";
    }
}
