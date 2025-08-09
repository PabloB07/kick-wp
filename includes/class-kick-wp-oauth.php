<?php
/**
 * Manejo de autenticación OAuth2 para Kick.com (Corregido)
 *
 * @link       https://blancocl.vercel.app
 * @since      1.2.0
 *
 * @package    Kick_Wp
 * @subpackage Kick_Wp/includes
 */

class Kick_Wp_OAuth {
    
    private $client_id;
    private $client_secret;
    private $redirect_uri;
    
    // URLs oficiales de OAuth de Kick.com (actualizadas)
    private $auth_url = 'https://id.kick.com/oauth2/authorize';
    private $token_url = 'https://id.kick.com/oauth2/token';
    private $api_scope = 'profile:read streams:read';
    
    public function __construct() {
        $this->client_id = get_option('kick_wp_client_id', '');
        $this->client_secret = get_option('kick_wp_client_secret', '');
        $this->redirect_uri = admin_url('admin.php?page=kick-wp-settings&oauth=callback');
        
        // Inicializar hooks
        add_action('admin_init', array($this, 'handle_oauth_callback'));
    }
    
    /**
     * Genera la URL de autorización
     */
    public function get_auth_url() {
        if (empty($this->client_id)) {
            return false;
        }
        
        $state = wp_create_nonce('kick_wp_oauth_' . get_current_user_id());
        
        $params = array(
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'response_type' => 'code',
            'scope' => $this->api_scope,
            'state' => $state
        );
        
        // Guardar el state para verificación posterior
        set_transient('kick_wp_oauth_state', $state, 600); // 10 minutos
        
        return $this->auth_url . '?' . http_build_query($params);
    }
    
    /**
     * Maneja la respuesta del servidor OAuth
     */
    public function handle_oauth_callback() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'kick-wp-settings' || !isset($_GET['oauth']) || $_GET['oauth'] !== 'callback') {
            return;
        }
        
        // Verificar si hay error
        if (isset($_GET['error'])) {
            $error_description = isset($_GET['error_description']) ? sanitize_text_field($_GET['error_description']) : $_GET['error'];
            
            add_settings_error(
                'kick_wp_messages',
                'oauth_error',
                sprintf(__('Error de autorización: %s', 'kick-wp'), $error_description),
                'error'
            );
            
            wp_redirect(admin_url('admin.php?page=kick-wp-settings&auth=error'));
            exit;
        }
        
        // Verificar state para seguridad
        if (!isset($_GET['state'])) {
            add_settings_error(
                'kick_wp_messages',
                'oauth_error',
                __('Error de seguridad: State faltante.', 'kick-wp'),
                'error'
            );
            return;
        }
        
        $received_state = sanitize_text_field($_GET['state']);
        $stored_state = get_transient('kick_wp_oauth_state');
        
        if (!$stored_state || !hash_equals($stored_state, $received_state)) {
            delete_transient('kick_wp_oauth_state');
            
            add_settings_error(
                'kick_wp_messages',
                'oauth_error',
                __('Error de seguridad: State inválido.', 'kick-wp'),
                'error'
            );
            return;
        }
        
        // Limpiar state usado
        delete_transient('kick_wp_oauth_state');
        
        // Verificar código de autorización
        if (!isset($_GET['code'])) {
            add_settings_error(
                'kick_wp_messages',
                'oauth_error',
                __('Error: Código de autorización faltante.', 'kick-wp'),
                'error'
            );
            return;
        }
        
        // Intercambiar código por token
        $code = sanitize_text_field($_GET['code']);
        $token_data = $this->exchange_code_for_token($code);
        
        if (is_wp_error($token_data)) {
            add_settings_error(
                'kick_wp_messages',
                'oauth_error',
                __('Error al obtener token: ', 'kick-wp') . $token_data->get_error_message(),
                'error'
            );
            
            wp_redirect(admin_url('admin.php?page=kick-wp-settings&auth=token_error'));
            exit;
        }
        
        // Guardar token de acceso
        if (isset($token_data['access_token'])) {
            // Guardar el token
            update_option('kick_wp_auth_token', sanitize_text_field($token_data['access_token']));
            
            // Guardar refresh token si está disponible
            if (isset($token_data['refresh_token'])) {
                update_option('kick_wp_refresh_token', sanitize_text_field($token_data['refresh_token']));
            }
            
            // Calcular y guardar fecha de expiración
            $expires_in = isset($token_data['expires_in']) ? intval($token_data['expires_in']) : 3600;
            $expires_at = time() + $expires_in;
            update_option('kick_wp_token_expires', $expires_at);
            
            // Configurar el token en la instancia de API
            $api = new Kick_Wp_Api();
            $api->set_auth_token($token_data['access_token']);
            
            add_settings_error(
                'kick_wp_messages',
                'oauth_success',
                __('¡Autenticación exitosa con Kick.com!', 'kick-wp'),
                'updated'
            );
            
            wp_redirect(admin_url('admin.php?page=kick-wp-settings&auth=success'));
            exit;
        }
        
        add_settings_error(
            'kick_wp_messages',
            'oauth_error',
            __('Error: No se recibió token de acceso.', 'kick-wp'),
            'error'
        );
    }
    
    /**
     * Intercambia el código de autorización por un token de acceso
     */
    private function exchange_code_for_token($code) {
        $body = array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirect_uri
        );
        
        $args = array(
            'body' => $body,
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.1',
            'headers' => array(
                'Accept' => 'application/json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url()
            ),
            'sslverify' => true
        );
        
        $response = wp_remote_post($this->token_url, $args);
        
        if (is_wp_error($response)) {
            return new WP_Error(
                'http_request_failed',
                sprintf(__('Error de conexión: %s', 'kick-wp'), $response->get_error_message())
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            return new WP_Error(
                'oauth_http_error',
                sprintf(__('Error HTTP %d: %s', 'kick-wp'), $response_code, wp_remote_retrieve_response_message($response))
            );
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error(
                'json_parse_error',
                __('Error al procesar la respuesta del servidor.', 'kick-wp')
            );
        }
        
        if (!is_array($data)) {
            return new WP_Error(
                'invalid_response',
                __('Respuesta inválida del servidor de autorización.', 'kick-wp')
            );
        }
        
        if (isset($data['error'])) {
            $error_message = isset($data['error_description']) ? 
                $data['error_description'] : 
                (isset($data['message']) ? $data['message'] : $data['error']);
                
            return new WP_Error(
                'oauth_server_error',
                sprintf(__('Error del servidor: %s', 'kick-wp'), $error_message)
            );
        }
        
        if (!isset($data['access_token'])) {
            return new WP_Error(
                'no_access_token',
                __('No se recibió token de acceso en la respuesta.', 'kick-wp')
            );
        }
        
        return $data;
    }
    
    /**
     * Refresca el token cuando expira
     */
    public function refresh_token() {
        $refresh_token = get_option('kick_wp_refresh_token', '');
        
        if (empty($refresh_token)) {
            return new WP_Error('no_refresh_token', __('No hay token de actualización disponible', 'kick-wp'));
        }
        
        if (empty($this->client_id) || empty($this->client_secret)) {
            return new WP_Error('missing_credentials', __('Credenciales OAuth no configuradas', 'kick-wp'));
        }
        
        $body = array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh_token
        );
        
        $args = array(
            'body' => $body,
            'timeout' => 30,
            'headers' => array(
                'Accept' => 'application/json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url()
            ),
            'sslverify' => true
        );
        
        $response = wp_remote_post($this->token_url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            // Si el refresh token es inválido, limpiamos todo
            if ($response_code === 400 || $response_code === 401) {
                $this->revoke_tokens();
            }
            
            return new WP_Error(
                'refresh_http_error',
                sprintf(__('Error al refrescar token (HTTP %d)', 'kick-wp'), $response_code)
            );
        }
        
        $data = json_decode($body, true);
        
        if (!is_array($data) || isset($data['error'])) {
            $error_message = isset($data['error_description']) ? 
                $data['error_description'] : 
                (isset($data['error']) ? $data['error'] : __('Error desconocido', 'kick-wp'));
                
            return new WP_Error('refresh_error', $error_message);
        }
        
        // Actualizar tokens
        if (isset($data['access_token'])) {
            update_option('kick_wp_auth_token', sanitize_text_field($data['access_token']));
            
            if (isset($data['refresh_token'])) {
                update_option('kick_wp_refresh_token', sanitize_text_field($data['refresh_token']));
            }
            
            $expires_in = isset($data['expires_in']) ? intval($data['expires_in']) : 3600;
            $expires_at = time() + $expires_in;
            update_option('kick_wp_token_expires', $expires_at);
            
            // Actualizar token en la instancia de API
            $api = new Kick_Wp_Api();
            $api->set_auth_token($data['access_token']);
            
            return true;
        }
        
        return new WP_Error('refresh_no_token', __('No se recibió nuevo token de acceso', 'kick-wp'));
    }
    
    /**
     * Verifica si el token actual ha expirado
     */
    public function is_token_expired() {
        $expires = get_option('kick_wp_token_expires', 0);
        return time() >= $expires;
    }
    
    /**
     * Verifica si el token expira pronto (en menos de 5 minutos)
     */
    public function is_token_expiring_soon() {
        $expires = get_option('kick_wp_token_expires', 0);
        return time() >= ($expires - 300); // 5 minutos antes
    }
    
    /**
     * Verifica y refresca el token si es necesario
     */
    public function maybe_refresh_token() {
        $auth_token = get_option('kick_wp_auth_token', '');
        
        // Si no hay token, no hay nada que refrescar
        if (empty($auth_token)) {
            return true;
        }
        
        // Si el token no ha expirado y no expira pronto, no hacer nada
        if (!$this->is_token_expiring_soon()) {
            return true;
        }
        
        // Intentar refrescar el token
        $result = $this->refresh_token();
        
        if (is_wp_error($result)) {
            // Log del error para debug
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kick WP: Error refreshing token - ' . $result->get_error_message());
            }
            
            // Si el refresh falla, el token probablemente sea inválido
            // Pero no lo eliminamos automáticamente para evitar pérdida de datos
            return $result;
        }
        
        return true;
    }
    
    /**
     * Revoca todos los tokens y limpia la configuración
     */
    public function revoke_tokens() {
        delete_option('kick_wp_auth_token');
        delete_option('kick_wp_refresh_token');
        delete_option('kick_wp_token_expires');
        
        // También limpiar cualquier caché relacionado
        if (class_exists('Kick_Wp_Api')) {
            $api = new Kick_Wp_Api();
            $api->clear_cache();
        }
        
        return true;
    }
    
    /**
     * Obtiene información del token actual
     */
    public function get_token_info() {
        $token = get_option('kick_wp_auth_token', '');
        $expires = get_option('kick_wp_token_expires', 0);
        $refresh_token = get_option('kick_wp_refresh_token', '');
        
        if (empty($token)) {
            return array(
                'status' => 'not_authenticated',
                'message' => __('No autenticado', 'kick-wp')
            );
        }
        
        if ($this->is_token_expired()) {
            return array(
                'status' => 'expired',
                'message' => __('Token expirado', 'kick-wp'),
                'expires_at' => $expires,
                'has_refresh_token' => !empty($refresh_token)
            );
        }
        
        if ($this->is_token_expiring_soon()) {
            return array(
                'status' => 'expiring_soon',
                'message' => sprintf(__('Token expira en %s', 'kick-wp'), human_time_diff(time(), $expires)),
                'expires_at' => $expires,
                'has_refresh_token' => !empty($refresh_token)
            );
        }
        
        return array(
            'status' => 'valid',
            'message' => sprintf(__('Token válido (expira en %s)', 'kick-wp'), human_time_diff(time(), $expires)),
            'expires_at' => $expires,
            'has_refresh_token' => !empty($refresh_token)
        );
    }
    
    /**
     * Valida la configuración OAuth
     */
    public function validate_config() {
        $errors = array();
        
        if (empty($this->client_id)) {
            $errors[] = __('Client ID no configurado', 'kick-wp');
        }
        
        if (empty($this->client_secret)) {
            $errors[] = __('Client Secret no configurado', 'kick-wp');
        }
        
        // Validar formato de Client ID (generalmente UUID o string alfanumérico)
        if (!empty($this->client_id) && !preg_match('/^[a-zA-Z0-9\-_]+$/', $this->client_id)) {
            $errors[] = __('Client ID tiene formato inválido', 'kick-wp');
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * Obtiene la URL de redirección configurada
     */
    public function get_redirect_uri() {
        return $this->redirect_uri;
    }
    
    /**
     * Test de conectividad con los endpoints OAuth
     */
    public function test_oauth_connectivity() {
        // Test del endpoint de autorización
        $auth_response = wp_remote_head($this->auth_url, array(
            'timeout' => 10,
            'sslverify' => true
        ));
        
        if (is_wp_error($auth_response)) {
            return array(
                'success' => false,
                'message' => __('No se puede conectar con el servidor de autorización', 'kick-wp'),
                'details' => $auth_response->get_error_message()
            );
        }
        
        $auth_code = wp_remote_retrieve_response_code($auth_response);
        if ($auth_code >= 400) {
            return array(
                'success' => false,
                'message' => sprintf(__('Servidor de autorización devolvió error %d', 'kick-wp'), $auth_code)
            );
        }
        
        return array(
            'success' => true,
            'message' => __('Conectividad OAuth verificada', 'kick-wp')
        );
    }
}