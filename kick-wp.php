<?php
/**
 * Plugin Name: Kick WP
 * Plugin URI: https://blancocl.vercel.app
 * Description: Plugin para integrar streams de Kick.com en WordPress con autenticación OAuth2, shortcodes y dashboard administrativo.
 * Version: 1.2.0
 * Author: PabloB07
 * Author URI: https://blancocl.vercel.app
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: kick-wp
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Constantes del plugin
 */
define('KICK_WP_VERSION', '1.2.0');
define('KICK_WP_FILE', __FILE__);
define('KICK_WP_PATH', plugin_dir_path(__FILE__));
define('KICK_WP_URL', plugin_dir_url(__FILE__));
define('KICK_WP_BASENAME', plugin_basename(__FILE__));

/**
 * Verificar requisitos mínimos
 */
function kick_wp_check_requirements() {
    $requirements = array();
    
    // Verificar versión de PHP
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        $requirements[] = sprintf(
            __('Se requiere PHP 7.4 o superior. Tu versión actual es: %s', 'kick-wp'),
            PHP_VERSION
        );
    }
    
    // Verificar versión de WordPress
    global $wp_version;
    if (version_compare($wp_version, '5.0', '<')) {
        $requirements[] = sprintf(
            __('Se requiere WordPress 5.0 o superior. Tu versión actual es: %s', 'kick-wp'),
            $wp_version
        );
    }
    
    // Verificar funciones necesarias
    if (!function_exists('wp_remote_get')) {
        $requirements[] = __('La función wp_remote_get() no está disponible.', 'kick-wp');
    }
    
    if (!function_exists('json_decode')) {
        $requirements[] = __('La extensión JSON de PHP no está disponible.', 'kick-wp');
    }
    
    if (!extension_loaded('curl') && !function_exists('wp_remote_get')) {
        $requirements[] = __('Se requiere la extensión cURL de PHP o las funciones HTTP de WordPress.', 'kick-wp');
    }
    
    return $requirements;
}

/**
 * Mostrar errores de requisitos
 */
function kick_wp_requirements_notice() {
    $requirements = kick_wp_check_requirements();
    
    if (!empty($requirements)) {
        ?>
        <div class="notice notice-error">
            <p><strong><?php esc_html_e('Kick WP no puede activarse:', 'kick-wp'); ?></strong></p>
            <ul style="margin-left: 20px;">
                <?php foreach ($requirements as $requirement): ?>
                    <li><?php echo esc_html($requirement); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }
}

/**
 * Verificar requisitos antes de cargar el plugin
 */
$requirements = kick_wp_check_requirements();
if (!empty($requirements)) {
    add_action('admin_notices', 'kick_wp_requirements_notice');
    return; // No cargar el plugin
}

/**
 * Código que se ejecuta durante la activación del plugin
 */
function activate_kick_wp() {
    require_once KICK_WP_PATH . 'includes/class-kick-wp-activator.php';
    Kick_Wp_Activator::activate();
}

/**
 * Código que se ejecuta durante la desactivación del plugin
 */
function deactivate_kick_wp() {
    require_once KICK_WP_PATH . 'includes/class-kick-wp-deactivator.php';
    Kick_Wp_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_kick_wp');
register_deactivation_hook(__FILE__, 'deactivate_kick_wp');

/**
 * La clase principal del plugin
 */
require KICK_WP_PATH . 'includes/class-kick-wp.php';

/**
 * Cargar dependencias adicionales
 */
require_once KICK_WP_PATH . 'includes/class-kick-wp-api.php';
require_once KICK_WP_PATH . 'includes/class-kick-wp-oauth.php';

if (is_admin()) {
    require_once KICK_WP_PATH . 'admin/class-kick-wp-admin.php';
} else {
    require_once KICK_WP_PATH . 'public/class-kick-wp-public.php';
}

/**
 * Inicializar el plugin
 */
function run_kick_wp() {
    $plugin = new Kick_Wp();
    $plugin->run();
}

// Ejecutar el plugin después de que WordPress esté completamente cargado
add_action('plugins_loaded', 'run_kick_wp');

/**
 * Cargar textdomain para internacionalización
 */
function kick_wp_load_textdomain() {
    load_plugin_textdomain(
        'kick-wp',
        false,
        dirname(KICK_WP_BASENAME) . '/languages/'
    );
}
add_action('init', 'kick_wp_load_textdomain');

/**
 * Agregar enlaces de acción en la página de plugins
 */
function kick_wp_add_action_links($links) {
    $action_links = array(
        'settings' => '<a href="' . admin_url('admin.php?page=kick-wp-settings') . '">' . __('Configuración', 'kick-wp') . '</a>',
        'dashboard' => '<a href="' . admin_url('admin.php?page=kick-wp') . '">' . __('Dashboard', 'kick-wp') . '</a>',
    );
    
    return array_merge($action_links, $links);
}
add_filter('plugin_action_links_' . KICK_WP_BASENAME, 'kick_wp_add_action_links');

/**
 * Agregar meta links en la página de plugins
 */
function kick_wp_add_meta_links($links, $file) {
    if ($file === KICK_WP_BASENAME) {
        $meta_links = array(
            'docs' => '<a href="https://blancocl.vercel.app" target="_blank">' . __('Documentación', 'kick-wp') . '</a>',
            'support' => '<a href="https://blancocl.vercel.app" target="_blank">' . __('Soporte', 'kick-wp') . '</a>',
        );
        
        return array_merge($links, $meta_links);
    }
    
    return $links;
}
add_filter('plugin_row_meta', 'kick_wp_add_meta_links', 10, 2);

/**
 * AJAX handler para test de conexión
 */
function kick_wp_ajax_test_connection() {
    // Verificar nonce
    if (!wp_verify_nonce($_POST['nonce'], 'kick_wp_nonce')) {
        wp_die(__('Acceso denegado', 'kick-wp'));
    }
    
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Permisos insuficientes', 'kick-wp'));
    }
    
    $api = new Kick_Wp_Api();
    $result = $api->test_connection();
    
    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}
add_action('wp_ajax_kick_wp_test_connection', 'kick_wp_ajax_test_connection');

/**
 * AJAX handler para auto-guardado
 */
function kick_wp_ajax_autosave() {
    // Verificar nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'kick_wp_nonce')) {
        wp_die(__('Acceso denegado', 'kick-wp'));
    }
    
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Permisos insuficientes', 'kick-wp'));
    }
    
    // Guardar configuraciones (implementar según necesidades)
    $settings = array(
        'kick_wp_cache_duration' => isset($_POST['kick_wp_cache_duration']) ? absint($_POST['kick_wp_cache_duration']) : 300,
        'kick_wp_streams_per_page' => isset($_POST['kick_wp_streams_per_page']) ? absint($_POST['kick_wp_streams_per_page']) : 12,
        'kick_wp_layout_style' => isset($_POST['kick_wp_layout_style']) ? sanitize_text_field($_POST['kick_wp_layout_style']) : 'grid'
    );
    
    foreach ($settings as $key => $value) {
        update_option($key, $value);
    }
    
    wp_send_json_success(__('Configuraciones guardadas', 'kick-wp'));
}
add_action('wp_ajax_kick_wp_autosave', 'kick_wp_ajax_autosave');

/**
 * AJAX handler para renovar token
 */
function kick_wp_ajax_renew_token() {
    // Verificar nonce
    if (!wp_verify_nonce($_POST['nonce'], 'kick_wp_nonce')) {
        wp_die(__('Acceso denegado', 'kick-wp'));
    }
    
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Permisos insuficientes', 'kick-wp'));
    }
    
    $oauth = new Kick_Wp_OAuth();
    $result = $oauth->maybe_refresh_token();
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    } else {
        wp_send_json_success(__('Token renovado correctamente', 'kick-wp'));
    }
}
add_action('wp_ajax_kick_wp_renew_token', 'kick_wp_ajax_renew_token');

/**
 * Limpiar datos al desinstalar el plugin
 */
function kick_wp_uninstall() {
    // Solo ejecutar si realmente se está desinstalando
    if (!defined('WP_UNINSTALL_PLUGIN')) {
        return;
    }
    
    // Limpiar opciones
    $options = array(
        'kick_wp_cache_duration',
        'kick_wp_streams_per_page',
        'kick_wp_layout_style',
        'kick_wp_show_viewer_count',
        'kick_wp_show_categories',
        'kick_wp_client_id',
        'kick_wp_client_secret',
        'kick_wp_auth_token',
        'kick_wp_refresh_token',
        'kick_wp_token_expires',
        'kick_wp_version',
        'kick_wp_activated_time'
    );
    
    foreach ($options as $option) {
        delete_option($option);
    }
    
    // Limpiar transients/cache
    global $wpdb;
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $wpdb->esc_like('_transient_kick_wp_') . '%',
            $wpdb->esc_like('_transient_timeout_kick_wp_') . '%'
        )
    );
    
    // Limpiar caché de objetos si está disponible
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
}

/**
 * Hook de debug para desarrollo
 */
if (defined('WP_DEBUG') && WP_DEBUG && defined('KICK_WP_DEBUG') && KICK_WP_DEBUG) {
    function kick_wp_debug_log($message, $data = null) {
        $log_message = '[Kick WP Debug] ' . $message;
        
        if ($data !== null) {
            $log_message .= ' | Data: ' . print_r($data, true);
        }
        
        error_log($log_message);
    }
    
    // Log de inicialización
    add_action('init', function() {
        kick_wp_debug_log('Plugin inicializado correctamente');
    });
}

/**
 * Verificar y mostrar notificaciones de actualización
 */
function kick_wp_check_version() {
    $current_version = get_option('kick_wp_version', '0.0.0');
    
    if (version_compare($current_version, KICK_WP_VERSION, '<')) {
        // Ejecutar rutinas de actualización si es necesario
        kick_wp_upgrade_routine($current_version, KICK_WP_VERSION);
        
        // Actualizar versión en la base de datos
        update_option('kick_wp_version', KICK_WP_VERSION);
        
        // Mostrar notificación de actualización
        add_action('admin_notices', 'kick_wp_upgrade_notice');
    }
}
add_action('admin_init', 'kick_wp_check_version');

/**
 * Rutinas de actualización
 */
function kick_wp_upgrade_routine($from_version, $to_version) {
    // Limpiar caché en actualizaciones
    global $wpdb;
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $wpdb->esc_like('_transient_kick_wp_') . '%',
            $wpdb->esc_like('_transient_timeout_kick_wp_') . '%'
        )
    );
    
    // Registrar actualización en log
    error_log("Kick WP actualizado de {$from_version} a {$to_version}");
}

/**
 * Mostrar notificación de actualización
 */
function kick_wp_upgrade_notice() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p>
            <strong><?php esc_html_e('Kick WP actualizado!', 'kick-wp'); ?></strong>
            <?php 
            printf(
                esc_html__('Se ha actualizado a la versión %s. Revisa la configuración para ver las nuevas funciones.', 'kick-wp'),
                KICK_WP_VERSION
            );
            ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=kick-wp')); ?>" class="button button-primary" style="margin-left: 10px;">
                <?php esc_html_e('Ver Dashboard', 'kick-wp'); ?>
            </a>
        </p>
    </div>
    <?php
}

/**
 * Verificar compatibilidad con otros plugins
 */
function kick_wp_check_plugin_conflicts() {
    $conflicts = array();
    
    // Verificar conflictos conocidos
    if (is_plugin_active('old-kick-plugin/old-kick-plugin.php')) {
        $conflicts[] = __('Detectado conflicto con "Old Kick Plugin". Se recomienda desactivarlo.', 'kick-wp');
    }
    
    // Mostrar advertencias si hay conflictos
    if (!empty($conflicts)) {
        add_action('admin_notices', function() use ($conflicts) {
            ?>
            <div class="notice notice-warning">
                <p><strong><?php esc_html_e('Kick WP - Conflictos detectados:', 'kick-wp'); ?></strong></p>
                <ul style="margin-left: 20px;">
                    <?php foreach ($conflicts as $conflict): ?>
                        <li><?php echo esc_html($conflict); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php
        });
    }
}
add_action('admin_init', 'kick_wp_check_plugin_conflicts');

/**
 * Agregar nonce a páginas de admin
 */
function kick_wp_admin_nonce() {
    if (isset($_GET['page']) && strpos($_GET['page'], 'kick-wp') !== false) {
        wp_nonce_field('kick_wp_admin_action', 'kick_wp_nonce');
    }
}
add_action('admin_head', 'kick_wp_admin_nonce');