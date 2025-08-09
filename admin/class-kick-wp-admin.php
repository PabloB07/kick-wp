<?php
/**
 * Funcionalidad de administración unificada
 *
 * @link       https://blancocl.vercel.app
 * @since      1.0.0
 *
 * @package    Kick_Wp
 * @subpackage Kick_Wp/admin
 */

class Kick_Wp_Admin {
    
    /**
     * ID del plugin
     */
    private $plugin_name;
    
    /**
     * Versión del plugin
     */
    private $version;
    
    /**
     * Instancia de la API
     */
    private $api;

    /**
     * Constructor
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->api = new Kick_Wp_Api();
        
        $this->init_hooks();
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_plugin_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Enlace de configuración en plugins
        if (defined('KICK_WP_FILE')) {
            add_filter('plugin_action_links_' . plugin_basename(KICK_WP_FILE), array($this, 'add_settings_link'));
        }
    }

    /**
     * Registrar estilos
     */
    public function enqueue_styles($hook) {
        // Solo cargar en páginas del plugin
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
    }

    /**
     * Registrar scripts
     */
    public function enqueue_scripts($hook) {
        // Solo cargar en páginas del plugin
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

    /**
     * Agregar menú de administración
     */
    public function add_plugin_admin_menu() {
        // Página principal
        add_menu_page(
            __('Kick WP', 'kick-wp'),
            __('Kick WP', 'kick-wp'),
            'manage_options',
            'kick-wp',
            array($this, 'display_main_page'),
            'dashicons-video-alt3',
            30
        );
        
        // Subpáginas
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

    /**
     * Registrar configuraciones
     */
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
    }

    /**
     * Sanitizar duración de caché
     */
    public function sanitize_cache_duration($value) {
        $value = absint($value);
        return max($value, 60); // Mínimo 60 segundos
    }

    /**
     * Sanitizar streams por página
     */
    public function sanitize_streams_per_page($value) {
        $value = absint($value);
        return max(min($value, 24), 4); // Entre 4 y 24
    }

    /**
     * Mostrar página principal
     */
    public function display_main_page() {
        // Test de conexión
        $connection_test = $this->api->test_connection();
        
        // Obtener datos
        $featured_streams = $this->api->get_featured_streams(array('limit' => 6));
        $categories = $this->api->get_categories();
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Kick WP Dashboard', 'kick-wp'); ?></h1>
            
            <!-- Estado de conexión -->
            <div class="notice <?php echo $connection_test['success'] ? 'notice-success' : 'notice-error'; ?>">
                <p>
                    <strong><?php esc_html_e('Estado de la API:', 'kick-wp'); ?></strong>
                    <?php echo esc_html($connection_test['message']); ?>
                </p>
            </div>
            
            <div class="kick-wp-dashboard">
                <!-- Streams Destacados -->
                <div class="kick-wp-section">
                    <h2><?php esc_html_e('Streams Destacados', 'kick-wp'); ?></h2>
                    <div class="kick-wp-streams-grid">
                        <?php if (!empty($featured_streams['data'])): ?>
                            <?php foreach (array_slice($featured_streams['data'], 0, 6) as $stream): ?>
                                <div class="kick-wp-stream-card">
                                    <img src="<?php echo esc_url($stream['thumbnail']); ?>" 
                                         alt="<?php echo esc_attr($stream['username']); ?>"
                                         onerror="this.src='https://via.placeholder.com/320x180?text=No+Image'" />
                                    <h4><?php echo esc_html($stream['username']); ?></h4>
                                    <p><?php echo esc_html($stream['title']); ?></p>
                                    <div class="stream-meta">
                                        <span class="viewers">👁 <?php echo number_format($stream['viewer_count']); ?></span>
                                        <?php if (!empty($stream['category'])): ?>
                                            <span class="category"><?php echo esc_html($stream['category']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <a href="<?php echo esc_url($stream['channel_url']); ?>" 
                                       target="_blank" class="button button-small">
                                        <?php esc_html_e('Ver Stream', 'kick-wp'); ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p><?php esc_html_e('No se pudieron cargar los streams.', 'kick-wp'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Guía de uso -->
                <div class="kick-wp-section">
                    <h2><?php esc_html_e('Cómo usar Kick WP', 'kick-wp'); ?></h2>
                    <div class="kick-wp-usage-guide">
                        <h3><?php esc_html_e('Shortcodes disponibles:', 'kick-wp'); ?></h3>
                        <table class="widefat">
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
                                    <td><code>[kick_wp_streams streamer="xqc"]</code></td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <h4><?php esc_html_e('Parámetros:', 'kick-wp'); ?></h4>
                        <ul>
                            <li><strong>count:</strong> <?php esc_html_e('Número de streams a mostrar (1-12)', 'kick-wp'); ?></li>
                            <li><strong>layout:</strong> <?php esc_html_e('grid o list', 'kick-wp'); ?></li>
                            <li><strong>streamer:</strong> <?php esc_html_e('Username específico de Kick.com', 'kick-wp'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .kick-wp-dashboard {
            display: grid;
            gap: 20px;
        }
        
        .kick-wp-section {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
        }
        
        .kick-wp-streams-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .kick-wp-stream-card {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            text-align: center;
        }
        
        .kick-wp-stream-card img {
            width: 100%;
            height: auto;
            max-height: 120px;
            object-fit: cover;
            border-radius: 3px;
        }
        
        .kick-wp-stream-card h4 {
            margin: 10px 0 5px 0;
            font-size: 14px;
        }
        
        .kick-wp-stream-card p {
            font-size: 12px;
            color: #666;
            margin: 5px 0;
        }
        
        .stream-meta {
            font-size: 11px;
            color: #999;
            margin: 8px 0;
        }
        
        .stream-meta .category {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 2px;
            margin-left: 5px;
        }
        
        .kick-wp-usage-guide table {
            margin-top: 10px;
        }
        
        .kick-wp-usage-guide code {
            background: #f1f1f1;
            padding: 2px 6px;
            border-radius: 2px;
        }
        </style>
        <?php
    }

    /**
     * Mostrar página de configuración
     */
    public function display_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Configuración de Kick WP', 'kick-wp'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('kick_wp_settings');
                do_settings_sections('kick_wp_settings');
                ?>
                
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
                                   max="24"
                                   class="small-text" />
                            <span class="description">
                                <?php esc_html_e('entre 4 y 24', 'kick-wp'); ?>
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
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Agregar enlace de configuración
     */
    public function add_settings_link($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=kick-wp-settings'),
            __('Configuración', 'kick-wp')
        );
        
        array_unshift($links, $settings_link);
        return $links;
    }

    // Añadir este método en la clase Kick_Wp_Admin
    public function handle_admin_actions() {
        if (isset($_GET['action']) && $_GET['action'] === 'clear_cache' && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'kick_wp_clear_cache')) {
            $api = new Kick_Wp_Api();
            $api->clear_cache();
            
            add_settings_error(
                'kick_wp_messages',
                'cache_cleared',
                __('Caché limpiada correctamente.', 'kick-wp'),
                'success'
            );
            
            wp_redirect(admin_url('admin.php?page=kick-wp-settings&cache_cleared=1'));
            exit;
        }
    }
}