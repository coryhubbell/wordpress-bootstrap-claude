<?php
/**
 * WPBC REST API v2
 *
 * Provides REST API endpoints for Translation Bridge operations
 * Supports single translations, batch processing, validation, and more
 *
 * @package    WordPress_Bootstrap_Claude
 * @subpackage API
 * @version    3.2.0
 */

class WPBC_API_V2 {
    /**
     * API namespace
     *
     * @var string
     */
    private $namespace = 'wpbc/v2';

    /**
     * Supported frameworks
     *
     * @var array
     */
    private $frameworks = [
        'bootstrap',
        'divi',
        'elementor',
        'avada',
        'bricks',
        'wpbakery',
        'beaver-builder',
        'gutenberg',
        'oxygen',
        'claude',
    ];

    /**
     * Logger instance
     *
     * @var WPBC_Logger
     */
    private $logger;

    /**
     * Auth handler
     *
     * @var WPBC_Auth
     */
    private $auth;

    /**
     * Rate limiter
     *
     * @var WPBC_Rate_Limiter
     */
    private $rate_limiter;

    /**
     * Constructor
     */
    public function __construct() {
        $this->logger = new WPBC_Logger();
        $this->auth = new WPBC_Auth();
        $this->rate_limiter = new WPBC_Rate_Limiter();

        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Single translation endpoint
        register_rest_route($this->namespace, '/translate', [
            'methods'             => 'POST',
            'callback'            => [$this, 'translate'],
            'permission_callback' => [$this, 'check_permission'],
            'args'                => $this->get_translate_args(),
        ]);

        // Batch translation endpoint
        register_rest_route($this->namespace, '/batch-translate', [
            'methods'             => 'POST',
            'callback'            => [$this, 'batch_translate'],
            'permission_callback' => [$this, 'check_permission'],
            'args'                => $this->get_batch_translate_args(),
        ]);

        // Job status endpoint
        register_rest_route($this->namespace, '/job/(?P<job_id>[a-zA-Z0-9-]+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_job_status'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        // Validate content endpoint
        register_rest_route($this->namespace, '/validate', [
            'methods'             => 'POST',
            'callback'            => [$this, 'validate'],
            'permission_callback' => [$this, 'check_permission'],
            'args'                => $this->get_validate_args(),
        ]);

        // List frameworks endpoint
        register_rest_route($this->namespace, '/frameworks', [
            'methods'             => 'GET',
            'callback'            => [$this, 'list_frameworks'],
            'permission_callback' => '__return_true', // Public endpoint
        ]);

        // API status endpoint
        register_rest_route($this->namespace, '/status', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_status'],
            'permission_callback' => '__return_true', // Public endpoint
        ]);

        // API key management endpoints
        register_rest_route($this->namespace, '/api-keys', [
            'methods'             => 'GET',
            'callback'            => [$this, 'list_api_keys'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route($this->namespace, '/api-keys', [
            'methods'             => 'POST',
            'callback'            => [$this, 'create_api_key'],
            'permission_callback' => [$this, 'check_admin_permission'],
            'args'                => [
                'name' => [
                    'required'          => false,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'permissions' => [
                    'required' => false,
                    'type'     => 'array',
                    'default'  => ['read', 'write'],
                ],
                'tier' => [
                    'required' => false,
                    'type'     => 'string',
                    'enum'     => ['free', 'basic', 'premium', 'enterprise'],
                    'default'  => 'free',
                ],
            ],
        ]);

        register_rest_route($this->namespace, '/api-keys/(?P<key>[a-zA-Z0-9_]+)', [
            'methods'             => 'DELETE',
            'callback'            => [$this, 'revoke_api_key'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);
    }

    /**
     * Single translation endpoint
     *
     * POST /wp-json/wpbc/v2/translate
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function translate($request) {
        $source = $request->get_param('source');
        $target = $request->get_param('target');
        $content = $request->get_param('content');
        $options = $request->get_param('options') ?: [];

        try {
            // Initialize translator
            require_once WPBC_TRANSLATION_BRIDGE_DIR . '/core/class-translator.php';
            $translator = new Translator();

            // Perform translation
            $start_time = microtime(true);
            $result = $translator->translate($content, $source, $target);
            $elapsed_time = microtime(true) - $start_time;

            if (!$result) {
                return new WP_Error(
                    'translation_failed',
                    'Translation failed. Please check your input and try again.',
                    ['status' => 400]
                );
            }

            // Get statistics
            $stats = $translator->get_stats();

            // Log translation
            $this->logger->log_translation($source, $target, 'API', 'API', $elapsed_time, true);

            // Return response
            return new WP_REST_Response([
                'success'       => true,
                'source'        => $source,
                'target'        => $target,
                'result'        => $result,
                'elapsed_time'  => round($elapsed_time, 3),
                'stats'         => $stats,
                'timestamp'     => current_time('mysql'),
            ], 200);

        } catch (Exception $e) {
            $this->logger->error('API translation error', [
                'source' => $source,
                'target' => $target,
                'error'  => $e->getMessage(),
            ]);

            return new WP_Error(
                'translation_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Batch translation endpoint
     *
     * POST /wp-json/wpbc/v2/batch-translate
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function batch_translate($request) {
        $source = $request->get_param('source');
        $targets = $request->get_param('targets');
        $content = $request->get_param('content');
        $async = $request->get_param('async') ?: false;

        // Validate targets
        if (empty($targets) || !is_array($targets)) {
            return new WP_Error(
                'invalid_targets',
                'Targets must be a non-empty array of framework names.',
                ['status' => 400]
            );
        }

        // If async, create job and return immediately
        if ($async) {
            $job_id = $this->create_batch_job($source, $targets, $content);

            return new WP_REST_Response([
                'success' => true,
                'job_id'  => $job_id,
                'status'  => 'queued',
                'message' => 'Batch translation job created. Check status at /wp-json/wpbc/v2/job/' . $job_id,
            ], 202);
        }

        // Synchronous batch processing
        try {
            $results = [];
            $start_time = microtime(true);

            require_once WPBC_TRANSLATION_BRIDGE_DIR . '/core/class-translator.php';
            $translator = new Translator();

            foreach ($targets as $target) {
                if (!in_array($target, $this->frameworks)) {
                    $results[$target] = [
                        'success' => false,
                        'error'   => 'Unknown framework: ' . $target,
                    ];
                    continue;
                }

                try {
                    $result = $translator->translate($content, $source, $target);

                    if ($result) {
                        $results[$target] = [
                            'success' => true,
                            'result'  => $result,
                            'stats'   => $translator->get_stats(),
                        ];
                    } else {
                        $results[$target] = [
                            'success' => false,
                            'error'   => 'Translation failed',
                        ];
                    }
                } catch (Exception $e) {
                    $results[$target] = [
                        'success' => false,
                        'error'   => $e->getMessage(),
                    ];
                }
            }

            $elapsed_time = microtime(true) - $start_time;

            // Count successes/failures
            $successful = count(array_filter($results, function($r) {
                return $r['success'];
            }));
            $failed = count($results) - $successful;

            $this->logger->info('Batch translation completed', [
                'source'     => $source,
                'targets'    => count($targets),
                'successful' => $successful,
                'failed'     => $failed,
                'time'       => round($elapsed_time, 2),
            ]);

            return new WP_REST_Response([
                'success'      => true,
                'source'       => $source,
                'total'        => count($targets),
                'successful'   => $successful,
                'failed'       => $failed,
                'results'      => $results,
                'elapsed_time' => round($elapsed_time, 3),
                'timestamp'    => current_time('mysql'),
            ], 200);

        } catch (Exception $e) {
            return new WP_Error(
                'batch_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Get job status
     *
     * GET /wp-json/wpbc/v2/job/{job_id}
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_job_status($request) {
        $job_id = $request->get_param('job_id');

        $job = get_transient('wpbc_job_' . $job_id);

        if (!$job) {
            return new WP_Error(
                'job_not_found',
                'Job not found: ' . $job_id,
                ['status' => 404]
            );
        }

        return new WP_REST_Response($job, 200);
    }

    /**
     * Validate content endpoint
     *
     * POST /wp-json/wpbc/v2/validate
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function validate($request) {
        $framework = $request->get_param('framework');
        $content = $request->get_param('content');

        try {
            require_once WPBC_TRANSLATION_BRIDGE_DIR . '/core/class-parser-factory.php';

            $parser = Parser_Factory::create($framework);
            $components = $parser->parse($content);

            $is_valid = !empty($components);
            $component_count = is_array($components) ? count($components) : 0;

            // Get component types breakdown
            $types = [];
            if (is_array($components)) {
                foreach ($components as $component) {
                    $type = $component->type ?? 'unknown';
                    $types[$type] = ($types[$type] ?? 0) + 1;
                }
            }

            return new WP_REST_Response([
                'success'        => true,
                'valid'          => $is_valid,
                'framework'      => $framework,
                'component_count'=> $component_count,
                'component_types'=> $types,
                'timestamp'      => current_time('mysql'),
            ], 200);

        } catch (Exception $e) {
            return new WP_Error(
                'validation_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * List frameworks endpoint
     *
     * GET /wp-json/wpbc/v2/frameworks
     *
     * @return WP_REST_Response
     */
    public function list_frameworks() {
        $frameworks_info = [
            'bootstrap' => [
                'name'        => 'Bootstrap 5.3.3',
                'type'        => 'HTML/CSS',
                'extension'   => 'html',
                'description' => 'Clean HTML/CSS framework, perfect for Claude AI',
            ],
            'divi' => [
                'name'        => 'DIVI Builder',
                'type'        => 'Shortcodes',
                'extension'   => 'txt',
                'description' => 'Visual page builder with 100+ modules',
            ],
            'elementor' => [
                'name'        => 'Elementor',
                'type'        => 'JSON',
                'extension'   => 'json',
                'description' => 'Popular page builder with 90+ widgets',
            ],
            'avada' => [
                'name'        => 'Avada Fusion Builder',
                'type'        => 'HTML',
                'extension'   => 'html',
                'description' => 'Premium builder with 150+ elements',
            ],
            'bricks' => [
                'name'        => 'Bricks Builder',
                'type'        => 'JSON',
                'extension'   => 'json',
                'description' => 'Performance-focused builder',
            ],
            'wpbakery' => [
                'name'        => 'WPBakery Page Builder',
                'type'        => 'Shortcodes',
                'extension'   => 'txt',
                'description' => 'Visual Composer, 50+ elements',
            ],
            'beaver-builder' => [
                'name'        => 'Beaver Builder',
                'type'        => 'Serialized PHP',
                'extension'   => 'txt',
                'description' => 'Flexible page builder with 30+ modules',
            ],
            'gutenberg' => [
                'name'        => 'Gutenberg Block Editor',
                'type'        => 'HTML Comments',
                'extension'   => 'html',
                'description' => 'WordPress native block editor with 50+ core blocks',
            ],
            'oxygen' => [
                'name'        => 'Oxygen Builder',
                'type'        => 'JSON',
                'extension'   => 'json',
                'description' => 'Visual site builder with 30+ elements',
            ],
            'claude' => [
                'name'        => 'Claude AI-Optimized',
                'type'        => 'HTML',
                'extension'   => 'html',
                'description' => 'AI-native framework with data-claude-editable attributes',
            ],
        ];

        return new WP_REST_Response([
            'success'           => true,
            'total_frameworks'  => count($frameworks_info),
            'translation_pairs' => count($frameworks_info) * (count($frameworks_info) - 1),
            'frameworks'        => $frameworks_info,
        ], 200);
    }

    /**
     * Get API status
     *
     * GET /wp-json/wpbc/v2/status
     *
     * @return WP_REST_Response
     */
    public function get_status() {
        return new WP_REST_Response([
            'success'  => true,
            'version'  => '2.0',
            'status'   => 'operational',
            'features' => [
                'single_translation' => true,
                'batch_translation'  => true,
                'async_processing'   => true,
                'validation'         => true,
                'webhooks'           => true,
                'api_key_auth'       => true,
                'rate_limiting'      => true,
            ],
            'timestamp' => current_time('mysql'),
        ], 200);
    }

    /**
     * Check permission for API access
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function check_permission($request) {
        // Try API key authentication first
        $api_key = $this->auth->get_api_key_from_request($request);
        $key_data = null;

        if ($api_key) {
            $result = $this->auth->authenticate_request($request);

            if (is_wp_error($result)) {
                return $result;
            }

            $key_data = $result;

            // Check rate limit for API key
            $tier = $this->rate_limiter->get_tier_for_key($key_data);
            $identifier = 'key_' . $api_key;

            $limit_check = $this->rate_limiter->check_limit($identifier, $tier);

            if (!$limit_check['allowed']) {
                // Add rate limit headers
                $headers = $this->rate_limiter->get_headers($limit_check);

                return new WP_Error(
                    'wpbc_rate_limit_exceeded',
                    'Rate limit exceeded. Please retry after ' . $limit_check['retry_after'] . ' seconds.',
                    [
                        'status' => 429,
                        'headers' => $headers,
                    ]
                );
            }

            // Record request
            $this->rate_limiter->record_request($identifier, $tier);

            // Add rate limit headers to response
            add_filter('rest_post_dispatch', function($response) use ($limit_check) {
                $headers = $this->rate_limiter->get_headers($limit_check);
                foreach ($headers as $key => $value) {
                    $response->header($key, $value);
                }
                return $response;
            });

            return true;
        }

        // Fall back to WordPress user authentication
        if (!is_user_logged_in()) {
            return new WP_Error(
                'wpbc_auth_required',
                'Authentication required. Provide an API key or log in.',
                ['status' => 401]
            );
        }

        // Check capability
        if (!current_user_can('edit_posts')) {
            return new WP_Error(
                'wpbc_forbidden',
                'You do not have permission to use the Translation Bridge API.',
                ['status' => 403]
            );
        }

        // Rate limit for logged-in users
        $user_id = get_current_user_id();
        $identifier = 'user_' . $user_id;

        $limit_check = $this->rate_limiter->check_limit($identifier, 'basic');

        if (!$limit_check['allowed']) {
            $headers = $this->rate_limiter->get_headers($limit_check);

            return new WP_Error(
                'wpbc_rate_limit_exceeded',
                'Rate limit exceeded. Please retry after ' . $limit_check['retry_after'] . ' seconds.',
                [
                    'status' => 429,
                    'headers' => $headers,
                ]
            );
        }

        // Record request
        $this->rate_limiter->record_request($identifier, 'basic');

        return true;
    }

    /**
     * Create batch translation job
     *
     * @param string $source Source framework
     * @param array  $targets Target frameworks
     * @param string $content Content to translate
     * @return string Job ID
     */
    private function create_batch_job($source, $targets, $content) {
        $job_id = 'wpbc_' . uniqid();

        $job_data = [
            'job_id'     => $job_id,
            'status'     => 'queued',
            'source'     => $source,
            'targets'    => $targets,
            'content'    => $content,
            'progress'   => 0,
            'results'    => [],
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        // Store job (expires in 24 hours)
        set_transient('wpbc_job_' . $job_id, $job_data, DAY_IN_SECONDS);

        // Schedule processing
        wp_schedule_single_event(time(), 'wpbc_process_batch_job', [$job_id]);

        return $job_id;
    }

    /**
     * Get translate endpoint arguments
     *
     * @return array
     */
    private function get_translate_args() {
        return [
            'source' => [
                'required'          => true,
                'type'              => 'string',
                'enum'              => $this->frameworks,
                'description'       => 'Source framework name',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'target' => [
                'required'          => true,
                'type'              => 'string',
                'enum'              => $this->frameworks,
                'description'       => 'Target framework name',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'content' => [
                'required'    => true,
                'type'        => 'string',
                'description' => 'Content to translate',
            ],
            'options' => [
                'required'    => false,
                'type'        => 'object',
                'description' => 'Additional translation options',
            ],
        ];
    }

    /**
     * Get batch translate endpoint arguments
     *
     * @return array
     */
    private function get_batch_translate_args() {
        return [
            'source' => [
                'required'          => true,
                'type'              => 'string',
                'enum'              => $this->frameworks,
                'description'       => 'Source framework name',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'targets' => [
                'required'    => true,
                'type'        => 'array',
                'items'       => [
                    'type' => 'string',
                    'enum' => $this->frameworks,
                ],
                'description' => 'Array of target framework names',
            ],
            'content' => [
                'required'    => true,
                'type'        => 'string',
                'description' => 'Content to translate',
            ],
            'async' => [
                'required'    => false,
                'type'        => 'boolean',
                'default'     => false,
                'description' => 'Process asynchronously (returns job ID)',
            ],
        ];
    }

    /**
     * Get validate endpoint arguments
     *
     * @return array
     */
    private function get_validate_args() {
        return [
            'framework' => [
                'required'          => true,
                'type'              => 'string',
                'enum'              => $this->frameworks,
                'description'       => 'Framework to validate against',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'content' => [
                'required'    => true,
                'type'        => 'string',
                'description' => 'Content to validate',
            ],
        ];
    }

    /**
     * Check admin permission
     *
     * @return bool|WP_Error
     */
    public function check_admin_permission() {
        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'wpbc_forbidden',
                'You must be an administrator to access this endpoint.',
                ['status' => 403]
            );
        }

        return true;
    }

    /**
     * List API keys for current user
     *
     * GET /wp-json/wpbc/v2/api-keys
     *
     * @return WP_REST_Response
     */
    public function list_api_keys() {
        $user_id = get_current_user_id();
        $keys = $this->auth->get_user_api_keys($user_id);

        return new WP_REST_Response([
            'success' => true,
            'keys'    => $keys,
            'total'   => count($keys),
        ], 200);
    }

    /**
     * Create new API key
     *
     * POST /wp-json/wpbc/v2/api-keys
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function create_api_key($request) {
        $user_id = get_current_user_id();
        $name = $request->get_param('name') ?: 'API Key';
        $permissions = $request->get_param('permissions') ?: ['read', 'write'];
        $tier = $request->get_param('tier') ?: 'free';

        // Add tier to permissions array for storage
        $key_data = $this->auth->generate_api_key($user_id, $name, $permissions);
        $key_data['tier'] = $tier;

        // Store updated key data with tier
        $option_name = 'wpbc_api_keys';
        $all_keys = get_option($option_name, []);
        $all_keys[$key_data['key']] = $key_data;
        update_option($option_name, $all_keys);

        return new WP_REST_Response([
            'success'     => true,
            'message'     => 'API key created successfully. Store it securely - it won\'t be shown again.',
            'key'         => $key_data['key'],
            'name'        => $key_data['name'],
            'tier'        => $tier,
            'permissions' => $permissions,
            'created_at'  => $key_data['created_at'],
        ], 201);
    }

    /**
     * Revoke API key
     *
     * DELETE /wp-json/wpbc/v2/api-keys/{key}
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function revoke_api_key($request) {
        $key = $request->get_param('key');

        $success = $this->auth->revoke_api_key($key);

        if (!$success) {
            return new WP_Error(
                'key_not_found',
                'API key not found',
                ['status' => 404]
            );
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'API key revoked successfully',
        ], 200);
    }
}

// Initialize API
if (class_exists('WPBC_Logger')) {
    new WPBC_API_V2();
}
