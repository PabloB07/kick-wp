<?php
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
 * This class defines all code necessary to run during the plugin's activation.
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
		// Inicializar opciones por defecto si no existen
		if (!get_option('kick_wp_cache_duration')) {
			add_option('kick_wp_cache_duration', 300);
		}
		if (!get_option('kick_wp_streams_per_page')) {
			add_option('kick_wp_streams_per_page', 12);
		}
		if (!get_option('kick_wp_auto_refresh')) {
			add_option('kick_wp_auto_refresh', 1);
		}

		// Crear o actualizar la tabla de caché si es necesario
		self::create_cache_table();
	}

	/**
	 * Crea la tabla de caché en la base de datos
	 */
	private static function create_cache_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'kick_wp_cache';

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			cache_key varchar(255) NOT NULL,
			cache_value longtext NOT NULL,
			expiration datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY cache_key (cache_key),
			KEY expiration (expiration)
		) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}

}
