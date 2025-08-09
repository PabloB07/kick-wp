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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// Verificar las constantes necesarias
		if (!defined('KICK_WP_PATH')) {
			define('KICK_WP_PATH', plugin_dir_path(dirname(__FILE__)));
		}

		// Inicializar la API
		$this->api = new Kick_Wp_Api();
	}

	/**
	 * Carga las dependencias necesarias para el admin
	 */
	private function load_dependencies() {
		// Cargar la clase API si no está disponible
		if (!class_exists('Kick_Wp_Api')) {
			$api_file = KICK_WP_PATH . 'includes/class-kick-wp-api.php';
			if (file_exists($api_file)) {
				require_once $api_file;
			}
		}
	}

	/**
	 * Define los hooks de administración
	 */
	private function define_admin_hooks() {
		// Añadir menú de administración
		add_action('admin_menu', array($this, 'add_plugin_admin_menu'));
		
		// Registrar configuraciones y opciones
		add_action('admin_init', array($this, 'register_settings'));
		
		// Añadir enlace de configuración en la lista de plugins si es posible
		if (defined('KICK_WP_FILE')) {
			add_filter('plugin_action_links_' . plugin_basename(KICK_WP_FILE), array($this, 'add_settings_link'));
		}
		
		// Inicializar las opciones por defecto
		$this->initialize_options();

		// Registrar scripts y estilos
		add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
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
	 * Inicializa las opciones del plugin
	 */
	private function initialize_options() {
		add_option('kick_wp_cache_duration', 300);
		add_option('kick_wp_streams_per_page', 12);
		add_option('kick_wp_auto_refresh', 1);
	}

	/**
	 * Registra las opciones del plugin
	 */
	public function register_settings() {
		// Configuraciones generales
		register_setting(
			'kick_wp_options',
			'kick_wp_cache_duration',
			array(
				'type' => 'integer',
				'description' => 'Duración del caché en segundos',
				'sanitize_callback' => array($this, 'sanitize_cache_duration'),
				'default' => 300
			)
		);

		register_setting(
			'kick_wp_options',
			'kick_wp_streams_per_page',
			array(
				'type' => 'integer',
				'description' => 'Número de streams por página',
				'sanitize_callback' => array($this, 'sanitize_streams_per_page'),
				'default' => 12
			)
		);

		register_setting(
			'kick_wp_options',
			'kick_wp_auto_refresh',
			array(
				'type' => 'boolean',
				'description' => 'Actualización automática',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default' => true
			)
		);

		// Configuraciones de visualización
		register_setting(
			'kick_wp_options',
			'kick_wp_layout_style',
			array(
				'type' => 'string',
				'description' => 'Estilo de visualización',
				'sanitize_callback' => 'sanitize_text_field',
				'default' => 'grid'
			)
		);

		register_setting(
			'kick_wp_options',
			'kick_wp_show_viewer_count',
			array(
				'type' => 'boolean',
				'description' => 'Mostrar contador de espectadores',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default' => true
			)
		);

		register_setting(
			'kick_wp_options',
			'kick_wp_show_categories',
			array(
				'type' => 'boolean',
				'description' => 'Mostrar categorías en streams',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default' => true
			)
		);

		// Configuraciones de shortcode
		register_setting(
			'kick_wp_options',
			'kick_wp_default_stream_count',
			array(
				'type' => 'integer',
				'description' => 'Número predeterminado de streams en shortcode',
				'sanitize_callback' => array($this, 'sanitize_stream_count'),
				'default' => 4
			)
		);

		// Registrar sección
		add_settings_section(
			'kick_wp_general_section',
			__('Configuración General', 'kick-wp'),
			array($this, 'render_settings_section'),
			'kick_wp_settings'
		);
	}

	/**
	 * Sanitiza la duración del caché
	 */
	public function sanitize_cache_duration($value) {
		$value = absint($value);
		return $value < 60 ? 60 : $value;
	}

	/**
	 * Sanitiza el número de streams por página
	 */
	public function sanitize_streams_per_page($value) {
		$value = absint($value);
		return min(max($value, 4), 24);
	}

	/**
	 * Renderiza la descripción de la sección de configuración
	 */
	public function render_settings_section() {
		echo '<p>' . esc_html__('Configura las opciones generales del plugin Kick WP.', 'kick-wp') . '</p>';
	}

	/**
	 * Obtiene los datos para la página de administración
	 */
	private function get_admin_data() {
		try {
			return array(
				'featured_streams' => $this->api->get_featured_streams(),
				'categories' => $this->api->get_categories()
			);
		} catch (Exception $e) {
			return array(
				'error' => $e->getMessage(),
				'featured_streams' => array(),
				'categories' => array()
			);
		}
	}

	/**
	 * Añade el menú de administración
	 */
	public function add_plugin_admin_menu() {
		// Menú principal
		add_menu_page(
			__('Kick WP', 'kick-wp'),
			__('Kick WP', 'kick-wp'),
			'manage_options',
			$this->plugin_name,
			array($this, 'display_plugin_admin_page'),
			'dashicons-video-alt3',
			30
		);

		// Submenús
		add_submenu_page(
			$this->plugin_name,
			__('Streams Destacados', 'kick-wp'),
			__('Streams Destacados', 'kick-wp'),
			'manage_options',
			$this->plugin_name,
			array($this, 'display_plugin_admin_page')
		);

		add_submenu_page(
			$this->plugin_name,
			__('Categorías', 'kick-wp'),
			__('Categorías', 'kick-wp'),
			'manage_options',
			$this->plugin_name . '-categories',
			array($this, 'display_categories_page')
		);

		add_submenu_page(
			$this->plugin_name,
			__('Configuración', 'kick-wp'),
			__('Configuración', 'kick-wp'),
			'manage_options',
			$this->plugin_name . '-settings',
			array($this, 'display_settings_page')
		);
	}

	/**
	 * Añade el enlace de configuración en la lista de plugins
	 */
	public function add_settings_link($links) {
		$settings_link = '<a href="admin.php?page=' . $this->plugin_name . '">' . __('Configuración', 'kick-wp') . '</a>';
		array_unshift($links, $settings_link);
		return $links;
	}

	/**
	 * Muestra la página de administración
	 */
	/**
	 * Muestra la página principal de administración
	 */
	public function display_plugin_admin_page() {
		try {
			if (!isset($this->api)) {
				$this->api = new Kick_Wp_Api();
			}

			// Obtener datos y asegurar la estructura correcta
			$featured_streams = $this->api->get_featured_streams();
			$categories = $this->api->get_categories();

			// Asegurar que los datos tengan la estructura correcta
			$featured_streams = is_array($featured_streams) ? $featured_streams : array('data' => array());
			$categories = is_array($categories) ? $categories : array('data' => array());

			// Asegurar que 'data' existe en ambos arrays
			if (!isset($featured_streams['data'])) {
				$featured_streams['data'] = array();
			}
			if (!isset($categories['data'])) {
				$categories['data'] = array();
			}

			// Verificar si hay errores
			if (isset($featured_streams['error'])) {
				add_settings_error(
					'kick_wp_messages',
					'kick_wp_featured_error',
					$featured_streams['error'],
					'error'
				);
			}

			if (isset($categories['error'])) {
				add_settings_error(
					'kick_wp_messages',
					'kick_wp_categories_error',
					$categories['error'],
					'error'
				);
			}

			// Incluir la plantilla
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/kick-wp-admin-display.php';
		} catch (Exception $e) {
			echo '<div class="error"><p>' . 
				esc_html__('Error al cargar la página de administración: ', 'kick-wp') . 
				esc_html($e->getMessage()) . '</p></div>';
		}
	}

	/**
	 * Muestra la página de categorías
	 */
	public function display_categories_page() {
		try {
			if (!isset($this->api)) {
				$this->api = new Kick_Wp_Api();
			}

			// Obtener categorías
			$categories = $this->api->get_categories();
			$categories = is_array($categories) ? $categories : array('data' => array());

			if (!isset($categories['data'])) {
				$categories['data'] = array();
			}

			// Verificar errores
			if (isset($categories['error'])) {
				add_settings_error(
					'kick_wp_messages',
					'kick_wp_categories_error',
					$categories['error'],
					'error'
				);
			}

			// Incluir la plantilla
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/kick-wp-categories-display.php';
		} catch (Exception $e) {
			echo '<div class="error"><p>' . 
				esc_html__('Error al cargar la página de categorías: ', 'kick-wp') . 
				esc_html($e->getMessage()) . '</p></div>';
		}
	}

	/**
	 * Muestra la página de configuración
	 */
	public function display_settings_page() {
		// Incluir la plantilla
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/kick-wp-settings-display.php';
	}

}
