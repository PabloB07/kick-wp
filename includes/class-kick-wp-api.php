<?php

/**
 * Manejo de la API de Kick.com
 *
 * @link       https://blancocl.vercel.app
 * @since      1.0.0
 *
 * @package    Kick_Wp
 * @subpackage Kick_Wp/includes
 */

class Kick_Wp_Api {
    /**
     * URL base de la API de Kick.com
     *
     * @var string
     */
    private $api_base_url = 'https://kick.com/api/v2';

    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }

    /**
     * Registra los endpoints de la API
     */
    public function register_endpoints() {
        register_rest_route('kick-wp/v1', '/channels/(?P<channel_name>[a-zA-Z0-9-]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_channel_info'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route('kick-wp/v1', '/featured', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_featured_streams'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route('kick-wp/v1', '/categories', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_categories'),
            'permission_callback' => '__return_true'
        ));
    }

    /**
     * Obtiene información de un canal específico
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_channel_info($request) {
        $channel_name = $request['channel_name'];
        $response = wp_remote_get($this->api_base_url . '/channels/' . $channel_name);
        
        if (is_wp_error($response)) {
            return new WP_REST_Response(array(
                'error' => 'Error al obtener información del canal'
            ), 500);
        }

        $body = wp_remote_retrieve_body($response);
        return new WP_REST_Response(json_decode($body), 200);
    }

    /**
     * Obtiene los streams destacados
     *
     * @return WP_REST_Response
     */
    public function get_featured_streams() {
        $response = wp_remote_get($this->api_base_url . '/channels/featured');
        
        if (is_wp_error($response)) {
            return new WP_REST_Response(array(
                'error' => 'Error al obtener streams destacados'
            ), 500);
        }

        $body = wp_remote_retrieve_body($response);
        return new WP_REST_Response(json_decode($body), 200);
    }

    /**
     * Obtiene las categorías disponibles
     *
     * @return WP_REST_Response
     */
    public function get_categories() {
        $response = wp_remote_get($this->api_base_url . '/categories');
        
        if (is_wp_error($response)) {
            return new WP_REST_Response(array(
                'error' => 'Error al obtener categorías'
            ), 500);
        }

        $body = wp_remote_retrieve_body($response);
        return new WP_REST_Response(json_decode($body), 200);
    }
}
