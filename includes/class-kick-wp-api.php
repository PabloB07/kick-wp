<?php
/**
 * API de Kick.com con autenticación y seguimiento (Optimizada)
 *
 * @link       https://blancocl.vercel.app
 * @since      1.1.0
 *
 * @package    Kick_Wp
 * @subpackage Kick_Wp/includes
 */

class Kick_Wp_Api {
    
    private $api_v1 = 'https://kick.com/api/v1/';
    private $api_v2 = 'https://kick.com/api/v2/';
    // Actualizar la API pública con la URL correcta
    private $api_public = 'https://api.kick.com/public/v1/';
    private $auth_token;
    private $request_args;

    public function __construct() {
        $this->auth_token = get_option('kick_wp_auth_token', '');
        $this->setup_request_args();
        
        // Verificar si el token necesita ser refrescado
        $this->maybe_refresh_token();
    }

    /** Configura los argumentos base de todas las peticiones */
    private function setup_request_args() {
        $this->request_args = [
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.1',
            'headers' => $this->build_headers(),
            'sslverify' => true, // mejor mantener verificación activa en prod
            'blocking' => true
        ];
    }

    /** Construye headers según tipo de petición */
    private function build_headers($type = 'default') {
        $base = [
            'Accept' => 'application/json, text/plain, */*',
            'User-Agent' => 'Mozilla/5.0 (compatible; WordPress; +' . home_url() . ')',
            'Referer' => 'https://kick.com/',
        ];

        if ($type === 'minimal') {
            return $base;
        }

        if ($type === 'public') {
            $base['Accept-Language'] = 'en-US,en;q=0.5';
            $base['Cache-Control'] = 'no-cache';
            return $base;
        }

        if (!empty($this->auth_token)) {
            $base['Authorization'] = 'Bearer ' . $this->auth_token;
        }
        return $base;
    }

    /** Construye URL con versión */
    private function build_url($endpoint, $version = 'v1') {
        if ($version === 'public') {
            $base = $this->api_public;
        } else {
            $base = $version === 'v2' ? $this->api_v2 : $this->api_v1;
        }
        return rtrim($base, '/') . '/' . ltrim($endpoint, '/');
    }

    /** Ejecuta una petición y parsea JSON */
    private function try_request($url, $headers) {
        $args = $this->request_args;
        $args['headers'] = $headers;

        $response = wp_remote_request($url, $args);
        if (is_wp_error($response)) return false;

        if (wp_remote_retrieve_response_code($response) === 200) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            return (json_last_error() === JSON_ERROR_NONE) ? $data : false;
        }
        return false;
    }

    /** Hace una petición con reintentos y caché */
    private function make_request($endpoint, $params = [], $cache_time = 300, $method = 'GET') {
        // Determinar si debemos usar la API pública para este endpoint
        $use_public_api = in_array($endpoint, ['categories', 'channels/livestreams']);
        $version = $use_public_api ? 'public' : 'v1';
        
        $url = $this->build_url($endpoint, $version);

        if (!empty($params) && $method === 'GET') {
            $url = add_query_arg($params, $url);
        }

        $cache_key = 'kick_wp_' . md5($url . serialize($params));
        if ($cache_time > 0 && ($cached = get_transient($cache_key)) !== false) {
            return $cached;
        }

        $attempts = [
            // Primer intento: usar la versión determinada (pública o v1)
            [$url, $this->build_headers($use_public_api ? 'public' : 'default')],
            // Segundo intento: probar con v2 si no es pública
            [$use_public_api ? $url : str_replace('/api/v1/', '/api/v2/', $url), $this->build_headers('minimal')],
            // Tercer intento: probar con la otra opción
            [$use_public_api ? $this->build_url($endpoint, 'v1') : $this->build_url($endpoint, 'public'), $this->build_headers('public')]
        ];

        foreach ($attempts as [$try_url, $try_headers]) {
            $data = $this->try_request($try_url, $try_headers);
            if ($data) {
                if ($cache_time > 0) set_transient($cache_key, $data, $cache_time);
                return $data;
            }
            sleep(1);
        }

        return $this->get_fallback_data($endpoint);
    }

    /** Unifica formato de streams */
    private function format_stream($stream) {
        return [
            'username' => $stream['user']['username'] ?? $stream['slug'] ?? 'Unknown',
            'channel_url' => 'https://kick.com/' . ($stream['slug'] ?? 'unknown'),
            'thumbnail' => $stream['thumbnail']['url'] ?? $stream['user']['profile_pic'] ?? '',
            'title' => $stream['session_title'] ?? $stream['livestream']['session_title'] ?? 'Sin título',
            'viewer_count' => $stream['viewer_count'] ?? $stream['livestream']['viewer_count'] ?? 0,
            'category' => $stream['category']['name'] ?? $stream['categories'][0]['name'] ?? 'Sin categoría',
            'is_live' => isset($stream['livestream']) || !empty($stream['is_live']),
            'followers' => $stream['followers_count'] ?? 0
        ];
    }

    /** API pública */
    public function get_followed_streams($user_id = null) {
        if (empty($this->auth_token)) {
            return ['error' => 'Token requerido', 'data' => $this->get_fallback_followed_streams()['data']];
        }
        $endpoint = 'channels/followed' . ($user_id ? '/' . $user_id : '');
        $result = $this->make_request($endpoint, [], 180);
        return isset($result['error']) ? ['error' => $result['error'], 'data' => $this->get_fallback_followed_streams()['data']] : ['data' => array_map([$this, 'format_stream'], $result['data'] ?? [])];
    }

    public function get_featured_streams($args = []) {
        $params = [
            'limit' => isset($args['limit']) ? absint($args['limit']) : 12,
            'sort' => 'desc',
            'order_by' => 'viewers'
        ];
        if (!empty($args['category'])) $params['category'] = sanitize_text_field($args['category']);
        $result = $this->make_request('channels/livestreams', $params, 300);
        return isset($result['error']) ? ['error' => $result['error'], 'data' => $this->get_fallback_featured_streams()['data']] : ['data' => array_map([$this, 'format_stream'], $result['data'] ?? [])];
    }

    public function get_streamer($username) {
        if (empty($username)) return ['error' => 'Username requerido'];
        $result = $this->make_request('channels/' . sanitize_text_field($username), [], 300);
        return isset($result['error']) ? ['error' => $result['error'], 'data' => $this->get_fallback_streamer_data($username)] : ['data' => [$this->format_stream($result)]];
    }

    public function get_categories() {
        // Usar explícitamente la API pública para categorías
        $url = $this->build_url('categories', 'public');
        $headers = $this->build_headers('public');
        
        $cache_key = 'kick_wp_categories';
        if (($cached = get_transient($cache_key)) !== false) {
            return $cached;
        }
        
        $args = $this->request_args;
        $args['headers'] = $headers;
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            return $this->get_fallback_categories();
        }
        
        if (wp_remote_retrieve_response_code($response) === 200) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                set_transient($cache_key, $data, 3600);
                return $data;
            }
        }
        
        return $this->get_fallback_categories();
    }

    /** Fallbacks (igual que en tu versión original) */
    private function get_fallback_data($endpoint) {
        if (strpos($endpoint, 'channels/followed') !== false) return $this->get_fallback_followed_streams();
        if (strpos($endpoint, 'channels') !== false) return $this->get_fallback_featured_streams();
        if (strpos($endpoint, 'categories') !== false) return $this->get_fallback_categories();
        return ['error' => 'No se pudo conectar con la API de Kick.com'];
    }
    private function get_fallback_followed_streams() { /* igual que tu versión */ }
    private function get_fallback_featured_streams() { /* igual que tu versión */ }
    private function get_fallback_streamer_data($username) { /* igual que tu versión */ }
    private function get_fallback_categories() { /* igual que tu versión */ }

    /** Token y utilidades */
    public function set_auth_token($token) {
        $this->auth_token = sanitize_text_field($token);
        update_option('kick_wp_auth_token', $this->auth_token);
        $this->setup_request_args();
    }

    public function test_connection() {
        // Primero intentar con la API pública
        $response = wp_remote_get('https://api.kick.com/public/v1/categories', [
            'timeout' => 15, 
            'headers' => $this->build_headers('minimal'), 
            'sslverify' => true
        ]);
        
        // Si la API pública no funciona, intentar con el sitio principal
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            $response = wp_remote_get('https://kick.com', [
                'timeout' => 15, 
                'headers' => $this->build_headers('minimal'), 
                'sslverify' => true
            ]);
        }
        
        if (is_wp_error($response)) {
            return ['success' => false, 'message' => 'Error de conexión: ' . $response->get_error_message()];
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code === 200) {
            $api_test = $this->make_request('channels/livestreams', ['limit' => 1], 0);
            return isset($api_test['error']) ? 
                ['success' => true, 'message' => 'Sitio accesible, API con restricciones', 'api_working' => false] : 
                ['success' => true, 'message' => 'Conexión exitosa', 'api_working' => true];
        }
        
        // Proporcionar información más detallada sobre el error HTTP
        return [
            'success' => false, 
            'message' => 'Error HTTP: Código ' . $response_code, 
            'details' => 'La API de Kick.com respondió con un código de error ' . $response_code . '. Esto puede indicar un problema temporal con el servicio o cambios en la API.'
        ];
    }

    public function clear_cache() {
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $wpdb->esc_like('_transient_kick_wp_') . '%',
            $wpdb->esc_like('_transient_timeout_kick_wp_') . '%'
        ));
        return true;
    }

    /**
     * Verifica y refresca el token si es necesario
     */
    private function maybe_refresh_token() {
        // Solo intentar refrescar si hay un token configurado
        if (empty($this->auth_token)) {
            return;
        }
        
        $expires = get_option('kick_wp_token_expires', 0);
        
        // Si el token expira en menos de 1 hora, refrescarlo
        if (time() > ($expires - 3600)) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kick-wp-oauth.php';
            $oauth = new Kick_Wp_OAuth();
            $oauth->maybe_refresh_token();
            
            // Actualizar el token en esta instancia
            $this->auth_token = get_option('kick_wp_auth_token', '');
            $this->setup_request_args();
        }
    }
}
