<?php
/**
 * Manejo de autenticación OAuth2 para Kick.com
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
    private $auth_url = 'https://id.kick.com/oauth/authorize';
    private $token_url = 'https://id.kick.com/oauth/token';
    private $api_scope = 'channel:read user:read';
    
    /**
     * Constructor
     */
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
        
        $params = array(
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'response_type' => 'code',
            'scope' => $this->api_scope,
            'state' => wp_create_nonce('kick_wp_oauth')
        );
        
        return $this->auth_url . '?' . http_build_query($params);
    }
    
    /**
     * Maneja la respuesta del servidor OAuth
     */
    public function handle_oauth_callback() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'kick-wp-settings' || !isset($_GET['oauth']) || $_GET['oauth'] !== 'callback') {
            return;
        }
        
        // Verificar nonce para seguridad
        if (!isset($_GET['state']) || !wp_verify_nonce($_GET['state'], 'kick_wp_oauth')) {
            add_settings_error(
                'kick_wp_messages',
                'oauth_error',
                __('Error de seguridad en la autenticación.', 'kick-wp'),
                'error'
            );
            return;
        }
        
        // Verificar código de autorización
        if (!isset($_GET['code'])) {
            if (isset($_GET['error'])) {
                add_settings_error(
                    'kick_wp_messages',
                    'oauth_error',
                    sprintf(__('Error de autorización: %s', 'kick-wp'), sanitize_text_field($_GET['error'])),
                    'error'
                );
            }
            return;
        }
        
        // Intercambiar código por token
        $token_data = $this->exchange_code_for_token($_GET['code']);
        
        if (is_wp_error($token_data)) {
            add_settings_error(
                'kick_wp_messages',
                'oauth_error',
                $token_data->get_error_message(),
                'error'
            );
            return;
        }
        
        // Guardar token en opciones
        if (isset($token_data['access_token'])) {
            // Instanciar API y configurar token
            $api = new Kick_Wp_Api();
            $api->set_auth_token($token_data['access_token']);
            
            // Guardar refresh token si está disponible
            if (isset($token_data['refresh_token'])) {
                update_option('kick_wp_refresh_token', $token_data['refresh_token']);
            }
            
            // Guardar fecha de expiración
            if (isset($token_data['expires_in'])) {
                $expires = time() + intval($token_data['expires_in']);
                update_option('kick_wp_token_expires', $expires);
            }
            
            add_settings_error(
                'kick_wp_messages',
                'oauth_success',
                __('Autenticación exitosa con Kick.com', 'kick-wp'),
                'success'
            );
        }
        
        // Redireccionar para limpiar la URL
        wp_redirect(admin_url('admin.php?page=kick-wp-settings&auth=success'));
        exit;
    }
    
    /**
     * Intercambia el código de autorización por un token de acceso
     */
    private function exchange_code_for_token($code) {
        $params = array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirect_uri
        );
        
        $response = wp_remote_post($this->token_url, array(
            'body' => $params,
            'timeout' => 30,
            'headers' => array(
                'Accept' => 'application/json'
            )
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!is_array($data) || isset($data['error'])) {
            return new WP_Error(
                'oauth_error',
                isset($data['error_description']) ? $data['error_description'] : __('Error al obtener el token', 'kick-wp')
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
        
        $params = array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh_token
        );
        
        $response = wp_remote_post($this->token_url, array(
            'body' => $params,
            'timeout' => 30,
            'headers' => array(
                'Accept' => 'application/json'
            )
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!is_array($data) || isset($data['error'])) {
            return new WP_Error(
                'refresh_error',
                isset($data['error_description']) ? $data['error_description'] : __('Error al refrescar el token', 'kick-wp')
            );
        }
        
        // Actualizar tokens
        if (isset($data['access_token'])) {
            $api = new Kick_Wp_Api();
            $api->set_auth_token($data['access_token']);
            
            if (isset($data['refresh_token'])) {
                update_option('kick_wp_refresh_token', $data['refresh_token']);
            }
            
            if (isset($data['expires_in'])) {
                $expires = time() + intval($data['expires_in']);
                update_option('kick_wp_token_expires', $expires);
            }
            
            return true;
        }
        
        return new WP_Error('unknown_error', __('Error desconocido al refrescar el token', 'kick-wp'));
    }
    
    /**
     * Verifica si el token actual ha expirado
     */
    public function is_token_expired() {
        $expires = get_option('kick_wp_token_expires', 0);
        return time() > $expires;
    }
    
    /**
     * Verifica y refresca el token si es necesario
     */
    public function maybe_refresh_token() {
        if ($this->is_token_expired()) {
            return $this->refresh_token();
        }
        return true;
    }
}