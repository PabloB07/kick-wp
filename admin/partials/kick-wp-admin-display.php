<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://blancocl.vercel.app
 * @since      1.0.0
 *
 * @package    Kick_Wp
 * @subpackage Kick_Wp/admin/partials
 */

// Prevenir acceso directo
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <h2 class="nav-tab-wrapper">
        <a href="#streams" class="nav-tab nav-tab-active" data-tab="streams"><?php _e('Streams Destacados', 'kick-wp'); ?></a>
        <a href="#categories" class="nav-tab" data-tab="categories"><?php _e('Categorías', 'kick-wp'); ?></a>
        <a href="#settings" class="nav-tab" data-tab="settings"><?php _e('Configuración', 'kick-wp'); ?></a>
    </h2>

    <div id="streams" class="tab-content active">
        <h3><?php _e('Streams Destacados', 'kick-wp'); ?></h3>
        <div class="kick-wp-streams-grid">
            <?php if (is_array($featured_streams) && !empty($featured_streams)): ?>
                <?php foreach ($featured_streams as $stream): ?>
                    <div class="kick-wp-stream-card">
                        <?php if (isset($stream['thumbnail'])): ?>
                            <img src="<?php echo esc_url($stream['thumbnail']); ?>" alt="<?php echo esc_attr($stream['title']); ?>">
                        <?php endif; ?>
                        <h4><?php echo esc_html($stream['title']); ?></h4>
                        <p><?php echo esc_html($stream['viewer_count']); ?> <?php _e('espectadores', 'kick-wp'); ?></p>
                        <a href="<?php echo esc_url('https://kick.com/' . $stream['username']); ?>" class="kick-wp-watch-button" target="_blank">
                            <?php _e('Ver Stream', 'kick-wp'); ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p><?php _e('No hay streams destacados disponibles en este momento.', 'kick-wp'); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div id="categories" class="tab-content">
        <h3><?php _e('Categorías Disponibles', 'kick-wp'); ?></h3>
        <div class="kick-wp-categories-grid">
            <?php if (is_array($categories) && !empty($categories)): ?>
                <?php foreach ($categories as $category): ?>
                    <div class="kick-wp-category-card">
                        <?php if (isset($category['image'])): ?>
                            <img src="<?php echo esc_url($category['image']); ?>" alt="<?php echo esc_attr($category['name']); ?>">
                        <?php endif; ?>
                        <h4><?php echo esc_html($category['name']); ?></h4>
                        <?php if (isset($category['viewer_count'])): ?>
                            <p><?php echo esc_html($category['viewer_count']); ?> <?php _e('espectadores', 'kick-wp'); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p><?php _e('No hay categorías disponibles en este momento.', 'kick-wp'); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div id="settings" class="tab-content">
        <form method="post" action="options.php">
            <?php
            settings_fields('kick_wp_options');
            do_settings_sections('kick_wp_settings');
            ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="kick_wp_cache_duration">
                            <?php _e('Duración del Caché', 'kick-wp'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               id="kick_wp_cache_duration" 
                               name="kick_wp_cache_duration" 
                               value="<?php echo esc_attr(get_option('kick_wp_cache_duration', 300)); ?>"
                               min="60"
                               class="regular-text">
                        <p class="description">
                            <?php _e('Tiempo en segundos que se almacenarán en caché los datos de la API (mínimo 60 segundos).', 'kick-wp'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="kick_wp_streams_per_page">
                            <?php _e('Streams por Página', 'kick-wp'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               id="kick_wp_streams_per_page" 
                               name="kick_wp_streams_per_page" 
                               value="<?php echo esc_attr(get_option('kick_wp_streams_per_page', 12)); ?>"
                               min="4"
                               max="24"
                               class="regular-text">
                        <p class="description">
                            <?php _e('Número de streams a mostrar por página (entre 4 y 24).', 'kick-wp'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php _e('Actualización Automática', 'kick-wp'); ?>
                    </th>
                    <td>
                        <label for="kick_wp_auto_refresh">
                            <input type="checkbox" 
                                   id="kick_wp_auto_refresh" 
                                   name="kick_wp_auto_refresh" 
                                   value="1" 
                                   <?php checked(1, get_option('kick_wp_auto_refresh', 1)); ?>>
                            <?php _e('Habilitar actualización automática de streams', 'kick-wp'); ?>
                        </label>
                        <p class="description">
                            <?php _e('Actualiza automáticamente la información de los streams cada vez que expire el caché.', 'kick-wp'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Manejo de pestañas
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var tab = $(this).data('tab');
        
        // Actualizar pestañas activas
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Mostrar contenido de pestaña
        $('.tab-content').removeClass('active');
        $('#' + tab).addClass('active');
    });
});
</script>

<style>
.tab-content {
    display: none;
    padding: 20px 0;
}

.tab-content.active {
    display: block;
}

.kick-wp-streams-grid,
.kick-wp-categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.kick-wp-stream-card,
.kick-wp-category-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.kick-wp-stream-card img,
.kick-wp-category-card img {
    width: 100%;
    height: auto;
    border-radius: 4px;
}

.kick-wp-watch-button {
    display: inline-block;
    background: #0073aa;
    color: #fff;
    padding: 8px 15px;
    text-decoration: none;
    border-radius: 3px;
    margin-top: 10px;
}

.kick-wp-watch-button:hover {
    background: #005177;
    color: #fff;
}
</style>
