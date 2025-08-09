<?php
/**
 * Funcionalidad pública con soporte para streams seguidos
 */

class Kick_Wp_Public {
    
    private $plugin_name;
    private $version;
    private $api;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->api = new Kick_Wp_Api();
        
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Registrar shortcodes
        add_shortcode('kick_wp_streams', array($this, 'render_streams_shortcode'));
        add_shortcode('kick_streamer', array($this, 'render_streamer_shortcode'));
        add_shortcode('kick_followed', array($this, 'render_followed_shortcode')); // Nuevo
    }

    public function enqueue_styles() {
        wp_enqueue_style('dashicons');
        
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/kick-wp-public.css',
            array('dashicons'),
            $this->version,
            'all'
        );
    }

    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/kick-wp-public.js',
            array('jquery'),
            $this->version,
            true
        );
    }

    /**
     * Shortcode principal con soporte para streams seguidos
     */
    public function render_streams_shortcode($atts = array(), $content = null) {
        $atts = shortcode_atts(array(
            'count' => get_option('kick_wp_streams_per_page', 4),
            'layout' => get_option('kick_wp_layout_style', 'grid'),
            'type' => get_option('kick_wp_default_stream_type', 'featured'), // Nuevo
            'category' => '',
            'streamer' => ''
        ), $atts, 'kick_wp_streams');

        // Validar parámetros
        $count = absint($atts['count']);
        if ($count < 1 || $count > 24) {
            $count = 4;
        }

        $layout = in_array($atts['layout'], array('grid', 'list')) ? $atts['layout'] : 'grid';
        $type = in_array($atts['type'], array('featured', 'followed')) ? $atts['type'] : 'featured';

        try {
            $streams_data = array();
            
            // Si se especifica un streamer específico
            if (!empty($atts['streamer'])) {
                $streamer = sanitize_text_field($atts['streamer']);
                $response = $this->api->get_streamer($streamer);
                
                if (isset($response['error'])) {
                    return $this->render_error_message($response['error']);
                }
                
                $streams_data = $response;
            } 
            // Si se solicitan streams seguidos
            elseif ($type === 'followed') {
                $auth_token = get_option('kick_wp_auth_token', '');
                
                if (empty($auth_token)) {
                    return $this->render_auth_required_message();
                }
                
                $response = $this->api->get_followed_streams();
                
                if (isset($response['error'])) {
                    // Si hay error con followed, mostrar mensaje pero usar datos de fallback
                    $error_notice = $this->render_warning_message($response['error']);
                    $streams_data = array('data' => $response['data'] ?? array());
                    
                    return $error_notice . $this->render_streams_html($streams_data, array(
                        'layout' => $layout,
                        'show_viewers' => get_option('kick_wp_show_viewer_count', true),
                        'show_categories' => get_option('kick_wp_show_categories', true)
                    ));
                }
                
                $streams_data = $response;
            }
            // Streams destacados por defecto
            else {
                $response = $this->api->get_featured_streams(array(
                    'limit' => $count,
                    'category' => sanitize_text_field($atts['category'])
                ));
                
                if (isset($response['error'])) {
                    // Si hay error, usar datos de fallback pero mostrar aviso
                    $error_notice = $this->render_warning_message($response['error']);
                    $streams_data = array('data' => $response['data'] ?? array());
                    
                    return $error_notice . $this->render_streams_html($streams_data, array(
                        'layout' => $layout,
                        'show_viewers' => get_option('kick_wp_show_viewer_count', true),
                        'show_categories' => get_option('kick_wp_show_categories', true)
                    ));
                }
                
                $streams_data = $response;
            }

            return $this->render_streams_html($streams_data, array(
                'layout' => $layout,
                'show_viewers' => get_option('kick_wp_show_viewer_count', true),
                'show_categories' => get_option('kick_wp_show_categories', true),
                'count' => $count
            ));

        } catch (Exception $e) {
            error_log('Kick WP Shortcode Error: ' . $e->getMessage());
            return $this->render_error_message(__('Error al cargar los streams.', 'kick-wp'));
        }
    }

    /**
     * Shortcode específico para streamers
     */
    public function render_streamer_shortcode($atts = array(), $content = null) {
        if (empty($atts['username']) && empty($atts['streamer'])) {
            return $this->render_error_message(__('Se requiere especificar un username.', 'kick-wp'));
        }
        
        if (!empty($atts['username'])) {
            $atts['streamer'] = $atts['username'];
        }
        
        return $this->render_streams_shortcode($atts, $content);
    }

    /**
     * Shortcode específico para streams seguidos
     */
    public function render_followed_shortcode($atts = array(), $content = null) {
        $atts['type'] = 'followed';
        return $this->render_streams_shortcode($atts, $content);
    }

    /**
     * Renderizar HTML de streams
     */
    private function render_streams_html($streams, $args = array()) {
        $defaults = array(
            'layout' => 'grid',
            'show_viewers' => true,
            'show_categories' => true,
            'count' => 4
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Limitar número de streams si se especifica
        if (isset($args['count']) && !empty($streams['data'])) {
            $streams['data'] = array_slice($streams['data'], 0, $args['count']);
        }
        
        ob_start();
        ?>
        <div class="kick-wp-streams-container kick-wp-layout-<?php echo esc_attr($args['layout']); ?>">
            <?php if (!empty($streams['data']) && is_array($streams['data'])): ?>
                <?php foreach ($streams['data'] as $stream): ?>
                    <div class="kick-wp-stream-card">
                        <!-- Thumbnail con estado live -->
                        <div class="kick-wp-stream-thumbnail">
                            <?php
                            $thumbnail = !empty($stream['thumbnail']) 
                                ? $stream['thumbnail'] 
                                : 'https://via.placeholder.com/320x180?text=' . urlencode($stream['username'] ?? 'Stream');
                            ?>
                            <img src="<?php echo esc_url($thumbnail); ?>" 
                                 alt="<?php echo esc_attr($stream['username'] ?? 'Stream'); ?>"
                                 loading="lazy"
                                 onerror="this.src='https://via.placeholder.com/320x180?text=<?php echo urlencode($stream['username'] ?? 'No+Image'); ?>'" />
                            
                            <?php if (!empty($stream['is_live'])): ?>
                                <span class="kick-wp-live-badge">
                                    <span class="live-dot"></span>
                                    <?php esc_html_e('EN VIVO', 'kick-wp'); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($args['show_viewers'] && !empty($stream['viewer_count'])): ?>
                                <span class="kick-wp-viewer-overlay">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <?php echo number_format_i18n($stream['viewer_count']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Información del stream -->
                        <div class="kick-wp-stream-info">
                            <h3 class="kick-wp-stream-title">
                                <a href="<?php echo esc_url($stream['channel_url'] ?? '#'); ?>" 
                                   target="_blank" 
                                   rel="noopener noreferrer">
                                    <?php echo esc_html($stream['username'] ?? 'Unknown'); ?>
                                </a>
                            </h3>
                            
                            <?php if (!empty($stream['title']) && $stream['title'] !== $stream['username']): ?>
                                <p class="kick-wp-stream-description">
                                    <?php echo esc_html(wp_trim_words($stream['title'], 12)); ?>
                                </p>
                            <?php endif; ?>
                            
                            <!-- Meta información -->
                            <div class="kick-wp-stream-meta">
                                <?php if ($args['show_categories'] && !empty($stream['category'])): ?>
                                    <span class="kick-wp-category-tag">
                                        <?php echo esc_html($stream['category']); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($stream['followers'])): ?>
                                    <span class="kick-wp-followers">
                                        <span class="dashicons dashicons-groups"></span>
                                        <?php echo number_format_i18n($stream['followers']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Botón de acción -->
                            <a href="<?php echo esc_url($stream['channel_url'] ?? '#'); ?>" 
                               class="kick-wp-watch-button <?php echo !empty($stream['is_live']) ? 'is-live' : 'is-offline'; ?>" 
                               target="_blank" 
                               rel="noopener noreferrer">
                                <?php 
                                if (!empty($stream['is_live'])) {
                                    esc_html_e('Ver Stream', 'kick-wp');
                                } else {
                                    esc_html_e('Ver Canal', 'kick-wp');
                                }
                                ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="kick-wp-no-streams">
                    <div class="no-streams-icon">📺</div>
                    <h3><?php esc_html_e('No hay streams disponibles', 'kick-wp'); ?></h3>
                    <p><?php esc_html_e('No se encontraron streams en este momento.', 'kick-wp'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Estilos adicionales para mejorar la presentación -->
        <style>
        .kick-wp-live-badge {
            position: absolute;
            top: 8px;
            left: 8px;
            background: linear-gradient(45deg, #ff3333, #ff6666);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 4px;
            z-index: 2;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .live-dot {
            width: 6px;
            height: 6px;
            background: white;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(0.9); }
        }
        
        .kick-wp-viewer-overlay {
            position: absolute;
            bottom: 8px;
            right: 8px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .kick-wp-viewer-overlay .dashicons {
            font-size: 12px;
            width: 12px;
            height: 12px;
        }
        
        .kick-wp-followers {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            color: #666;
            font-size: 12px;
        }
        
        .kick-wp-followers .dashicons {
            font-size: 14px;
            width: 14px;
            height: 14px;
        }
        
        .kick-wp-watch-button.is-live {
            background: linear-gradient(135deg, #00ff88 0%, #00cc6a 100%);
            animation: subtle-glow 3s ease-in-out infinite alternate;
        }
        
        .kick-wp-watch-button.is-offline {
            background: linear-gradient(135deg, #666 0%, #555 100%);
        }
        
        @keyframes subtle-glow {
            0% { box-shadow: 0 4px 12px rgba(0, 255, 136, 0.3); }
            100% { box-shadow: 0 6px 20px rgba(0, 255, 136, 0.5); }
        }
        
        .no-streams-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        
        .kick-wp-no-streams h3 {
            margin: 0 0 8px 0;
            color: #333;
        }
        
        .kick-wp-no-streams p {
            margin: 0;
            color: #666;
        }
        </style>
        <?php
        
        return ob_get_clean();
    }

    /**
     * Mensaje cuando se requiere autenticación
     */
    private function render_auth_required_message() {
        ob_start();
        ?>
        <div class="kick-wp-auth-required">
            <div class="auth-icon">🔒</div>
            <h3><?php esc_html_e('Autenticación Requerida', 'kick-wp'); ?></h3>
            <p>
                <?php esc_html_e('Para ver tus streams seguidos, necesitas configurar tu token de autenticación en la configuración del plugin.', 'kick-wp'); ?>
            </p>
            <?php if (current_user_can('manage_options')): ?>
                <a href="<?php echo admin_url('admin.php?page=kick-wp-settings'); ?>" 
                   class="kick-wp-config-button">
                    <?php esc_html_e('Configurar Ahora', 'kick-wp'); ?>
                </a>
            <?php endif; ?>
        </div>
        
        <style>
        .kick-wp-auth-required {
            text-align: center;
            padding: 30px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .auth-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        
        .kick-wp-auth-required h3 {
            margin: 0 0 12px 0;
            color: white;
        }
        
        .kick-wp-auth-required p {
            margin: 0 0 20px 0;
            opacity: 0.9;
            line-height: 1.5;
        }
        
        .kick-wp-config-button {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .kick-wp-config-button:hover {
            background: rgba(255,255,255,0.3);
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Mensaje de advertencia
     */
    private function render_warning_message($message) {
        ob_start();
        ?>
        <div class="kick-wp-warning">
            <div class="warning-icon">⚠️</div>
            <div class="warning-content">
                <strong><?php esc_html_e('Aviso:', 'kick-wp'); ?></strong>
                <?php echo esc_html($message); ?>
                <br>
                <small><?php esc_html_e('Mostrando datos de ejemplo mientras tanto.', 'kick-wp'); ?></small>
            </div>
        </div>
        
        <style>
        .kick-wp-warning {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 12px 16px;
            margin: 0 0 20px 0;
            font-size: 14px;
        }
        
        .warning-icon {
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .warning-content {
            color: #856404;
            line-height: 1.4;
        }
        
        .warning-content strong {
            color: #664d03;
        }
        
        .warning-content small {
            opacity: 0.8;
            font-size: 12px;
        }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Mensaje de error
     */
    private function render_error_message($message) {
        ob_start();
        ?>
        <div class="kick-wp-error">
            <div class="error-icon">❌</div>
            <div class="error-content">
                <strong><?php esc_html_e('Error:', 'kick-wp'); ?></strong>
                <?php echo esc_html($message); ?>
            </div>
        </div>
        
        <style>
        .kick-wp-error {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 6px;
            padding: 12px 16px;
            margin: 20px 0;
            font-size: 14px;
        }
        
        .error-icon {
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .error-content {
            color: #721c24;
            line-height: 1.4;
        }
        
        .error-content strong {
            color: #491217;
        }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtener configuración de visualización
     */
    private function get_display_options() {
        return array(
            'show_viewers' => get_option('kick_wp_show_viewer_count', true),
            'show_categories' => get_option('kick_wp_show_categories', true),
            'layout' => get_option('kick_wp_layout_style', 'grid'),
            'streams_per_page' => get_option('kick_wp_streams_per_page', 4),
            'default_type' => get_option('kick_wp_default_stream_type', 'featured')
        );
    }
}