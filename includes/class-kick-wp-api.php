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
     * Configuración por defecto para las peticiones HTTP
     *
     * @var array
     */
    private $request_args;

    /**
     * URLs base de la API de Kick.com
     *
     * @var array
     */
    private $endpoints = array(
        // Endpoints de canales
        'channel' => 'https://api.kick.com/public/v1/channels/%s',
        'channel_followers' => 'https://api.kick.com/public/v1/channels/%s/followers',
        'channel_clips' => 'https://api.kick.com/public/v1/channels/%s/clips',
        'channel_search' => 'https://api.kick.com/public/v1/search/channels?query=%s',
        
        // Endpoints de livestreams
        'livestream' => 'https://api.kick.com/public/v1/channels/%s/livestream',
        'featured_livestreams' => 'https://api.kick.com/public/v1/channels?order=trending&limit=%d',
        'popular_livestreams' => 'https://api.kick.com/public/v1/channels?order=newest&limit=%d',
        
        // Endpoints de categorías
        'categories' => 'https://api.kick.com/public/v1/categories',
        'category_info' => 'https://api.kick.com/public/v1/categories/%s',
        'category_channels' => 'https://api.kick.com/public/v1/categories/%s/channels',
        'category_search' => 'https://api.kick.com/public/v1/search/categories?query=%s',
        
        // Endpoints de usuarios
        'user_info' => 'https://api.kick.com/public/v1/users/%s',
        'user_channels' => 'https://api.kick.com/public/v1/users/%s/channels'
    );

    /**
     * Headers para la API de Kick
     *
     * @var array
     */
    private $headers;

    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_endpoints'));
        
        // Obtener el token de las opciones de WordPress
        $token = get_option('kick_wp_api_token', '');
        $this->set_auth_token($token);

        // Inicializar headers
        $this->headers = array(
            'Accept' => 'application/json',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Authorization' => 'Bearer null',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'Host' => 'api.kick.com',
            'Origin' => 'https://kick.com',
            'Referer' => 'https://kick.com/',
            'User-Agent' => 'Mozilla/5.0 (compatible; KickWP/1.0; +https://kick.com)',
            'Content-Type' => 'application/json'
        );

        // Inicializar configuración de peticiones HTTP
        $this->request_args = array(
            'headers' => $this->headers,
            'timeout' => 15,
            'sslverify' => true,
            'cookies' => array(),
            'redirection' => 5,
            'decompress' => true,
            'httpversion' => '1.1'
        );
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
    /**
     * Realiza una solicitud HTTP GET a la API de Kick.com
     *
     * @param string $endpoint El endpoint de la API sin la URL base
     * @param array $params Parámetros adicionales para la solicitud
     * @param int $cache_time Tiempo de caché en segundos (0 para deshabilitar)
     * @return array|WP_Error Datos de respuesta o error
     */
    protected function make_request($endpoint_key, $params = array(), $cache_time = 300) {
        // Construir URL completa
        if (isset($this->endpoints[$endpoint_key])) {
            $url = $this->endpoints[$endpoint_key];
            // Si la URL tiene placeholders (%s, %d), aplicar los parámetros en orden
            if (strpos($url, '%') !== false) {
                if (!empty($params['url_params'])) {
                    $url = vsprintf($url, $params['url_params']);
                    unset($params['url_params']);
                }
            }
        } else {
            return new WP_Error('invalid_endpoint', 'Endpoint no válido: ' . $endpoint_key);
        }

        // Agregar parámetros adicionales a la URL
        if (!empty($params)) {
            $url = add_query_arg($params, $url);
        }

        // Verificar caché
        $cache_key = 'kick_wp_' . md5($url);
        if ($cache_time > 0) {
            $cached_data = get_transient($cache_key);
            if ($cached_data !== false) {
                return $cached_data;
            }
        }

        // Realizar solicitud
        $response = wp_remote_get($url, $this->request_args);

        // Procesar respuesta
        $result = $this->handle_response($response);

        // Guardar en caché si es necesario
        if ($cache_time > 0 && !is_wp_error($result)) {
            set_transient($cache_key, $result, $cache_time);
        }

        return $result;
    }

    /**
     * Procesa la respuesta de la API
     *
     * @param array|WP_Error $response Respuesta de wp_remote_request
     * @return array|WP_Error Datos procesados o error
     */
    protected function handle_response($response) {
        if (is_wp_error($response)) {
            error_log('Error en la solicitud a Kick.com API: ' . $response->get_error_message());
            return new WP_Error('api_error', 'Error en la solicitud a Kick.com API', $response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            $error_message = wp_remote_retrieve_response_message($response);
            error_log("Error {$status_code} en Kick.com API: {$error_message}");
            return new WP_Error('api_error', "Error {$status_code} en la API", $error_message);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Error al decodificar respuesta JSON de Kick.com API');
            return new WP_Error('json_error', 'Error al procesar la respuesta de la API');
        }

        return $data;
    }

    /**
     * Obtiene la información de un canal
     *
     * @param string $channelname Nombre del canal
     * @return array|WP_Error Información del canal o error
     */
    public function get_channel_info($channelname) {
        return $this->make_request('channel', array('url_params' => array(urlencode($channelname))));
    }

    /**
     * Obtiene la información de un streamer específico
     * 
     * @param string $username Nombre de usuario del streamer
     * @return array Datos del streamer
     */
    /**
     * Obtiene la información de un streamer
     *
     * @param string $username Nombre de usuario del streamer
     * @return array Datos del streamer
     */
    public function get_streamer($username) {
        if (empty($username)) {
            return array(
                'error' => 'Nombre de usuario no especificado',
                'data' => array()
            );
        }

        // Obtener información del canal
        $channel_result = $this->make_request('channel', array('url_params' => array(urlencode($username))), 300);
        if (is_wp_error($channel_result)) {
            return array(
                'error' => $channel_result->get_error_message(),
                'data' => array()
            );
        }

        // Obtener datos del livestream
        $livestream_result = $this->make_request('livestream', array('url_params' => array(urlencode($username))), 60);
        $is_live = !is_wp_error($livestream_result) && !empty($livestream_result);

        // Formatear los datos
        $formatted_data = array(
            'data' => array(
                array(
                    'username' => $channel_result['user']['username'] ?? '',
                    'channel_url' => 'https://kick.com/' . ($channel_result['slug'] ?? ''),
                    'thumbnail' => $is_live ? 
                        ($livestream_result['thumbnail']['url'] ?? '') : 
                        ($channel_result['user']['profile_img'] ?? ''),
                    'title' => $is_live ? 
                        ($livestream_result['session_title'] ?? '') : 
                        ($channel_result['user']['bio'] ?? ''),
                    'viewer_count' => $is_live ? ($livestream_result['viewer_count'] ?? 0) : 0,
                    'category' => $is_live && isset($livestream_result['categories'][0]) ? 
                        $livestream_result['categories'][0]['name'] : '',
                    'playback_url' => $is_live ? ($livestream_result['playback_url'] ?? '') : '',
                    'is_live' => $is_live,
                    'followers' => $channel_result['followersCount'] ?? 0,
                    'subscription_enabled' => $channel_result['subscription_enabled'] ?? false
                )
            )
        );

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Kick WP - Datos formateados: ' . print_r($formatted_data, true));
        }

        return $formatted_data;
    }

    /**
     * Obtiene los streams destacados
     *
     * @return array|WP_Error Array de streams o WP_Error en caso de error
     */
    /**
     * Obtiene los streams destacados
     *
     * @param array $args Argumentos para filtrar los streams
     * @return array|WP_Error Array de streams o WP_Error en caso de error
     */
    public function get_featured_streams($args = array()) {
        $limit = isset($args['limit']) ? absint($args['limit']) : 12;
        if ($limit < 1) $limit = 12;
        
        $category = isset($args['category']) ? sanitize_text_field($args['category']) : '';
        
        $params = array(
            'order' => 'trending',
            'limit' => $limit
        );

        // Si se especifica una categoría, obtener su ID
        if (!empty($category)) {
            $category_result = $this->make_request('category_search', array('url_params' => array($category)), 3600);
            if (!is_wp_error($category_result) && !empty($category_result['data'])) {
                foreach ($category_result['data'] as $cat) {
                    if (strtolower($cat['name']) === strtolower($category)) {
                        $params['category'] = $cat['id'];
                        break;
                    }
                }
            }
        }

        $result = $this->make_request('featured_livestreams', array('url_params' => array($limit), 'category' => $params['category'] ?? null), 300);

        if (is_wp_error($result)) {
            return array(
                'error' => $result->get_error_message(),
                'data' => array()
            );
        }

        $streams = array();
        if (isset($result['data']) && is_array($result['data'])) {
            foreach ($result['data'] as $stream) {
                if (isset($stream['livestream'])) {
                    $streams[] = array(
                        'username' => $stream['user']['username'] ?? '',
                        'channel_url' => 'https://kick.com/' . ($stream['slug'] ?? ''),
                        'thumbnail' => $stream['livestream']['thumbnail']['url'] ?? '',
                        'title' => $stream['livestream']['session_title'] ?? '',
                        'viewer_count' => $stream['livestream']['viewer_count'] ?? 0,
                        'category' => isset($stream['livestream']['categories'][0]) ? $stream['livestream']['categories'][0]['name'] : '',
                        'playback_url' => $stream['livestream']['playback_url'] ?? '',
                        'is_live' => true,
                        'followers' => $stream['followers_count'] ?? 0,
                        'subscription_enabled' => $stream['subscription_enabled'] ?? false
                    );
                }

                if (count($streams) >= $limit) {
                    break;
                }
            }
        }

        return array(
            'data' => $streams
        );
    }

    /**
     * Obtiene las categorías disponibles
     *
     * @return array|WP_Error Array de categorías o WP_Error en caso de error
     */
    /**
     * Busca canales por nombre
     *
     * @param string $query Término de búsqueda
     * @return array Resultado de la búsqueda
     */
    /**
     * Busca canales por nombre
     *
     * @param string $query Término de búsqueda
     * @return array Resultado de la búsqueda
     */
    public function search_channels($query) {
        if (empty($query)) {
            return array(
                'error' => 'Término de búsqueda no especificado',
                'data' => array()
            );
        }

        $result = $this->make_request('channel_search', array('url_params' => array(urlencode($query))), 60);

        if (is_wp_error($result)) {
            return array(
                'error' => $result->get_error_message(),
                'data' => array()
            );
        }

        $channels = array();
        if (isset($result['data']) && is_array($result['data'])) {
            foreach ($result['data'] as $channel) {
                $channels[] = array(
                    'id' => $channel['id'],
                    'username' => $channel['user']['username'],
                    'slug' => $channel['slug'],
                    'avatar' => $channel['user']['profile_img'],
                    'is_live' => isset($channel['livestream']),
                    'verified' => $channel['user']['verified'] ?? false,
                    'followers' => $channel['followersCount'] ?? 0
                );
            }
        }

        return array(
            'data' => $channels,
            'meta' => $result['meta'] ?? array()
        );
    }

    /**
     * Obtiene todas las categorías disponibles
     *
     * @return array|WP_Error Lista de categorías o error
     */
    /**
     * Establece el token de autenticación para la API
     *
     * @param string $token Token de autenticación
     * @return void
     */
    public function set_auth_token($token) {
        if (!empty($token)) {
            $this->headers['Authorization'] = 'Bearer ' . $token;
            $this->request_args['headers'] = $this->headers;
        }
    }

    public function get_categories() {
        $result = $this->make_request('categories', array(), 3600); // Cache por 1 hora

        if (is_wp_error($result)) {
            return array(
                'error' => $result->get_error_message(),
                'data' => array()
            );
        }

        // Formatear las categorías
        $formatted_categories = array();
        if (isset($result['data']) && is_array($result['data'])) {
            foreach ($result['data'] as $category) {
                $formatted_categories[] = array(
                    'id' => $category['id'] ?? '',
                    'name' => $category['name'] ?? '',
                    'slug' => $category['slug'] ?? '',
                    'viewers' => $category['viewers'] ?? 0,
                    'thumbnail' => isset($category['banner_image']) ? ($category['banner_image']['url'] ?? '') : ''
                );
            }
        }

        return array(
            'data' => $formatted_categories,
            'meta' => $result['meta'] ?? array()
        );
    }
}
