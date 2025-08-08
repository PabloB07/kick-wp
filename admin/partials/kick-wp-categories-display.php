<?php
/**
 * Provide a admin area view for the plugin's categories page
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

    <div class="kick-wp-categories-container">
        <?php
        // Verificar si hay categorías para mostrar
        if (!empty($categories['data'])) {
            echo '<div class="kick-wp-categories-grid">';
            foreach ($categories['data'] as $category) {
                ?>
                <div class="kick-wp-category-card">
                    <h3><?php echo esc_html($category['name']); ?></h3>
                    <?php if (isset($category['slug'])): ?>
                        <p class="category-slug">Slug: <?php echo esc_html($category['slug']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($category['viewers'])): ?>
                        <p class="category-viewers">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php echo number_format($category['viewers']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <div class="category-actions">
                        <button class="button button-secondary" data-category-id="<?php echo esc_attr($category['id']); ?>">
                            <?php esc_html_e('Ver Streams', 'kick-wp'); ?>
                        </button>
                    </div>
                </div>
                <?php
            }
            echo '</div>';
        } else {
            echo '<div class="notice notice-warning"><p>' . 
                esc_html__('No se encontraron categorías.', 'kick-wp') . 
                '</p></div>';
        }
        ?>
    </div>
</div>
