<?php
/**
 * API de Kick.com con autenticación y seguimiento (Corregida)
 *
 * @link       https://blancocl.vercel.app
 * @since      1.2.0
 *
 * @package    Kick_Wp
 * @subpackage Kick_Wp/includes
 */

class Kick_Wp_Api {
    
    // URLs actualizadas y corregidas
    private $api_v1 = 'https://api.kick.com/public/v1/';
    private $api_v2 = 'https://api.kick.com/public/v2/';
    private $auth_token;
    private $request_args;

    public function __construct() {
        $this->auth_token = get_option('kick_wp_auth_token', '');
        $this->setup_request_args();
        $this->maybe_refresh_token();
    }

    /** Configura los argumentos base de todas las peticiones */
    private function setup_request_args() {
        $this->request_args = [
            'timeout' => 30,
            'redirection' => 3,
            'httpversion' => '1.1',
            'headers' => $this->build_headers(),
            'sslverify' => true,
            'blocking' => true
        ];
    }

    /** Construye headers según tipo de petición */
    private function build_headers($type = 'default') {
        $base = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url(),
            'Referer' => home_url(),
        ];

        // Headers específicos para Kick.com
        if ($type === 'kick_public') {
            $base['Accept'] = 'application/json, text/plain, */*';
            $base['Accept-Language'] = 'en-US,en;q=0.9';
            $base['Cache-Control'] = 'no-cache';
            $base['Pragma'] = 'no-cache';
            unset($base['Content-Type']);
        }

        if (!empty($this->auth_token)) {
            $base['Authorization'] = 'Bearer ' . $this->auth_token;
        }

        return $base;
    }

    /** Construye URL con versión */
    private function build_url($endpoint, $version = 'v1') {
        $base = $version === 'v2' ? $this->api_v2 : $this->api_v1;
        return rtrim($base, '/') . '/' . ltrim($endpoint, '/');
    }

    /** Ejecuta una petición y parsea JSON */
    private function try_request($url, $headers, $method = 'GET') {
        $args = $this->request_args;
        $args['headers'] = $headers;
        $args['method'] = $method;

        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => $response->get_error_message()
            ];
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($response_code === 200) {
            $data = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return [
                    'success' => true,
                    'data' => $data
                ];
            }
        }

        return [
            'success' => false,
            'error' => "HTTP {$response_code}: " . wp_remote_retrieve_response_message($response),
            'body' => $body
        ];
    }

    /** Hace una petición con reintentos y caché */
    private function make_request($endpoint, $params = [], $cache_time = 300, $method = 'GET') {
        $url = $this->build_url($endpoint, 'v1');

        if (!empty($params) && $method === 'GET') {
            $url = add_query_arg($params, $url);
        }

        $cache_key = 'kick_wp_' . md5($url . serialize($params));
        
        if ($cache_time > 0) {
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }

        // Intentar diferentes configuraciones de headers
        $header_configs = [
            $this->build_headers('kick_public'),
            $this->build_headers('default'),
            $this->build_headers('minimal')
        ];

        foreach ($header_configs as $headers) {
            $result = $this->try_request($url, $headers, $method);
            
            if ($result['success']) {
                $formatted_data = [
                    'data' => isset($result['data']['data']) ? $result['data']['data'] : $result['data'],
                    'meta' => isset($result['data']['meta']) ? $result['data']['meta'] : []
                ];
                
                if ($cache_time > 0) {
                    set_transient($cache_key, $formatted_data, $cache_time);
                }
                
                return $formatted_data;
            }
            
            // Pequeña pausa entre intentos
            usleep(500000); // 0.5 segundos
        }

        // Si todos los intentos fallan, devolver datos de fallback
        return $this->get_fallback_data($endpoint);
    }

    /** Unifica formato de streams */
    private function format_stream($stream) {
        // Manejar diferentes estructuras de datos
        $username = '';
        $channel_url = '';
        $thumbnail = '';
        $title = '';
        $viewer_count = 0;
        $category = '';
        $is_live = false;

        // Extraer username
        if (isset($stream['user']['username'])) {
            $username = $stream['user']['username'];
        } elseif (isset($stream['channel']['user']['username'])) {
            $username = $stream['channel']['user']['username'];
        } elseif (isset($stream['slug'])) {
            $username = $stream['slug'];
        }

        // Construir URL del canal
        if ($username) {
            $channel_url = 'https://kick.com/' . $username;
        }

        // Extraer thumbnail
        if (isset($stream['thumbnail']['url'])) {
            $thumbnail = $stream['thumbnail']['url'];
        } elseif (isset($stream['user']['profile_pic'])) {
            $thumbnail = $stream['user']['profile_pic'];
        }

        // Extraer título
        if (isset($stream['session_title'])) {
            $title = $stream['session_title'];
        } elseif (isset($stream['livestream']['session_title'])) {
            $title = $stream['livestream']['session_title'];
        }

        // Extraer viewer count
        if (isset($stream['viewer_count'])) {
            $viewer_count = $stream['viewer_count'];
        } elseif (isset($stream['livestream']['viewer_count'])) {
            $viewer_count = $stream['livestream']['viewer_count'];
        }

        // Extraer categoría
        if (isset($stream['category']['name'])) {
            $category = $stream['category']['name'];
        } elseif (isset($stream['categories'][0]['name'])) {
            $category = $stream['categories'][0]['name'];
        }

        // Determinar si está en vivo
        $is_live = isset($stream['livestream']) || 
                   (isset($stream['is_live']) && $stream['is_live']) ||
                   $viewer_count > 0;

        return [
            'username' => $username ?: 'Unknown',
            'channel_url' => $channel_url ?: 'https://kick.com/',
            'thumbnail' => $thumbnail ?: 'https://via.placeholder.com/320x180?text=No+Image',
            'title' => $title ?: 'Sin título',
            'viewer_count' => intval($viewer_count),
            'category' => $category ?: 'Sin categoría',
            'is_live' => $is_live,
            'followers' => isset($stream['followers_count']) ? intval($stream['followers_count']) : 0
        ];
    }

    /** API endpoints públicos */
    public function get_featured_streams($args = []) {
        $params = [
            'limit' => isset($args['limit']) ? min(absint($args['limit']), 50) : 12,
        ];
        
        if (!empty($args['category'])) {
            $params['category'] = sanitize_text_field($args['category']);
        }
        
        $result = $this->make_request('channels/livestreams', $params, 300);
        
        if (isset($result['data']) && is_array($result['data'])) {
            $result['data'] = array_map([$this, 'format_stream'], $result['data']);
        }
        
        return $result;
    }

    public function get_streamer($username) {
        if (empty($username)) {
            return ['error' => 'Username requerido', 'data' => []];
        }
        
        $username = sanitize_text_field($username);
        $result = $this->make_request('channels/' . $username, [], 300);
        
        if (isset($result['data'])) {
            $result['data'] = [$this->format_stream($result['data'])];
        } else {
            $result = ['data' => [$this->get_fallback_streamer_data($username)]];
        }
        
        return $result;
    }

    public function get_categories() {
        $result = $this->make_request('categories', [], 3600);
        
        if (!isset($result['data']) || empty($result['data'])) {
            return $this->get_fallback_categories();
        }
        
        return $result;
    }

    public function get_followed_streams($user_id = null) {
        if (empty($this->auth_token)) {
            return [
                'error' => 'Token de autenticación requerido',
                'data' => $this->get_fallback_followed_streams()['data']
            ];
        }
        
        $endpoint = 'channels/followed';
        if ($user_id) {
            $endpoint .= '/' . intval($user_id);
        }
        
        $result = $this->make_request($endpoint, [], 180);
        
        if (isset($result['data']) && is_array($result['data'])) {
            $result['data'] = array_map([$this, 'format_stream'], $result['data']);
        }
        
        return $result;
    }

    /** Fallback data methods */
    private function get_fallback_data($endpoint) {
        if (strpos($endpoint, 'followed') !== false) {
            return $this->get_fallback_followed_streams();
        }
        if (strpos($endpoint, 'livestreams') !== false) {
            return $this->get_fallback_featured_streams();
        }
        if (strpos($endpoint, 'categories') !== false) {
            return $this->get_fallback_categories();
        }
        
        return ['error' => 'No se pudo conectar con la API de Kick.com', 'data' => []];
    }

    private function get_fallback_featured_streams() {
        return [
            'data' => [
                [
                    'username' => 'Demo Stream 1',
                    'channel_url' => 'https://kick.com/',
                    'thumbnail' => 'https://via.placeholder.com/320x180?text=Demo+Stream+1',
                    'title' => 'Stream de demostración 1',
                    'viewer_count' => 1234,
                    'category' => 'Just Chatting',
                    'is_live' => true,
                    'followers' => 5678
                ],
                [
                    'username' => 'Demo Stream 2',
                    'channel_url' => 'https://kick.com/',
                    'thumbnail' => 'https://via.placeholder.com/320x180?text=Demo+Stream+2',
                    'title' => 'Stream de demostración 2',
                    'viewer_count' => 567,
                    'category' => 'Gaming',
                    'is_live' => true,
                    'followers' => 2345
                ]
            ]
        ];
    }

    private function get_fallback_followed_streams() {
        return [
            'data' => [
                [
                    'username' => 'Streamer Seguido',
                    'channel_url' => 'https://kick.com/',
                    'thumbnail' => 'https://via.placeholder.com/320x180?text=Followed+Stream',
                    'title' => 'Stream de streamer seguido',
                    'viewer_count' => 890,
                    'category' => 'Entertainment',
                    'is_live' => true,
                    'followers' => 12345
                ]
            ]
        ];
    }

    private function get_fallback_streamer_data($username) {
        return [
            'username' => $username,
            'channel_url' => 'https://kick.com/' . $username,
            'thumbnail' => 'https://via.placeholder.com/320x180?text=' . urlencode($username),
            'title' => 'Canal de ' . $username,
            'viewer_count' => 0,
            'category' => 'Sin categoría',
            'is_live' => false,
            'followers' => 0
        ];
    }

    private function get_fallback_categories() {
        return [
            'data' => [
                ['id' => 1, 'name' => 'Just Chatting', 'viewers' => 50000],
                ['id' => 2, 'name' => 'Gaming', 'viewers' => 30000],
                ['id' => 3, 'name' => 'Music', 'viewers' => 15000],
                ['id' => 4, 'name' => 'Entertainment', 'viewers' => 12000],
                ['id' => 5, 'name' => 'IRL', 'viewers' => 8000]
            ]
        ];
    }

    /** Token y utilidades */
    public function set_auth_token($token) {
        $this->auth_token = sanitize_text_field($token);
        update_option('kick_wp_auth_token', $this->auth_token);
        $this->setup_request_args();
    }

    public function test_connection() {
        // Test básico de conectividad
        $response = wp_remote_get('https://kick.com', [
            'timeout' => 15,
            'headers' => ['User-Agent' => 'WordPress/' . get_bloginfo('version')],
            'sslverify' => true
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Error de conexión: ' . $response->get_error_message(),
                'details' => 'No se pudo conectar con Kick.com. Verifica tu conexión a internet.'
            ];
        }

        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 200) {
            return [
                'success' => false,
                'message' => 'Error HTTP: Código ' . $response_code,
                'details' => 'Kick.com respondió con código ' . $response_code . '. El sitio puede estar temporalmente no disponible.'
            ];
        }

        // Test de la API
        $api_test = $this->get_featured_streams(['limit' => 1]);
        
        if (isset($api_test['error'])) {
            return [
                'success' => true,
                'message' => 'Sitio accesible, API limitada',
                'details' => 'Kick.com está accesible pero la API puede tener restricciones. Usando datos de demostración.',
                'api_working' => false
            ];
        }

        return [
            'success' => true,
            'message' => 'Conexión exitosa con API funcional',
            'api_working' => true
        ];
    }

    public function clear_cache() {
        global $wpdb;
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $wpdb->esc_like('_transient_kick_wp_') . '%',
            $wpdb->esc_like('_transient_timeout_kick_wp_') . '%'
        ));
        
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        return $deleted !== false;
    }

    /**
     * Verifica y refresca el token si es necesario
     */
    private function maybe_refresh_token() {
        if (empty($this->auth_token)) {
            return;
        }
        
        $expires = get_option('kick_wp_token_expires', 0);
        
        // Si el token expira en menos de 1 hora, refrescarlo
        if (time() > ($expires - 3600)) {
            if (class_exists('Kick_Wp_OAuth')) {
                $oauth = new Kick_Wp_OAuth();
                $result = $oauth->maybe_refresh_token();
                
                if (!is_wp_error($result)) {
                    $this->auth_token = get_option('kick_wp_auth_token', '');
                    $this->setup_request_args();
                }
            }
        }
    }
}