<?php

/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       https://blancocl.vercel.app
 * @since      1.0.0
 *
 * @package    Kick_Wp
 * @subpackage Kick_Wp/public/partials
 */

$api = new Kick_Wp_Api();
$featured_streams = $api->get_featured_streams();
?>

<div class="kick-wp-container">
    <h2>Streams Destacados en Kick.com</h2>
    
    <?php if (is_wp_error($featured_streams)) : ?>
        <p class="kick-wp-error">Error al cargar los streams destacados.</p>
    <?php else : ?>
        <div class="kick-wp-streams-grid">
            <?php foreach ($featured_streams['data'] as $stream) : ?>
                <div class="kick-wp-stream-card">
                    <?php if (!empty($stream['thumbnail'])) : ?>
                        <img src="<?php echo esc_url($stream['thumbnail']); ?>" alt="<?php echo esc_attr($stream['username']); ?>">
                    <?php endif; ?>
                    <h3><?php echo esc_html($stream['username']); ?></h3>
                    <p class="kick-wp-stream-title"><?php echo esc_html($stream['session_title']); ?></p>
                    <p class="kick-wp-viewer-count"><?php echo esc_html($stream['viewer_count']); ?> viewers</p>
                    <a href="https://kick.com/<?php echo esc_attr($stream['username']); ?>" target="_blank" class="kick-wp-watch-button">
                        Ver Stream
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
