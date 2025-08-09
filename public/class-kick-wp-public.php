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
	 * The API instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Kick_Wp_Api    $api    The API instance.
	 */
	private $api;

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
		$this->api = new Kick_Wp_Api();

		// Registrar el shortcode
		add_shortcode('kick_wp_streams', array($this, 'render_streams_shortcode'));
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		// Enqueue Dashicons
		wp_enqueue_style('dashicons');
		
		// Enqueue our plugin styles
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/kick-wp-public.css', array('dashicons'), $this->version, 'all' );
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
	public function render_streams_shortcode($atts = array()) {
		// Procesar atributos con valores por defecto
		$atts = shortcode_atts(array(
			'count' => get_option('kick_wp_default_stream_count', 4),
			'category' => '',
			'layout' => get_option('kick_wp_layout_style', 'grid'),
			'streamer' => ''
		), $atts, 'kick_wp_streams');

		// Asegurar que count sea un número válido
		$atts['count'] = absint($atts['count']);
		if ($atts['count'] < 1) {
			$atts['count'] = get_option('kick_wp_default_stream_count', 4);
		}

		try {
			// Debug: Mostrar atributos recibidos
			$debug_output = '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc;">';
			$debug_output .= '<p><strong>Debug Info:</strong></p>';
			$debug_output .= '<pre>' . print_r($atts, true) . '</pre>';
			
			// Inicializar la API
			if (!isset($this->api)) {
				$this->api = new Kick_Wp_Api();
				$debug_output .= '<p>API inicializada</p>';
			}
			
			// Si se especifica un streamer, obtener su stream
			if (!empty($atts['streamer'])) {
				$streamer = sanitize_text_field($atts['streamer']);
				$debug_output .= '<p>Buscando streamer: ' . esc_html($streamer) . '</p>';
				
				$response = $this->api->get_streamer($streamer);
				if (isset($response['error'])) {
					throw new Exception($response['error']);
				}
				
				// La respuesta ya viene formateada correctamente del método get_streamer
				$streams = $response;
				
				$debug_output .= '<p>Respuesta de la API:</p>';
				$debug_output .= '<pre>' . print_r($streams, true) . '</pre>';
			} else {
				// Obtener los streams destacados
				$response = $this->api->get_featured_streams(array(
					'limit' => intval($atts['count']),
					'category' => sanitize_text_field($atts['category'])
				));
				
				if (isset($response['error'])) {
					throw new Exception($response['error']);
				}
				
				// La respuesta ya viene formateada correctamente del método get_featured_streams
				$streams = $response;
			}
			$debug_output .= '</div>';
			
			if (defined('WP_DEBUG') && WP_DEBUG) {
				echo $debug_output;
			}
			
			// Incluir la plantilla
			require_once plugin_dir_path(dirname(__FILE__)) . 'public/partials/kick-wp-shortcode.php';
			return kick_wp_display_streams_html($streams, $atts);

		} catch (Exception $e) {
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('Kick WP - Error en shortcode: ' . $e->getMessage());
			}
			return '<p class="kick-wp-error">' . 
				esc_html__('Error al cargar los streams.', 'kick-wp') . 
				'</p>';
		}
	}

}
