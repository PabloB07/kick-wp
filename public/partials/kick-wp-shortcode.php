<?php
/**
 * Provide a public-facing view for the plugin's shortcode
 *
 * @link       https://blancocl.vercel.app
 * @since      1.0.0
 *
 * @package    Kick_Wp
 * @subpackage Kick_Wp/public/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Muestra los streams
 */
function kick_wp_display_streams_html($streams, $args = array()) {
    $default_layout = get_option('kick_wp_layout_style', 'grid');
    $show_viewers = get_option('kick_wp_show_viewer_count', true);
    $show_categories = get_option('kick_wp_show_categories', true);
    
    $layout = isset($args['layout']) ? $args['layout'] : $default_layout;
    $container_class = 'kick-wp-streams-' . esc_attr($layout);
    
    ob_start();
    ?>
    <div class="kick-wp-streams-container <?php echo esc_attr($container_class); ?>">
        <?php if (!empty($streams['data'])): ?>
            <?php foreach ($streams['data'] as $stream): ?>
                <div class="kick-wp-stream-card">
                    <?php if (!empty($stream['thumbnail'])): ?>
                        <div class="kick-wp-stream-thumbnail">
                            <img src="<?php echo esc_url($stream['thumbnail']); ?>" 
                                 alt="<?php echo esc_attr($stream['username']); ?>" />
                        </div>
                    <?php endif; ?>
                    
                    <div class="kick-wp-stream-info">
                        <h3>
                            <a href="<?php echo esc_url($stream['channel_url']); ?>" 
                               target="_blank" rel="noopener noreferrer">
                                <?php echo esc_html($stream['username']); ?>
                            </a>
                        </h3>
                        
                        <?php if (!empty($stream['title'])): ?>
                            <p class="kick-wp-stream-title">
                                <?php echo esc_html($stream['title']); ?>
                            </p>
                        <?php endif; ?>
                        
                        <div class="kick-wp-stream-meta">
                            <?php if ($show_viewers && !empty($stream['viewer_count'])): ?>
                                <div class="kick-wp-viewer-count">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <?php echo number_format($stream['viewer_count']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($show_categories && !empty($stream['category'])): ?>
                                <div class="kick-wp-category-tag">
                                    <?php echo esc_html($stream['category']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <a href="<?php echo esc_url($stream['channel_url']); ?>" 
                           class="kick-wp-watch-button" 
                           target="_blank" rel="noopener noreferrer">
                            <?php esc_html_e('Ver Stream', 'kick-wp'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="kick-wp-no-streams">
                <?php esc_html_e('No hay streams disponibles en este momento.', 'kick-wp'); ?>
            </p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
