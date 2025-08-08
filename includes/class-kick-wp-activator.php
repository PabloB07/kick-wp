<?php defined('ABSPATH') || exit;
/**
 * Fired during plugin activation
 *
 * @link       https://blancocl.vercel.app
 * @since      1.0.0
 *
 * @package    Kick_Wp
 * @subpackage Kick_Wp/includes
 */

/**
 * Fired during plugin activation.
 *
 * @since      1.0.0
 * @package    Kick_Wp
 * @subpackage Kick_Wp/includes
 * @author     PabloB07 <pabloblanco0798@gmail.com>
 */
class Kick_Wp_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;

		if (ob_get_level() > 0) {
			for ($i = 0; $i < ob_get_level(); $i++) {
				ob_end_clean();
			}
		}

		ob_start();

		try {
			// Limpiar caché y transients antes de la activación
			wp_cache_flush();
			self::clear_transients();

			// Inicializar opciones por defecto
			self::init_options();

			// Crear tabla de caché
			if (!self::create_cache_table()) {
				throw new Exception('Error al crear la tabla de caché');
			}

			// Registrar activación exitosa
			update_option('kick_wp_activated', current_time('mysql'));
			update_option('kick_wp_version', KICK_WP_VERSION);

		} catch (Exception $e) {
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('Kick WP - Error de activación: ' . $e->getMessage());
			}
			
			// Asegurar que el buffer está limpio
			while (ob_get_level() > 0) {
				ob_end_clean();
			}
			
			// Desactivar el plugin si hay un error crítico
			deactivate_plugins(plugin_basename(KICK_WP_FILE));
			wp_die(
				esc_html('Error activando Kick WP: ' . $e->getMessage()),
				'Error de Activación',
				array('back_link' => true)
			);
		}

		if (ob_get_length() > 0) {
			ob_end_clean();
		}
	}

	/**
	 * Inicializa las opciones del plugin
	 */
	private static function init_options() {
		$default_options = array(
			'kick_wp_cache_duration' => 300,
			'kick_wp_streams_per_page' => 12,
			'kick_wp_auto_refresh' => 1,
			'kick_wp_last_error' => '',
			'kick_wp_version' => KICK_WP_VERSION
		);

		foreach ($default_options as $option => $value) {
			if (false === get_option($option)) {
				add_option($option, $value, '', 'no');
			}
		}

		// Limpiar cualquier error anterior
		update_option('kick_wp_last_error', '', 'no');
	}

	/**
	 * Limpia los transients existentes
	 */
	private static function clear_transients() {
		global $wpdb;
		
		// Usar prepared statements para mayor seguridad
		$wpdb->query($wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like('_transient_kick_wp_') . '%',
			$wpdb->esc_like('_transient_timeout_kick_wp_') . '%'
		));
		
		// Limpiar caché de objetos relacionados
		wp_cache_flush();
	}

	/**
	 * Crea la tabla de caché en la base de datos
	 */
	private static function create_cache_table() {
		global $wpdb;

		try {
			$charset_collate = $wpdb->get_charset_collate();
			$table_name = $wpdb->prefix . 'kick_wp_cache';

			$sql = "CREATE TABLE IF NOT EXISTS $table_name (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				cache_key varchar(255) NOT NULL,
				cache_value longtext NOT NULL,
				expiration datetime NOT NULL,
				created_at timestamp DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				KEY cache_key (cache_key),
				KEY expiration (expiration)
			) $charset_collate;";

			// Desactivar notificaciones durante la creación de la tabla
			$error_reporting = error_reporting();
			error_reporting(0);

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);

			// Restaurar nivel de notificación
			error_reporting($error_reporting);

		} catch (Exception $e) {
			error_log('Error creando tabla de caché Kick WP: ' . $e->getMessage());
			return false;
		}

		return true;
	}

}
