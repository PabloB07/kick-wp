<?php
/**
 * Funcionalidad de administración unificada (Corregida)
 *
 * @link       https://blancocl.vercel.app
 * @since      1.2.0
 *
 * @package    Kick_Wp
 * @subpackage Kick_Wp/admin
 */

class Kick_Wp_Admin {
    
    private $plugin_name;
    private $version;
    private $api;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->api = new Kick_Wp_Api();
        
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_plugin_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
        
        // Enlace de configuración en plugins
        if (defined('KICK_WP_FILE')) {
            add_filter('plugin_action_links_' . plugin_basename(KICK_WP_FILE), array($this, 'add_settings_link'));
        }
        
        // Mensajes de admin
        add_action('admin_notices', array($this, 'show_admin_notices'));
    }

    public function enqueue_styles($hook) {
        if (strpos($hook, 'kick-wp') === false) {
            return;
        }
        
        wp_enqueue_style(
            $this->plugin_name . '-admin',
            plugin_dir_url(__FILE__) . 'css/kick-wp-admin.css',
            array(),
            $this->version,
            'all'
        );
        
        // Agregar estilos inline para el dashboard mejorado
        $custom_css = "
            .kick-wp-dashboard {
                display: grid;
                gap: 20px;
                margin-top: 20px;
            }
            
            .kick-wp-card {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 8px;
                padding: 20px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            
            .kick-wp-card h2 {
                margin-top: 0;
                color: #1d2327;
                border-bottom: 1px solid #e0e0e0;
                padding-bottom: 10px;
            }
            
            .kick-wp-status-indicator {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 8px 12px;
                border-radius: 4px;
                font-weight: 500;
            }
            
            .status-success {
                background: #d1e7dd;
                color: #0f5132;
            }
            
            .status-warning {
                background: #fff3cd;
                color: #664d03;
            }
            
            .status-error {
                background: #f8d7da;
                color: #721c24;
            }
            
            .kick-wp-oauth-section {
                border: 1px solid #e0e0e0;
                border-radius: 6px;
                padding: 20px;
                margin: 20px 0;
                background: #fafafa;
            }
            
            .kick-wp-oauth-button {
                background: #1a73e8;
                color: white;
                border: none;
                padding: 12px 24px;
                border-radius: 6px;
                font-size: 14px;
                font-weight: 500;
                cursor: pointer;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: background-color 0.2s;
            }
            
            .kick-wp-oauth-button:hover {
                background: #1557b0;
                color: white;
            }
            
            .kick-wp-oauth-button:before {
                content: '🔐';
                font-size: 16px;
            }
            
            .kick-wp-streams-preview {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin-top: 15px;
            }
            
            .kick-wp-stream-preview-card {
                border: 1px solid #e0e0e0;
                border-radius: 6px;
                overflow: hidden;
                transition: transform 0.2s, box-shadow 0.2s;
            }
            
            .kick-wp-stream-preview-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            }
            
            .kick-wp-stream-preview-card img {
                width: 100%;
                height: 120px;
                object-fit: cover;
            }
            
            .kick-wp-stream-preview-info {
                padding: 12px;
            }
            
            .kick-wp-stream-preview-info h4 {
                margin: 0 0 5px 0;
                font-size: 14px;
                color: #1d2327;
            }
            
            .kick-wp-stream-preview-info p {
                margin: 0;
                font-size: 12px;
                color: #646970;
            }
            
            .kick-wp-viewer-badge {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                background: #2271b1;
                color: white;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 11px;
                margin-top: 5px;
            }
        ";
        
        wp_add_inline_style($this->plugin_name . '-admin', $custom_css);
    }

    public function enqueue_scripts($hook) {
        if (strpos($hook, 'kick-wp') === false) {
            return;
        }
        
        wp_enqueue_script(
            $this->plugin_name . '-admin',
            plugin_dir_url(__FILE__) . 'js/kick-wp-admin.js',
            array('jquery'),
            $this->version,
            true
        );
    }

    public function add_plugin_admin_menu() {
        add_menu_page(
            __('Kick WP', 'kick-wp'),
            __('Kick WP', 'kick-wp'),
            'manage_options',
            'kick-wp',
            array($this, 'display_main_page'),
            'dashicons-video-alt3',
            30
        );
        
        add_submenu_page(
            'kick-wp',
            __('Dashboard', 'kick-wp'),
            __('Dashboard', 'kick-wp'),
            'manage_options',
            'kick-wp',
            array($this, 'display_main_page')
        );
        
        add_submenu_page(
            'kick-wp',
            __('Configuración', 'kick-wp'),
            __('Configuración', 'kick-wp'),
            'manage_options',
            'kick-wp-settings',
            array($this, 'display_settings_page')
        );
    }

    public function register_settings() {
        // Configuraciones básicas
        register_setting('kick_wp_settings', 'kick_wp_cache_duration', array(
            'type' => 'integer',
            'default' => 300,
            'sanitize_callback' => array($this, 'sanitize_cache_duration')
        ));
        
        register_setting('kick_wp_settings', 'kick_wp_streams_per_page', array(
            'type' => 'integer',
            'default' => 12,
            'sanitize_callback' => array($this, 'sanitize_streams_per_page')
        ));
        
        register_setting('kick_wp_settings', 'kick_wp_layout_style', array(
            'type' => 'string',
            'default' => 'grid',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        register_setting('kick_wp_settings', 'kick_wp_show_viewer_count', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));
        
        register_setting('kick_wp_settings', 'kick_wp_show_categories', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));
        
        // Configuraciones OAuth
        register_setting('kick_wp_settings', 'kick_wp_client_id', array(
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        register_setting('kick_wp_settings', 'kick_wp_client_secret', array(
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'sanitize_text_field'
        ));
    }

    public function sanitize_cache_duration($value) {
        $value = absint($value);
        return max($value, 60);
    }

    public function sanitize_streams_per_page($value) {
        $value = absint($value);
        return max(min($value, 50), 4);
    }

    public function display_main_page() {
        // Test de conexión
        $connection_test = $this->api->test_connection();
        
        // Obtener datos para preview
        $featured_streams = $this->api->get_featured_streams(array('limit' => 6));
        
        // Verificar configuración OAuth
        $client_id = get_option('kick_wp_client_id', '');
        $client_secret = get_option('kick_wp_client_secret', '');
        $auth_token = get_option('kick_wp_auth_token', '');
        $token_expires = get_option('kick_wp_token_expires', 0);
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Kick WP Dashboard', 'kick-wp'); ?></h1>
            
            <div class="kick-wp-dashboard">
                <!-- Estado de Conexión -->
                <div class="kick-wp-card">
                    <h2><?php esc_html_e('Estado del Sistema', 'kick-wp'); ?></h2>
                    
                    <div class="kick-wp-status-indicator <?php echo $connection_test['success'] ? 'status-success' : 'status-error'; ?>">
                        <span class="dashicons dashicons-<?php echo $connection_test['success'] ? 'yes' : 'no'; ?>"></span>
                        <strong><?php esc_html_e('API de Kick.com:', 'kick-wp'); ?></strong>
                        <?php echo esc_html($connection_test['message']); ?>
                    </div>
                    
                    <?php if (isset($connection_test['details'])): ?>
                        <p style="margin-top: 10px; color: #646970;">
                            <?php echo esc_html($connection_test['details']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <!-- OAuth Status -->
                    <div style="margin-top: 15px;">
                        <h4><?php esc_html_e('Estado de Autenticación', 'kick-wp'); ?></h4>
                        <?php if (!empty($auth_token) && time() < $token_expires): ?>
                            <div class="kick-wp-status-indicator status-success">
                                <span class="dashicons dashicons-yes"></span>
                                <?php 
                                $expires_in = human_time_diff(time(), $token_expires);
                                printf(esc_html__('Autenticado (expira en %s)', 'kick-wp'), $expires_in);
                                ?>
                            </div>
                        <?php elseif (!empty($auth_token)): ?>
                            <div class="kick-wp-status-indicator status-warning">
                                <span class="dashicons dashicons-warning"></span>
                                <?php esc_html_e('Token expirado - Requiere renovación', 'kick-wp'); ?>
                            </div>
                        <?php else: ?>
                            <div class="kick-wp-status-indicator status-error">
                                <span class="dashicons dashicons-no"></span>
                                <?php esc_html_e('No autenticado', 'kick-wp'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Configuración OAuth -->
                <?php if (empty($client_id) || empty($client_secret)): ?>
                <div class="kick-wp-card">
                    <h2><?php esc_html_e('Configuración Requerida', 'kick-wp'); ?></h2>
                    <div class="kick-wp-oauth-section">
                        <p><?php esc_html_e('Para utilizar todas las funciones de Kick WP, necesitas configurar las credenciales OAuth:', 'kick-wp'); ?></p>
                        <ol>
                            <li><?php esc_html_e('Visita el portal de desarrolladores de Kick.com', 'kick-wp'); ?></li>
                            <li><?php esc_html_e('Crea una nueva aplicación', 'kick-wp'); ?></li>
                            <li><?php esc_html_e('Configura el Client ID y Client Secret en la página de configuración', 'kick-wp'); ?></li>
                        </ol>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=kick-wp-settings')); ?>" class="kick-wp-oauth-button">
                            <?php esc_html_e('Ir a Configuración', 'kick-wp'); ?>
                        </a>
                    </div>
                </div>
                <?php elseif (empty($auth_token) || time() >= $token_expires): ?>
                <div class="kick-wp-card">
                    <h2><?php esc_html_e('Autenticación con Kick.com', 'kick-wp'); ?></h2>
                    <div class="kick-wp-oauth-section">
                        <p><?php esc_html_e('Conecta tu cuenta de Kick.com para acceder a streams seguidos y funciones avanzadas:', 'kick-wp'); ?></p>
                        <?php
                        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kick-wp-oauth.php';
                        $oauth = new Kick_Wp_OAuth();
                        $auth_url = $oauth->get_auth_url();
                        
                        if ($auth_url): ?>
                            <a href="<?php echo esc_url($auth_url); ?>" class="kick-wp-oauth-button">
                                <?php esc_html_e('Conectar con Kick.com', 'kick-wp'); ?>
                            </a>
                        <?php else: ?>
                            <p class="description" style="color: #d63638;">
                                <?php esc_html_e('Error: No se pudo generar la URL de autenticación. Verifica tu configuración.', 'kick-wp'); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Preview de Streams -->
                <div class="kick-wp-card">
                    <h2><?php esc_html_e('Vista Previa de Streams', 'kick-wp'); ?></h2>
                    <div class="kick-wp-streams-preview">
                        <?php if (!empty($featured_streams['data'])): ?>
                            <?php foreach (array_slice($featured_streams['data'], 0, 6) as $stream): ?>
                                <div class="kick-wp-stream-preview-card">
                                    <img src="<?php echo esc_url($stream['thumbnail']); ?>" 
                                         alt="<?php echo esc_attr($stream['username']); ?>"
                                         onerror="this.src='https://via.placeholder.com/200x120?text=<?php echo urlencode($stream['username']); ?>'" />
                                    <div class="kick-wp-stream-preview-info">
                                        <h4><?php echo esc_html($stream['username']); ?></h4>
                                        <p><?php echo esc_html(wp_trim_words($stream['title'], 8)); ?></p>
                                        <div class="kick-wp-viewer-badge">
                                            <span class="dashicons dashicons-visibility"></span>
                                            <?php echo number_format_i18n($stream['viewer_count']); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p><?php esc_html_e('No se pudieron cargar los streams. Esto puede deberse a restricciones de la API.', 'kick-wp'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Guía de Uso -->
                <div class="kick-wp-card">
                    <h2><?php esc_html_e('Cómo usar Kick WP', 'kick-wp'); ?></h2>
                    
                    <h3><?php esc_html_e('Shortcodes disponibles:', 'kick-wp'); ?></h3>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Shortcode', 'kick-wp'); ?></th>
                                <th><?php esc_html_e('Descripción', 'kick-wp'); ?></th>
                                <th><?php esc_html_e('Ejemplo', 'kick-wp'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>[kick_wp_streams]</code></td>
                                <td><?php esc_html_e('Muestra streams destacados', 'kick-wp'); ?></td>
                                <td><code>[kick_wp_streams count="6" layout="grid"]</code></td>
                            </tr>
                            <tr>
                                <td><code>[kick_wp_streams streamer="username"]</code></td>
                                <td><?php esc_html_e('Muestra un streamer específico', 'kick-wp'); ?></td>
                                <td><code>[kick_wp_streams streamer="username"]</code></td>
                            </tr>
                            <tr>
                                <td><code>[kick_wp_categories]</code></td>
                                <td><?php esc_html_e('Muestra categorías disponibles', 'kick-wp'); ?></td>
                                <td><code>[kick_wp_categories]</code></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h4 style="margin-top: 20px;"><?php esc_html_e('Parámetros disponibles:', 'kick-wp'); ?></h4>
                    <ul>
                        <li><strong>count:</strong> <?php esc_html_e('Número de streams a mostrar (1-50)', 'kick-wp'); ?></li>
                        <li><strong>layout:</strong> <?php esc_html_e('grid (cuadrícula) o list (lista)', 'kick-wp'); ?></li>
                        <li><strong>streamer:</strong> <?php esc_html_e('Username específico de Kick.com', 'kick-wp'); ?></li>
                        <li><strong>category:</strong> <?php esc_html_e('Filtrar por categoría específica', 'kick-wp'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }

    public function display_settings_page() {
        // Verificar configuración OAuth
        $client_id = get_option('kick_wp_client_id', '');
        $client_secret = get_option('kick_wp_client_secret', '');
        $auth_token = get_option('kick_wp_auth_token', '');
        $token_expires = get_option('kick_wp_token_expires', 0);
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Configuración de Kick WP', 'kick-wp'); ?></h1>
            
            <?php settings_errors('kick_wp_messages'); ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('kick_wp_settings');
                do_settings_sections('kick_wp_settings');
                ?>
                
                <div class="kick-wp-dashboard">
                    <!-- Configuración OAuth -->
                    <div class="kick-wp-card">
                        <h2><?php esc_html_e('Configuración de API de Kick.com', 'kick-wp'); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="kick_wp_client_id">
                                        <?php esc_html_e('Client ID', 'kick-wp'); ?>
                                    </label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="kick_wp_client_id" 
                                           name="kick_wp_client_id" 
                                           value="<?php echo esc_attr($client_id); ?>" 
                                           class="regular-text" />
                                    <p class="description">
                                        <?php esc_html_e('ID de cliente obtenido del portal de desarrolladores de Kick.com', 'kick-wp'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="kick_wp_client_secret">
                                        <?php esc_html_e('Client Secret', 'kick-wp'); ?>
                                    </label>
                                </th>
                                <td>
                                    <input type="password" 
                                           id="kick_wp_client_secret" 
                                           name="kick_wp_client_secret" 
                                           value="<?php echo esc_attr($client_secret); ?>" 
                                           class="regular-text" />
                                    <p class="description">
                                        <?php esc_html_e('Clave secreta obtenida del portal de desarrolladores de Kick.com', 'kick-wp'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                        
                        <!-- Estado OAuth -->
                        <h3><?php esc_html_e('Estado de Autenticación', 'kick-wp'); ?></h3>
                        <div class="kick-wp-oauth-section">
                            <?php if (!empty($auth_token) && time() < $token_expires): ?>
                                <div class="kick-wp-status-indicator status-success">
                                    <span class="dashicons dashicons-yes"></span>
                                    <?php 
                                    $expires_in = human_time_diff(time(), $token_expires);
                                    printf(esc_html__('Conectado - Token expira en %s', 'kick-wp'), $expires_in);
                                    ?>
                                </div>
                                <p>
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=kick-wp-settings&action=revoke_token'), 'kick_wp_revoke_token')); ?>" 
                                       class="button" 
                                       onclick="return confirm('<?php esc_attr_e('¿Estás seguro de que quieres revocar el acceso?', 'kick-wp'); ?>')">
                                        <?php esc_html_e('Revocar Acceso', 'kick-wp'); ?>
                                    </a>
                                </p>
                            <?php elseif (!empty($auth_token)): ?>
                                <div class="kick-wp-status-indicator status-warning">
                                    <span class="dashicons dashicons-warning"></span>
                                    <?php esc_html_e('Token expirado - Requiere renovación', 'kick-wp'); ?>
                                </div>
                            <?php else: ?>
                                <div class="kick-wp-status-indicator status-error">
                                    <span class="dashicons dashicons-no"></span>
                                    <?php esc_html_e('No autenticado', 'kick-wp'); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($client_id) && !empty($client_secret) && (empty($auth_token) || time() >= $token_expires)): ?>
                                <?php
                                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kick-wp-oauth.php';
                                $oauth = new Kick_Wp_OAuth();
                                $auth_url = $oauth->get_auth_url();
                                
                                if ($auth_url): ?>
                                    <br>
                                    <a href="<?php echo esc_url($auth_url); ?>" class="kick-wp-oauth-button">
                                        <?php esc_html_e('Conectar con Kick.com', 'kick-wp'); ?>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <p class="description" style="margin-top: 15px;">
                                <strong><?php esc_html_e('Nota:', 'kick-wp'); ?></strong>
                                <?php esc_html_e('Para obtener las credenciales OAuth, debes registrar una aplicación en el portal de desarrolladores de Kick.com. La URL de redirección debe ser:', 'kick-wp'); ?>
                                <br>
                                <code><?php echo esc_html(admin_url('admin.php?page=kick-wp-settings&oauth=callback')); ?></code>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Configuración General -->
                    <div class="kick-wp-card">
                        <h2><?php esc_html_e('Configuración General', 'kick-wp'); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="kick_wp_cache_duration">
                                        <?php esc_html_e('Duración del Caché', 'kick-wp'); ?>
                                    </label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="kick_wp_cache_duration" 
                                           name="kick_wp_cache_duration" 
                                           value="<?php echo esc_attr(get_option('kick_wp_cache_duration', 300)); ?>"
                                           min="60"
                                           class="small-text" />
                                    <span class="description">
                                        <?php esc_html_e('segundos (mínimo 60)', 'kick-wp'); ?>
                                    </span>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="kick_wp_streams_per_page">
                                        <?php esc_html_e('Streams por Página', 'kick-wp'); ?>
                                    </label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="kick_wp_streams_per_page" 
                                           name="kick_wp_streams_per_page" 
                                           value="<?php echo esc_attr(get_option('kick_wp_streams_per_page', 12)); ?>"
                                           min="4"
                                           max="50"
                                           class="small-text" />
                                    <span class="description">
                                        <?php esc_html_e('entre 4 y 50', 'kick-wp'); ?>
                                    </span>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="kick_wp_layout_style">
                                        <?php esc_html_e('Estilo de Layout', 'kick-wp'); ?>
                                    </label>
                                </th>
                                <td>
                                    <select id="kick_wp_layout_style" name="kick_wp_layout_style">
                                        <option value="grid" <?php selected('grid', get_option('kick_wp_layout_style', 'grid')); ?>>
                                            <?php esc_html_e('Cuadrícula', 'kick-wp'); ?>
                                        </option>
                                        <option value="list" <?php selected('list', get_option('kick_wp_layout_style', 'grid')); ?>>
                                            <?php esc_html_e('Lista', 'kick-wp'); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Mostrar Información', 'kick-wp'); ?>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" 
                                               name="kick_wp_show_viewer_count" 
                                               value="1" 
                                               <?php checked(1, get_option('kick_wp_show_viewer_count', true)); ?> />
                                        <?php esc_html_e('Contador de espectadores', 'kick-wp'); ?>
                                    </label>
                                    <br />
                                    <label>
                                        <input type="checkbox" 
                                               name="kick_wp_show_categories" 
                                               value="1" 
                                               <?php checked(1, get_option('kick_wp_show_categories', true)); ?> />
                                        <?php esc_html_e('Categorías', 'kick-wp'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Herramientas -->
                    <div class="kick-wp-card">
                        <h2><?php esc_html_e('Herramientas', 'kick-wp'); ?></h2>
                        
                        <p>
                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=kick-wp-settings&action=clear_cache'), 'kick_wp_clear_cache')); ?>" 
                               class="button" 
                               onclick="return confirm('<?php esc_attr_e('¿Estás seguro de que quieres limpiar el caché?', 'kick-wp'); ?>')">
                                <?php esc_html_e('Limpiar Caché', 'kick-wp'); ?>
                            </a>
                        </p>
                        
                        <p class="description">
                            <?php esc_html_e('Limpia todos los datos almacenados en caché para forzar la actualización desde la API.', 'kick-wp'); ?>
                        </p>
                    </div>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function add_settings_link($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=kick-wp-settings'),
            __('Configuración', 'kick-wp')
        );
        
        array_unshift($links, $settings_link);
        return $links;
    }

    public function handle_admin_actions() {
        // Acción para limpiar caché
        if (isset($_GET['action']) && $_GET['action'] === 'clear_cache' && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'kick_wp_clear_cache')) {
            $this->api->clear_cache();
            
            add_settings_error(
                'kick_wp_messages',
                'cache_cleared',
                __('Caché limpiada correctamente.', 'kick-wp'),
                'updated'
            );
            
            wp_redirect(admin_url('admin.php?page=kick-wp-settings&cache_cleared=1'));
            exit;
        }
        
        // Acción para revocar token
        if (isset($_GET['action']) && $_GET['action'] === 'revoke_token' && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'kick_wp_revoke_token')) {
            // Eliminar opciones relacionadas con la autenticación
            delete_option('kick_wp_auth_token');
            delete_option('kick_wp_refresh_token');
            delete_option('kick_wp_token_expires');
            
            add_settings_error(
                'kick_wp_messages',
                'token_revoked',
                __('Acceso revocado correctamente.', 'kick-wp'),
                'updated'
            );
            
            wp_redirect(admin_url('admin.php?page=kick-wp-settings&token_revoked=1'));
            exit;
        }
    }

    public function show_admin_notices() {
        // Mostrar notificaciones específicas según parámetros GET
        if (isset($_GET['page']) && strpos($_GET['page'], 'kick-wp') !== false) {
            if (isset($_GET['cache_cleared'])) {
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Caché limpiada correctamente.', 'kick-wp'); ?></p>
                </div>
                <?php
            }
            
            if (isset($_GET['token_revoked'])) {
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Acceso revocado correctamente.', 'kick-wp'); ?></p>
                </div>
                <?php
            }
            
            if (isset($_GET['auth']) && $_GET['auth'] === 'success') {
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Autenticación exitosa con Kick.com.', 'kick-wp'); ?></p>
                </div>
                <?php
            }
        }
    }
}