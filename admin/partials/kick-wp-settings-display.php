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

        <?php submit_button(); ?>
    </form>
</div>
