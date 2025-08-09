<?php
/**
 * Clase para manejar la configuración del plugin en el panel de administración
 *
 * @link       https://blancocl.vercel.app
 * @since      1.0.0
 *
 * @package    Kick_Wp
 * @subpackage Kick_Wp/admin
 */

class Kick_Wp_Admin_Settings {

    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_plugin_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Agrega el menú del plugin al panel de administración
     */
    public function add_plugin_admin_menu() {
        add_menu_page(
            'Kick WP Settings',
            'Kick WP',
            'manage_options',
            'kick-wp-settings',
            array($this, 'display_plugin_admin_page'),
            'dashicons-video-alt3',
            85
        );
    }

    /**
     * Registra las opciones de configuración
     */
    public function register_settings() {
        register_setting('kick_wp_options', 'kick_wp_api_token', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));

        register_setting('kick_wp_options', 'kick_wp_cache_duration', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 300
        ));

        add_settings_section(
            'kick_wp_settings_section',
            'Configuración de la API',
            array($this, 'settings_section_callback'),
            'kick-wp-settings'
        );

        add_settings_field(
            'kick_wp_api_token',
            'Token de API',
            array($this, 'api_token_callback'),
            'kick-wp-settings',
            'kick_wp_settings_section'
        );

        add_settings_field(
            'kick_wp_cache_duration',
            'Duración del caché (segundos)',
            array($this, 'cache_duration_callback'),
            'kick-wp-settings',
            'kick_wp_settings_section'
        );
    }

    /**
     * Callback para la sección de configuración
     */
    public function settings_section_callback() {
        echo '<p>Configura los ajustes para la integración con Kick.com</p>';
    }

    /**
     * Callback para el campo de token de API
     */
    public function api_token_callback() {
        $token = get_option('kick_wp_api_token');
        ?>
        <input type="password" id="kick_wp_api_token" name="kick_wp_api_token" 
               value="<?php echo esc_attr($token); ?>" class="regular-text">
        <p class="description">
            Ingresa tu token de autenticación para la API de Kick.com
        </p>
        <?php
    }

    /**
     * Callback para el campo de duración del caché
     */
    public function cache_duration_callback() {
        $duration = get_option('kick_wp_cache_duration', 300);
        ?>
        <input type="number" id="kick_wp_cache_duration" name="kick_wp_cache_duration" 
               value="<?php echo esc_attr($duration); ?>" class="small-text">
        <p class="description">
            Tiempo en segundos que se almacenarán en caché las respuestas de la API (por defecto: 300)
        </p>
        <?php
    }

    /**
     * Renderiza la página de administración
     */
    public function display_plugin_admin_page() {
        ?>
        <div class="wrap">
            <h2>Configuración de Kick WP</h2>
            <form method="post" action="options.php">
                <?php
                settings_fields('kick_wp_options');
                do_settings_sections('kick-wp-settings');
                submit_button();
                ?>
            </form>

            <div class="card">
                <h3>Estado de la API</h3>
                <?php
                $api = new Kick_Wp_Api();
                $test = $api->get_categories();
                if (is_wp_error($test)) {
                    echo '<p class="error">Error: ' . esc_html($test->get_error_message()) . '</p>';
                } else {
                    echo '<p class="success">✅ Conexión exitosa con la API</p>';
                }
                ?>
            </div>

            <div class="card">
                <h3>Instrucciones</h3>
                <p>Para usar este plugin:</p>
                <ol>
                    <li>Obtén un token de acceso desde tu cuenta de Kick.com</li>
                    <li>Ingresa el token en el campo "Token de API"</li>
                    <li>Ajusta la duración del caché según tus necesidades</li>
                    <li>Guarda los cambios</li>
                </ol>
                <p>Una vez configurado, puedes usar los shortcodes disponibles:</p>
                <ul>
                    <li><code>[kick_streamer username="nombre"]</code> - Muestra información de un streamer</li>
                    <li><code>[kick_featured limit="12"]</code> - Muestra streams destacados</li>
                    <li><code>[kick_categories]</code> - Muestra las categorías disponibles</li>
                </ul>
            </div>
        </div>

        <style>
        .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            margin-top: 20px;
            padding: 15px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .error {
            color: #dc3232;
            font-weight: bold;
        }
        .success {
            color: #46b450;
            font-weight: bold;
        }
        </style>
        <?php
    }
}
