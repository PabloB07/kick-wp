<?php
/**
 *
 * @link       https://blancocl.vercel.app
 * @since      1.0.0
 *
 * @package    Kick_Wp
 * @subpackage Kick_Wp/includes
 */

class Kick_Wp_Activator {

    /**
     * Activar el plugin
     */
    public static function activate() {
        try {
            // Limpiar output buffer previo
            if (ob_get_level()) {
                ob_end_clean();
            }

            // Inicializar opciones por defecto
            self::init_default_options();
            
            // Limpiar caché existente
            self::clear_plugin_cache();
            
            // Registrar versión actual
            update_option('kick_wp_version', KICK_WP_VERSION);
            update_option('kick_wp_activated_time', current_time('mysql'));
            
            // Flush rewrite rules para asegurar que los endpoints funcionen
            flush_rewrite_rules();
            
        } catch (Exception $e) {
            // Log del error
            error_log('Kick WP Activation Error: ' . $e->getMessage());
            
            // Desactivar plugin en caso de error crítico
            if (function_exists('deactivate_plugins')) {
                deactivate_plugins(plugin_basename(KICK_WP_FILE));
            }
            
            wp_die(
                esc_html__('Error al activar Kick WP: ', 'kick-wp') . esc_html($e->getMessage()),
                esc_html__('Error de Activación', 'kick-wp'),
                array('back_link' => true)
            );
        }
    }

    /**
     * Inicializar opciones por defecto
     */
    private static function init_default_options() {
        $default_options = array(
            'kick_wp_cache_duration' => 300,
            'kick_wp_streams_per_page' => 12,
            'kick_wp_layout_style' => 'grid',
            'kick_wp_show_viewer_count' => true,
            'kick_wp_show_categories' => true,
            'kick_wp_auto_refresh' => true,
            'kick_wp_api_timeout' => 30
        );

        foreach ($default_options as $option_name => $default_value) {
            if (false === get_option($option_name)) {
                add_option($option_name, $default_value, '', 'yes');
            }
        }
    }

    /**
     * Limpiar caché del plugin
     */
    private static function clear_plugin_cache() {
        global $wpdb;
        
        try {
            // Eliminar transients del plugin
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                    $wpdb->esc_like('_transient_kick_wp_') . '%',
                    $wpdb->esc_like('_transient_timeout_kick_wp_') . '%'
                )
            );
            
            // Limpiar cache de objetos de WordPress
            if (function_exists('wp_cache_flush')) {
                wp_cache_flush();
            }
            
        } catch (Exception $e) {
            // Log error but don't fail activation
            error_log('Kick WP Cache Clear Error: ' . $e->getMessage());
        }
    }

    /**
     * Crear tabla de logs si es necesario (opcional)
     */
    private static function maybe_create_log_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'kick_wp_logs';
        
        // Verificar si la tabla ya existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                log_type varchar(50) NOT NULL,
                message longtext NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY log_type (log_type),
                KEY created_at (created_at)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    /**
     * Verificar requisitos del sistema
     */
    private static function check_system_requirements() {
        $errors = array();
        
        // Verificar versión de PHP
        if (version_compare(PHP_VERSION, '7.0', '<')) {
            $errors[] = __('Se requiere PHP 7.0 o superior.', 'kick-wp');
        }
        
        // Verificar versión de WordPress
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            $errors[] = __('Se requiere WordPress 5.0 o superior.', 'kick-wp');
        }
        
        // Verificar funciones necesarias
        if (!function_exists('wp_remote_get')) {
            $errors[] = __('La función wp_remote_get no está disponible.', 'kick-wp');
        }
        
        if (!function_exists('json_decode')) {
            $errors[] = __('La extensión JSON de PHP no está disponible.', 'kick-wp');
        }
        
        if (!empty($errors)) {
            throw new Exception(implode(' ', $errors));
        }
        
        return true;
    }
}