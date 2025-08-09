<?php
/**
 * Funcionalidad pública del plugin (Shortcodes y Frontend)
 *
 * @link       https://blancocl.vercel.app
 * @since      1.2.0
 *
 * @package    Kick_Wp
 * @subpackage Kick_Wp/public
 */

class Kick_Wp_Public {

    private $plugin_name;
    private $version;
    private $api;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->api = new Kick_Wp_Api();
        
        $this->init_shortcodes();
    }

    /**
     * Registrar shortcodes
     */
    private function init_shortcodes() {
        add_shortcode('kick_wp_streams', array($this, 'display_streams_shortcode'));
        add_shortcode('kick_wp_categories', array($this, 'display_categories_shortcode'));
        
        // Mantener compatibilidad con versiones anteriores
        add_shortcode('kick_streamer', array($this, 'display_streamer_shortcode_legacy'));
        add_shortcode('kick_featured', array($this, 'display_featured_shortcode_legacy'));
    }

    /**
     * Shortcode principal para mostrar streams
     * Uso: [kick_wp_streams count="6" layout="grid" streamer="username" category="Gaming"]
     */
    public function display_streams_shortcode($atts) {
        $atts = shortcode_atts(array(
            'count' => get_option('kick_wp_streams_per_page', 12),
            'layout' => get_option('kick_wp_layout_style', 'grid'),
            'streamer' => '',
            'category' => '',
            'show_viewers' => get_option('kick_wp_show_viewer_count', true),
            'show_categories' => get_option('kick_wp_show_categories', true)
        ), $atts, 'kick_wp_streams');

        // Sanitizar atributos
        $count = max(1, min(50, intval($atts['count'])));
        $layout = in_array($atts['layout'], array('grid', 'list')) ? $atts['layout'] : 'grid';
        $streamer = sanitize_text_field($atts['streamer']);
        $category = sanitize_text_field($atts['category']);
        $show_viewers = filter_var($atts['show_viewers'], FILTER_VALIDATE_BOOLEAN);
        $show_categories = filter_var($atts['show_categories'], FILTER_VALIDATE_BOOLEAN);

        // Obtener datos
        if (!empty($streamer)) {
            $streams_data = $this->api->get_streamer($streamer);
        } else {
            $params = array('limit' => $count);
            if (!empty($category)) {
                $params['category'] = $category;
            }
            $streams_data = $this->api->get_featured_streams($params);
        }

        if (!isset($streams_data['data']) || empty($streams_data['data'])) {
            return $this->render_no_streams_message();
        }

        return $this->render_streams_html($streams_data['data'], array(
            'layout' => $layout,
            'show_viewers' => $show_viewers,
            'show_categories' => $show_categories
        ));
    }

    /**
     * Shortcode para mostrar categorías
     * Uso: [kick_wp_categories]
     */
    public function display_categories_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 20
        ), $atts, 'kick_wp_categories');

        $categories_data = $this->api->get_categories();

        if (!isset($categories_data['data']) || empty($categories_data['data'])) {
            return '<p class="kick-wp-no-content">' . 
                   esc_html__('No se pudieron cargar las categorías en este momento.', 'kick-wp') . 
                   '</p>';
        }

        $limit = max(1, min(50, intval($atts['limit'])));
        $categories = array_slice($categories_data['data'], 0, $limit);

        return $this->render_categories_html($categories);
    }

    /**
     * Shortcode legacy para un streamer específico
     */
    public function display_streamer_shortcode_legacy($atts) {
        $atts = shortcode_atts(array(
            'username' => '',
            'layout' => 'grid'
        ), $atts, 'kick_streamer');

        return $this->display_streams_shortcode(array(
            'streamer' => $atts['username'],
            'layout' => $atts['layout'],
            'count' => 1
        ));
    }

    /**
     * Shortcode legacy para streams destacados
     */
    public function display_featured_shortcode_legacy($atts) {
        $atts = shortcode_atts(array(
            'limit' => 12,
            'layout' => 'grid'
        ), $atts, 'kick_featured');

        return $this->display_streams_shortcode(array(
            'count' => $atts['limit'],
            'layout' => $atts['layout']
        ));
    }

    /**
     * Renderizar HTML de streams
     */
    private function render_streams_html($streams, $options = array()) {
        $layout = isset($options['layout']) ? $options['layout'] : 'grid';
        $show_viewers = isset($options['show_viewers']) ? $options['show_viewers'] : true;
        $show_categories = isset($options['show_categories']) ? $options['show_categories'] : true;

        ob_start();
        ?>
        <div class="kick-wp-streams kick-wp-layout-<?php echo esc_attr($layout); ?>">
            <?php foreach ($streams as $stream): ?>
                <div class="kick-wp-stream-item">
                    <div class="kick-wp-stream-thumbnail">
                        <a href="<?php echo esc_url($stream['channel_url']); ?>" 
                           target="_blank" rel="noopener noreferrer">
                            <img src="<?php echo esc_url($stream['thumbnail']); ?>" 
                                 alt="<?php echo esc_attr($stream['username']); ?>"
                                 loading="lazy"
                                 onerror="this.src='https://via.placeholder.com/320x180?text=<?php echo urlencode($stream['username']); ?>'" />
                            <?php if ($stream['is_live']): ?>
                                <span class="kick-wp-live-indicator">
                                    <?php esc_html_e('EN VIVO', 'kick-wp'); ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </div>
                    
                    <div class="kick-wp-stream-info">
                        <h3 class="kick-wp-stream-title">
                            <a href="<?php echo esc_url($stream['channel_url']); ?>" 
                               target="_blank" rel="noopener noreferrer">
                                <?php echo esc_html($stream['username']); ?>
                            </a>
                        </h3>
                        
                        <?php if (!empty($stream['title']) && $stream['title'] !== 'Sin título'): ?>
                            <p class="kick-wp-stream-description">
                                <?php echo esc_html(wp_trim_words($stream['title'], 15)); ?>
                            </p>
                        <?php endif; ?>
                        
                        <div class="kick-wp-stream-meta">
                            <?php if ($show_viewers && $stream['viewer_count'] > 0): ?>
                                <span class="kick-wp-viewers">
                                    <span class="kick-wp-icon">👁</span>
                                    <?php echo number_format_i18n($stream['viewer_count']); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($show_categories && !empty($stream['category']) && $stream['category'] !== 'Sin categoría'): ?>
                                <span class="kick-wp-category">
                                    <?php echo esc_html($stream['category']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <a href="<?php echo esc_url($stream['channel_url']); ?>" 
                           class="kick-wp-watch-btn" 
                           target="_blank" rel="noopener noreferrer">
                            <?php esc_html_e('Ver Stream', 'kick-wp'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar HTML de categorías
     */
    private function render_categories_html($categories) {
        ob_start();
        ?>
        <div class="kick-wp-categories">
            <?php foreach ($categories as $category): ?>
                <div class="kick-wp-category-item">
                    <div class="kick-wp-category-info">
                        <h4 class="kick-wp-category-name">
                            <?php echo esc_html($category['name']); ?>
                        </h4>
                        
                        <?php if (isset($category['viewers']) && $category['viewers'] > 0): ?>
                            <div class="kick-wp-category-viewers">
                                <span class="kick-wp-icon">👁</span>
                                <?php echo number_format_i18n($category['viewers']); ?>
                                <?php esc_html_e('espectadores', 'kick-wp'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Mensaje cuando no hay streams
     */
    private function render_no_streams_message() {
        return '<div class="kick-wp-no-streams">' .
               '<p>' . esc_html__('No hay streams disponibles en este momento.', 'kick-wp') . '</p>' .
               '</div>';
    }

    /**
     * Cargar estilos
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/kick-wp-public.css',
            array(),
            $this->version,
            'all'
        );

        // Estilos inline para shortcodes
        $custom_css = "
            .kick-wp-streams {
                display: grid;
                gap: 20px;
                margin: 20px 0;
            }
            
            .kick-wp-layout-grid {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            }
            
            .kick-wp-layout-list {
                grid-template-columns: 1fr;
            }
            
            .kick-wp-stream-item {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 8px;
                overflow: hidden;
                transition: transform 0.2s, box-shadow 0.2s;
                position: relative;
            }
            
            .kick-wp-stream-item:hover {
                transform: translateY(-4px);
                box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            }
            
            .kick-wp-stream-thumbnail {
                position: relative;
                width: 100%;
                padding-bottom: 56.25%; /* 16:9 aspect ratio */
                overflow: hidden;
            }
            
            .kick-wp-stream-thumbnail img {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            
            .kick-wp-live-indicator {
                position: absolute;
                top: 10px;
                left: 10px;
                background: #ff4444;
                color: white;
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 12px;
                font-weight: bold;
                text-shadow: 0 1px 2px rgba(0,0,0,0.5);
            }
            
            .kick-wp-stream-info {
                padding: 15px;
            }
            
            .kick-wp-stream-title {
                margin: 0 0 8px 0;
                font-size: 18px;
                line-height: 1.3;
            }
            
            .kick-wp-stream-title a {
                text-decoration: none;
                color: #333;
                transition: color 0.2s;
            }
            
            .kick-wp-stream-title a:hover {
                color: #007cba;
            }
            
            .kick-wp-stream-description {
                color: #666;
                font-size: 14px;
                line-height: 1.4;
                margin: 0 0 12px 0;
            }
            
            .kick-wp-stream-meta {
                display: flex;
                align-items: center;
                gap: 12px;
                margin: 12px 0;
                font-size: 13px;
                color: #777;
            }
            
            .kick-wp-viewers {
                display: flex;
                align-items: center;
                gap: 4px;
            }
            
            .kick-wp-category {
                background: #f0f0f0;
                padding: 2px 8px;
                border-radius: 12px;
                color: #555;
            }
            
            .kick-wp-watch-btn {
                display: inline-block;
                background: #1a73e8;
                color: white;
                padding: 8px 16px;
                border-radius: 6px;
                text-decoration: none;
                font-size: 14px;
                font-weight: 500;
                transition: background-color 0.2s;
            }
            
            .kick-wp-watch-btn:hover {
                background: #1557b0;
                color: white;
                text-decoration: none;
            }
            
            .kick-wp-categories {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin: 20px 0;
            }
            
            .kick-wp-category-item {
                background: #f8f9fa;
                border: 1px solid #e9ecef;
                border-radius: 8px;
                padding: 15px;
                text-align: center;
                transition: background-color 0.2s;
            }
            
            .kick-wp-category-item:hover {
                background: #e9ecef;
            }
            
            .kick-wp-category-name {
                margin: 0 0 8px 0;
                font-size: 16px;
                color: #333;
            }
            
            .kick-wp-category-viewers {
                color: #666;
                font-size: 14px;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 4px;
            }
            
            .kick-wp-no-streams,
            .kick-wp-no-content {
                text-align: center;
                padding: 40px 20px;
                color: #666;
                font-style: italic;
            }
            
            .kick-wp-icon {
                opacity: 0.7;
            }
            
            /* Responsive */
            @media (max-width: 768px) {
                .kick-wp-layout-grid {
                    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                }
                
                .kick-wp-stream-meta {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 6px;
                }
                
                .kick-wp-categories {
                    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                }
            }
            
            @media (max-width: 480px) {
                .kick-wp-layout-grid {
                    grid-template-columns: 1fr;
                }
                
                .kick-wp-categories {
                    grid-template-columns: 1fr;
                }
                
                .kick-wp-stream-info {
                    padding: 12px;
                }
            }
        ";

        wp_add_inline_style($this->plugin_name, $custom_css);
    }

    /**
     * Cargar scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/kick-wp-public.js',
            array('jquery'),
            $this->version,
            true
        );

        // Pasar datos al JavaScript
        wp_localize_script($this->plugin_name, 'kick_wp_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kick_wp_nonce'),
            'strings' => array(
                'error' => __('Error al cargar contenido', 'kick-wp'),
                'loading' => __('Cargando...', 'kick-wp')
            )
        ));
    }
}