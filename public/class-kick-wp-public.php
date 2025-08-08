<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://blancocl.vercel.app
 * @since      1.0.0
 *
 * @package    Kick_Wp
 * @subpackage Kick_Wp/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Kick_Wp
 * @subpackage Kick_Wp/public
 * @author     PabloB07 <pabloblanco0798@gmail.com>
 */
class Kick_Wp_Public {

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// Registrar el shortcode
		add_shortcode('kick_wp_streams', array($this, 'render_streams_shortcode'));
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/kick-wp-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/kick-wp-public.js', array( 'jquery' ), $this->version, false );
	}

	/**
	 * Renderiza el shortcode de streams
	 *
	 * @param array $atts Atributos del shortcode
	 * @return string HTML del shortcode
	 */
	public function render_streams_shortcode($atts) {
		// Inicializar la API
		$api = new Kick_Wp_Api();
		
		// Obtener los streams destacados
		$featured_streams = $api->get_featured_streams();
		
		// Iniciar el buffer de salida
		ob_start();
		
		// Incluir la plantilla
		include plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/kick-wp-public-display.php';
		
		// Retornar el contenido del buffer
		return ob_get_clean();
	}

}
