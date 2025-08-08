<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://blancocl.vercel.app
 * @since      1.0.0
 *
 * @package    Kick_Wp
 * @subpackage Kick_Wp/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Kick_Wp
 * @subpackage Kick_Wp/admin
 * @author     PabloB07 <pabloblanco0798@gmail.com>
 */
class Kick_Wp_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// Añadir menú de administración
		add_action('admin_menu', array($this, 'add_plugin_admin_menu'));
		
		// Registrar configuraciones
		add_action('admin_init', array($this, 'register_settings'));
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Kick_Wp_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Kick_Wp_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/kick-wp-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/kick-wp-admin.js', array( 'jquery' ), $this->version, false );
	}

	/**
	 * Añade el menú de administración del plugin
	 */
	public function add_plugin_admin_menu() {
		add_menu_page(
			'Kick WP', // Título de la página
			'Kick WP', // Texto del menú
			'manage_options', // Capacidad requerida
			'kick-wp', // Slug del menú
			array($this, 'display_plugin_admin_page'), // Función de callback
			'dashicons-video-alt3', // Icono
			30 // Posición
		);
	}

	/**
	 * Registra las configuraciones del plugin
	 */
	public function register_settings() {
		register_setting(
			'kick_wp_options',
			'kick_wp_cache_duration',
			array(
				'type' => 'integer',
				'default' => 300,
				'sanitize_callback' => 'absint',
			)
		);
	}

	/**
	 * Renderiza la página de administración
	 */
	public function display_plugin_admin_page() {
		// Inicializar la API
		$api = new Kick_Wp_Api();
		
		// Obtener datos
		$featured_streams = $api->get_featured_streams();
		$categories = $api->get_categories();
		
		// Incluir la plantilla
		include plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/kick-wp-admin-display.php';
	}

}
