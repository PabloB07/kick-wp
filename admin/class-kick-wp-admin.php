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
            array($this, 'display_unified_page'),
            'dashicons-video-alt3',
            30
        );
        
        add_submenu_page(
            'kick-wp',
            __('Dashboard', 'kick-wp'),
            __('Dashboard', 'kick-wp'),
            'manage_options',
            'kick-wp',
            array($this, 'display_unified_page')
        );
        
        // Eliminar la página de configuración separada y usar pestañas en su lugar
    }
    
    public function display_unified_page() {
        // Test de conexión
        $connection_test = $this->api->test_connection();
        
        // Obtener datos para preview
        $featured_streams = $this->api->get_featured_streams(array('limit' => 6));
        $categories = $this->api->get_categories();
        
        // Verificar configuración OAuth
        $client_id = get_option('kick_wp_client_id', '');
        $client_secret = get_option('kick_wp_client_secret', '');
        $auth_token = get_option('kick_wp_auth_token', '');
        $token_expires = get_option('kick_wp_token_expires', 0);
        
        // Cargar la vista unificada
        include plugin_dir_path(__FILE__) . 'partials/kick-wp-unified-display.php';
    }

    public function handle_admin_actions() {
        // Acción para limpiar caché
        if (isset($_GET['action']) && $_GET['action'] === 'clear_cache' && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'kick_wp_clear_cache')) {
            $this->api->clear_cache();
            
            add_settings_error(
                'kick_wp_messages',
                'cache_cleared',
                __('Caché limpiada correctamente.', 'kick-wp'),
                'success'
            );
            
            wp_redirect(admin_url('admin.php?page=kick-wp&cache_cleared=1'));
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
                'success'
            );
            
            wp_redirect(admin_url('admin.php?page=kick-wp&token_revoked=1'));
            exit;
        }
    }