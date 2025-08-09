<?php
/**
 * Vista unificada para el plugin Kick WP
 *
 * @link       https://blancocl.vercel.app
 * @since      1.2.0
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
    
    <?php settings_errors('kick_wp_messages'); ?>

    <h2 class="nav-tab-wrapper">
        <a href="#dashboard" class="nav-tab nav-tab-active" data-tab="dashboard"><?php _e('Dashboard', 'kick-wp'); ?></a>
        <a href="#streams" class="nav-tab" data-tab="streams"><?php _e('Streams', 'kick-wp'); ?></a>
        <a href="#categories" class="nav-tab" data-tab="categories"><?php _e('Categorías', 'kick-wp'); ?></a>
        <a href="#settings" class="nav-tab" data-tab="settings"><?php _e('Configuración', 'kick-wp'); ?></a>
    </h2>

    <!-- Dashboard Tab -->
    <div id="dashboard" class="tab-content active">
        <div class="kick-wp-dashboard">
            <!-- Estado de Conexión -->
            <div class="kick-wp-card">
                <h2><?php esc_html_e('Estado del Sistema', 'kick-wp'); ?></h2>
                
                <div class="kick-wp-status-indicator <?php echo $connection_test['success'] ? 'status-success' : 'status-error'; ?>">
                    <span class="dashicons dashicons-<?php echo $connection_test['success'] ? 'yes' : 'no'; ?>"></span>
                    <strong><?php esc_html_e('API de Kick.com:', 'kick-wp'); ?></strong>
                    <?php echo esc_html($connection_test['message']); ?>
                </div>
                
                <!-- OAuth Status -->
                <div style="margin-top: 15px;">
                    <h4><?php esc_html_e('Estado de Autenticación', 'kick-wp'); ?></h4>
                    <?php if (!empty($auth_token) && time() < $token_expires): ?>
                        <div class="kick-wp-status-indicator status-success">
                            <span class="dashicons dashicons-yes"></span>
                            <?php 
                            $expires_in = human_time_diff(time(), $token_expires);
                            printf(esc_html__('Autenticado (expira en %s)', 'kick-wp'), $expires_in);
                            ?>
                        </div>
                    <?php elseif (!empty($auth_token)): ?>
                        <div class="kick-wp-status-indicator status-warning">
                            <span class="dashicons dashicons-warning"></span>
                            <?php esc_html_e('Token expirado - Requiere renovación', 'kick-wp'); ?>
                        </div>
                    <?php else: ?>
                        <div class="kick-wp-status-indicator status-error">
                            <span class="dashicons dashicons-no"></span>
                            <?php esc_html_e('No autenticado', 'kick-wp'); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Acciones rápidas -->
                <div style="margin-top: 20px;">
                    <h4><?php esc_html_e('Acciones Rápidas', 'kick-wp'); ?></h4>
                    <div class="kick-wp-actions">
                        <?php if (empty($auth_token) || time() >= $token_expires): ?>
                            <?php
                            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kick-wp-oauth.php';
                            $oauth = new Kick_Wp_OAuth();
                            $auth_url = $oauth->get_auth_url();
                            
                            if ($auth_url): ?>
                                <a href="<?php echo esc_url($auth_url); ?>" class="button button-primary">
                                    <span class="dashicons dashicons-lock"></span>
                                    <?php esc_html_e('Conectar con Kick.com', 'kick-wp'); ?>
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=kick-wp&action=revoke_token'), 'kick_wp_revoke_token')); ?>" class="button">
                                <span class="dashicons dashicons-unlock"></span>
                                <?php esc_html_e('Revocar Acceso', 'kick-wp'); ?>
                            </a>
                        <?php endif; ?>
                        
                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=kick-wp&action=clear_cache'), 'kick_wp_clear_cache')); ?>" class="button">
                            <span class="dashicons dashicons-update"></span>
                            <?php esc_html_e('Limpiar Caché', 'kick-wp'); ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Guía de Shortcodes -->
            <div class="kick-wp-card">
                <h2><?php esc_html_e('Shortcodes Disponibles', 'kick-wp'); ?></h2>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Shortcode', 'kick-wp'); ?></th>
                            <th><?php esc_html_e('Descripción', 'kick-wp'); ?></th>
                            <th><?php esc_html_e('Ejemplo', 'kick-wp'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>[kick_wp_streams]</code></td>
                            <td><?php esc_html_e('Muestra streams destacados', 'kick-wp'); ?></td>
                            <td><code>[kick_wp_streams count="6" layout="grid"]</code></td>
                        </tr>
                        <tr>
                            <td><code>[kick_wp_streams streamer="username"]</code></td>
                            <td><?php esc_html_e('Muestra un streamer específico', 'kick-wp'); ?></td>
                            <td><code>[kick_wp_streams streamer="xqc"]</code></td>
                        </tr>
                        <tr>
                            <td><code>[kick_wp_categories]</code></td>
                            <td><?php esc_html_e('Muestra categorías disponibles', 'kick-wp'); ?></td>
                            <td><code>[kick_wp_categories limit="10"]</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Streams Tab -->
    <div id="streams" class="tab-content">
        <h3><?php _e('Streams Destacados', 'kick-wp'); ?></h3>
        <div class="kick-wp-streams-grid">
            <?php if (isset($featured_streams['data']) && !empty($featured_streams['data'])): ?>
                <?php foreach ($featured_streams['data'] as $stream): ?>
                    <div class="kick-wp-stream-card">
                        <img src="<?php echo esc_url($stream['thumbnail']); ?>" 
                             alt="<?php echo esc_attr($stream['username']); ?>"
                             onerror="this.src='https://via.placeholder.com/320x180?text=No+Image'" />
                        <h4><?php echo esc_html($stream['username']); ?></h4>
                        <p><?php echo esc_html($stream['title']); ?></p>
                        <div class="stream-meta">
                            <span class="viewers">👁 <?php echo number_format($stream['viewer_count']); ?></span>
                            <?php if (!empty($stream['category'])): ?>
                                <span class="category"><?php echo esc_html($stream['category']); ?></span>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo esc_url($stream['channel_url']); ?>" 
                           target="_blank" class="button button-small">
                            <?php esc_html_e('Ver Stream', 'kick-wp'); ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p><?php _e('No hay streams destacados disponibles en este momento.', 'kick-wp'); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Categories Tab -->
    <div id="categories" class="tab-content">
        <h3><?php _e('Categorías Disponibles', 'kick-wp'); ?></h3>
        <div class="kick-wp-categories-grid">
            <?php if (isset($categories['data']) && !empty($categories['data'])): ?>
                <?php foreach ($categories['data'] as $category): ?>
                    <div class="kick-wp-category-card">
                        <?php if (isset($category['icon'])): ?>
                            <img src="<?php echo esc_url($category['icon']); ?>" 
                                 alt="<?php echo esc_attr($category['name']); ?>">
                        <?php endif; ?>
                        
                        <?php if (isset($category['name'])): ?>
                            <h4><?php echo esc_html($category['name']); ?></h4>
                        <?php endif; ?>
                        
                        <?php if (isset($category['viewers'])): ?>
                            <p><?php printf(
                                esc_html__('%s espectadores', 'kick-wp'),
                                number_format_i18n($category['viewers'])
                            ); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p><?php _e('No hay categorías disponibles en este momento.', 'kick-wp'); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Settings Tab -->
    <div id="settings" class="tab-content">
        <form method="post" action="options.php">
            <?php
            settings_fields('kick_wp_settings');
            do_settings_sections('kick_wp_settings');
            ?>

            <h3><?php esc_html_e('Configuración General', 'kick-wp'); ?></h3>
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
                        <?php esc_html_e('Mostrar Información', 'kick-wp'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="kick_wp_show_viewer_count" 
                                   value="1" 
                                   <?php checked(1, get_option('kick_wp_show_viewer_count', true)); ?> />
                            <?php esc_html_e('Contador de espectadores', 'kick-wp'); ?>
                        </label>
                        <br />
                        <label>
                            <input type="checkbox" 
                                   name="kick_wp_show_categories" 
                                   value="1" 
                                   <?php checked(1, get_option('kick_wp_show_categories', true)); ?> />
                            <?php esc_html_e('Categorías', 'kick-wp'); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <h3><?php esc_html_e('Configuración de API', 'kick-wp'); ?></h3>
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
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
</div>

<script>
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
        
        // Actualizar hash en URL
        window.location.hash = tab;
    });
    
    // Cargar pestaña desde hash en URL
    if (window.location.hash) {
        var hash = window.location.hash.substring(1);
        $('.nav-tab[data-tab="' + hash + '"]').click();
    }
});
</script>