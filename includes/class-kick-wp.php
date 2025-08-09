<?php
/**
 *
 * @link       https://blancocl.vercel.app
 * @since      1.0.0
 *
 * @package    Kick_Wp
 * @subpackage Kick_Wp/includes
 */

class Kick_Wp {

    /**
     * Instancia del loader
     */
    protected $loader;

    /**
     * Nombre único del plugin
     */
    protected $plugin_name;

    /**
     * Versión actual del plugin
     */
    protected $version;

    /**
     * Constructor
     */
    public function __construct() {
        if (defined('KICK_WP_VERSION')) {
            $this->version = KICK_WP_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        
        $this->plugin_name = 'kick-wp';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Cargar dependencias
     */
    private function load_dependencies() {
        // Loader
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kick-wp-loader.php';
        
        // Internacionalización
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kick-wp-i18n.php';
        
        // API y OAuth
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kick-wp-api.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kick-wp-oauth.php';
        
        // Admin
        if (is_admin()) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-kick-wp-admin.php';
        }
        
        // Public
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-kick-wp-public.php';

        $this->loader = new Kick_Wp_Loader();
    }

    /**
     * Definir configuración de idiomas
     */
    private function set_locale() {
        $plugin_i18n = new Kick_Wp_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Definir hooks de administración
     */
    private function define_admin_hooks() {
        if (!is_admin()) {
            return;
        }
        
        $plugin_admin = new Kick_Wp_Admin($this->get_plugin_name(), $this->get_version());

        // Scripts y estilos
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        // Menú y configuraciones
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
        
        // Añadir el manejador de acciones administrativas
        $this->loader->add_action('admin_init', $plugin_admin, 'handle_admin_actions');

        // Enlace de configuración en la lista de plugins
        if (defined('KICK_WP_FILE')) {
            $this->loader->add_filter(
                'plugin_action_links_' . plugin_basename(KICK_WP_FILE),
                $plugin_admin,
                'add_settings_link'
            );
        }
    }

    /**
     * Definir hooks públicos
     */
    private function define_public_hooks() {
        $plugin_public = new Kick_Wp_Public($this->get_plugin_name(), $this->get_version());

        // Scripts y estilos
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

        // Los shortcodes ya se registran en el constructor de Kick_Wp_Public
    }

    /**
     * Ejecutar el loader
     */
    public function run() {
        $this->loader->run();
        
        // Asegurar que la API esté disponible globalmente
        if (!isset($GLOBALS['kick_wp_api'])) {
            $GLOBALS['kick_wp_api'] = new Kick_Wp_Api();
        }
    }

    /**
     * Obtener nombre del plugin
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Obtener referencia del loader
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Obtener versión del plugin
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Método estático para obtener instancia de la API
     */
    public static function get_api_instance() {
        if (isset($GLOBALS['kick_wp_api'])) {
            return $GLOBALS['kick_wp_api'];
        }
        
        return new Kick_Wp_Api();
    }

    /**
     * Log de errores del plugin
     */
    public static function log_error($message, $context = array()) {
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            $log_message = '[Kick WP] ' . $message;
            
            if (!empty($context)) {
                $log_message .= ' Context: ' . print_r($context, true);
            }
            
            error_log($log_message);
        }
    }

    /**
     * Obtener configuración del plugin
     */
    public static function get_settings() {
        return array(
            'cache_duration' => get_option('kick_wp_cache_duration', 300),
            'streams_per_page' => get_option('kick_wp_streams_per_page', 12),
            'layout_style' => get_option('kick_wp_layout_style', 'grid'),
            'show_viewer_count' => get_option('kick_wp_show_viewer_count', true),
            'show_categories' => get_option('kick_wp_show_categories', true),
            'auto_refresh' => get_option('kick_wp_auto_refresh', true)
        );
    }

    /**
     * Verificar si el plugin está configurado correctamente
     */
    public static function is_configured() {
        $settings = self::get_settings();
        
        // Verificaciones básicas
        if (empty($settings['cache_duration']) || $settings['cache_duration'] < 60) {
            return false;
        }
        
        if (empty($settings['streams_per_page']) || $settings['streams_per_page'] < 1) {
            return false;
        }
        
        return true;
    }
}