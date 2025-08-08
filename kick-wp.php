<?php defined('ABSPATH') || exit;
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://blancocl.vercel.app
 * @since             1.0.0
 * @package           Kick_Wp
 *
 * @wordpress-plugin
 * Plugin Name:       KickWP
 * Plugin URI:        https://github.com/PabloB07/KickWP
 * Description:       Wordpress plugin using kick api
 * Version:           1.0.0
 * Author:            PabloB07
 * Author URI:        https://blancocl.vercel.app/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       kick-wp
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define constants
 */
define( 'KICK_WP_VERSION', '1.0.0' );
define( 'KICK_WP_FILE', __FILE__ );
define( 'KICK_WP_PATH', plugin_dir_path( __FILE__ ) );
define( 'KICK_WP_URL', plugin_dir_url( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-kick-wp-activator.php
 */
function activate_kick_wp() {
    // Prevenir salida durante la activación
    ob_start();
    
    try {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-kick-wp-activator.php';
        Kick_Wp_Activator::activate();
    } catch (Exception $e) {
        error_log('Error activando Kick WP: ' . $e->getMessage());
    }
    
    // Limpiar cualquier salida buffereada
    ob_end_clean();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-kick-wp-deactivator.php
 */
function deactivate_kick_wp() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-kick-wp-deactivator.php';
	Kick_Wp_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_kick_wp' );
register_deactivation_hook( __FILE__, 'deactivate_kick_wp' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-kick-wp.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_kick_wp() {

	$plugin = new Kick_Wp();
	$plugin->run();

}
run_kick_wp();
