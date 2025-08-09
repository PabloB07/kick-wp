<?php
/**
 * Provide a admin area view for the plugin's settings page
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://blancocl.vercel.app
 * @since      1.0.0
 *
 * @package    Kick_Wp
 * @subpackage Kick_Wp/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors('kick_wp_messages'); ?>

    <form method="post" action="options.php">
        <?php
        settings_fields('kick_wp_options');
        do_settings_sections('kick_wp_settings');
        ?>

        <h2><?php esc_html_e('Configuración General', 'kick-wp'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="kick_wp_cache_duration">
                        <?php esc_html_e('Duración del Caché', 'kick-wp'); ?>
                    </label>
                </th>
                <td>
                    <input type="number" 
                           id="kick_wp_cache_duration" 
                           name="kick_wp_cache_duration" 
                           value="<?php echo esc_attr(get_option('kick_wp_cache_duration', 300)); ?>" 
                           min="60"
                           class="small-text" />
                    <p class="description">
                        <?php esc_html_e('Tiempo en segundos que se mantienen los datos en caché (mínimo 60 segundos).', 'kick-wp'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="kick_wp_streams_per_page">
                        <?php esc_html_e('Streams por Página', 'kick-wp'); ?>
                    </label>
                </th>
                <td>
                    <input type="number" 
                           id="kick_wp_streams_per_page" 
                           name="kick_wp_streams_per_page" 
                           value="<?php echo esc_attr(get_option('kick_wp_streams_per_page', 12)); ?>" 
                           min="4" 
                           max="24"
                           class="small-text" />
                    <p class="description">
                        <?php esc_html_e('Número de streams a mostrar por página (entre 4 y 24).', 'kick-wp'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <?php esc_html_e('Actualización Automática', 'kick-wp'); ?>
                </th>
                <td>
                    <label for="kick_wp_auto_refresh">
                        <input type="checkbox" 
                               id="kick_wp_auto_refresh" 
                               name="kick_wp_auto_refresh" 
                               value="1" 
                               <?php checked(1, get_option('kick_wp_auto_refresh', true), true); ?> />
                        <?php esc_html_e('Actualizar automáticamente los streams', 'kick-wp'); ?>
                    </label>
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e('Visualización', 'kick-wp'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="kick_wp_layout_style">
                        <?php esc_html_e('Estilo de Visualización', 'kick-wp'); ?>
                    </label>
                </th>
                <td>
                    <select id="kick_wp_layout_style" name="kick_wp_layout_style">
                        <option value="grid" <?php selected('grid', get_option('kick_wp_layout_style', 'grid')); ?>>
                            <?php esc_html_e('Cuadrícula', 'kick-wp'); ?>
                        </option>
                        <option value="list" <?php selected('list', get_option('kick_wp_layout_style', 'grid')); ?>>
                            <?php esc_html_e('Lista', 'kick-wp'); ?>
                        </option>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <?php esc_html_e('Elementos a Mostrar', 'kick-wp'); ?>
                </th>
                <td>
                    <label for="kick_wp_show_viewer_count">
                        <input type="checkbox" 
                               id="kick_wp_show_viewer_count" 
                               name="kick_wp_show_viewer_count" 
                               value="1" 
                               <?php checked(1, get_option('kick_wp_show_viewer_count', true), true); ?> />
                        <?php esc_html_e('Mostrar contador de espectadores', 'kick-wp'); ?>
                    </label>
                    <br />
                    <label for="kick_wp_show_categories">
                        <input type="checkbox" 
                               id="kick_wp_show_categories" 
                               name="kick_wp_show_categories" 
                               value="1" 
                               <?php checked(1, get_option('kick_wp_show_categories', true), true); ?> />
                        <?php esc_html_e('Mostrar categorías en streams', 'kick-wp'); ?>
                    </label>
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e('Configuración de Shortcode', 'kick-wp'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="kick_wp_default_stream_count">
                        <?php esc_html_e('Streams por Defecto', 'kick-wp'); ?>
                    </label>
                </th>
                <td>
                    <input type="number" 
                           id="kick_wp_default_stream_count" 
                           name="kick_wp_default_stream_count" 
                           value="<?php echo esc_attr(get_option('kick_wp_default_stream_count', 4)); ?>" 
                           min="1" 
                           max="12"
                           class="small-text" />
                    <p class="description">
                        <?php esc_html_e('Número predeterminado de streams a mostrar en el shortcode.', 'kick-wp'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e('Configuración de API', 'kick-wp'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="kick_wp_client_id">
                        <?php esc_html_e('Client ID', 'kick-wp'); ?>
                    </label>
                </th>
                <td>
                    <input type="text" 
                           id="kick_wp_client_id" 
                           name="kick_wp_client_id" 
                           value="<?php echo esc_attr(get_option('kick_wp_client_id', '')); ?>" 
                           class="regular-text" />
                    <p class="description">
                        <?php esc_html_e('ID de cliente para la API de Kick.com', 'kick-wp'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="kick_wp_client_secret">
                        <?php esc_html_e('Client Secret', 'kick-wp'); ?>
                    </label>
                </th>
                <td>
                    <input type="password" 
                           id="kick_wp_client_secret" 
                           name="kick_wp_client_secret" 
                           value="<?php echo esc_attr(get_option('kick_wp_client_secret', '')); ?>" 
                           class="regular-text" />
                    <p class="description">
                        <?php esc_html_e('Clave secreta para la API de Kick.com', 'kick-wp'); ?>
                    </p>
                </td>
            </tr>
            
            <!-- En la sección de Estado de Autenticación -->
            <tr>
                <th scope="row">
                    <?php esc_html_e('Estado de Autenticación', 'kick-wp'); ?>
                </th>
                <td>
                    <?php 
                    $token = get_option('kick_wp_auth_token', '');
                    $expires = get_option('kick_wp_token_expires', 0);
                    $now = time();
                    
                    // Mostrar estado de la API
                    $api = new Kick_Wp_Api();
                    $api_status = $api->test_connection();
                    
                    echo '<div class="kick-wp-api-status">';
                    if ($api_status['success']) {
                        echo '<p><span class="dashicons dashicons-yes" style="color:green;"></span> ';
                        echo esc_html($api_status['message']);
                        echo '</p>';
                    } else {
                        echo '<p><span class="dashicons dashicons-no" style="color:red;"></span> ';
                        echo esc_html($api_status['message']);
                        echo '</p>';
                        if (isset($api_status['details'])) {
                            echo '<p class="description">' . esc_html($api_status['details']) . '</p>';
                        }
                    }
                    echo '</div>';
                    
                    // Resto del código existente para mostrar el estado del token
                    if (!empty($token)) {
                        if ($now < $expires) {
                            $expires_in = human_time_diff($now, $expires);
                            echo '<span class="dashicons dashicons-yes" style="color:green;"></span> ';
                            printf(esc_html__('Autenticado (expira en %s)', 'kick-wp'), $expires_in);
                        } else {
                            echo '<span class="dashicons dashicons-warning" style="color:orange;"></span> ';
                            esc_html_e('Token expirado', 'kick-wp');
                        }
                        
                        echo '<br><br>';
                        echo '<a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=kick-wp-settings&action=revoke_token'), 'kick_wp_revoke_token')) . '" class="button">';
                        esc_html_e('Revocar Acceso', 'kick-wp');
                        echo '</a>';
                    } else {
                        echo '<span class="dashicons dashicons-no" style="color:red;"></span> ';
                        esc_html_e('No autenticado', 'kick-wp');
                    }
                    
                    // Mostrar botón de autenticación si hay client ID y secret
                    $client_id = get_option('kick_wp_client_id', '');
                    $client_secret = get_option('kick_wp_client_secret', '');
                    
                    // Añadir información de depuración
                    echo '<div class="kick-wp-debug-info" style="margin-top: 15px; padding: 10px; background: #f8f8f8; border-left: 4px solid #ccc;">';
                    echo '<h4>Información de depuración:</h4>';
                    echo '<p>Client ID configurado: ' . (!empty($client_id) ? 'Sí' : 'No') . '</p>';
                    echo '<p>Client Secret configurado: ' . (!empty($client_secret) ? 'Sí' : 'No') . '</p>';
                    echo '</div>';
                    
                    if (!empty($client_id) && !empty($client_secret)) {
                        require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'includes/class-kick-wp-oauth.php';
                        $oauth = new Kick_Wp_OAuth();
                        $auth_url = $oauth->get_auth_url();
                        
                        // Mostrar la URL generada para depuración
                        echo '<div class="kick-wp-debug-info" style="margin-top: 10px; padding: 10px; background: #f8f8f8; border-left: 4px solid #ccc;">';
                        echo '<p>URL de autenticación: ' . (empty($auth_url) ? 'No se pudo generar' : esc_url($auth_url)) . '</p>';
                        echo '</div>';
                        
                        if ($auth_url) {
                            echo '<br><br>';
                            echo '<a href="' . esc_url($auth_url) . '" class="button button-primary">';
                            esc_html_e('Conectar con Kick.com', 'kick-wp');
                            echo '</a>';
                        } else {
                            echo '<br><br>';
                            echo '<p class="description" style="color:red;">';
                            esc_html_e('Error al generar la URL de autenticación. Verifica el Client ID y Client Secret.', 'kick-wp');
                            echo '</p>';
                        }
                    }
                    ?>
                    <p class="description">
                        <?php esc_html_e('Para obtener las credenciales de API, debes registrar una aplicación en el portal de desarrolladores de Kick.com en https://dev.kick.com', 'kick-wp'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
</div>

                    // Añadir botón para limpiar caché
                    echo '<br><br>';
                    echo '<a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=kick-wp-settings&action=clear_cache'), 'kick_wp_clear_cache')) . '" class="button">';
                    esc_html_e('Limpiar Caché', 'kick-wp');
                    echo '</a>';
