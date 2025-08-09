(function($) {
    'use strict';

    /**
     * JavaScript mejorado para el admin de Kick WP
     */
    
    // Inicialización cuando el DOM está listo
    $(document).ready(function() {
        initKickWPAdmin();
    });

    /**
     * Función principal de inicialización
     */
    function initKickWPAdmin() {
        // Verificar que estamos en una página de Kick WP
        if (!$('.kick-wp-dashboard').length && !$('[id*="kick-wp"]').length) {
            return;
        }

        // Inicializar componentes
        initOAuthHandling();
        initFormValidation();
        initTooltips();
        initConfirmDialogs();
        initTestConnections();
        initAutoSave();
        
        console.log('Kick WP Admin: Inicializado correctamente');
    }

    /**
     * Manejo de OAuth y autenticación
     */
    function initOAuthHandling() {
        // Botón de conexión OAuth
        $('.kick-wp-oauth-button').on('click', function(e) {
            const $button = $(this);
            const originalText = $button.text();
            
            // Mostrar estado de carga
            $button.addClass('kick-wp-loading').text('Conectando...');
            
            // Abrir popup para OAuth
            const popup = window.open(
                this.href, 
                'kick_wp_oauth', 
                'width=600,height=700,scrollbars=yes,resizable=yes'
            );
            
            // Monitorear el popup
            const checkClosed = setInterval(() => {
                if (popup.closed) {
                    clearInterval(checkClosed);
                    $button.removeClass('kick-wp-loading').text(originalText);
                    
                    // Recargar la página después de un breve delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            }, 1000);
            
            e.preventDefault();
            return false;
        });

        // Manejo de revocación de token
        $('a[href*="revoke_token"]').on('click', function(e) {
            if (!confirm('¿Estás seguro de que quieres desconectar tu cuenta de Kick.com?')) {
                e.preventDefault();
                return false;
            }
        });

        // Auto-refresh del estado de token
        checkTokenStatus();
        setInterval(checkTokenStatus, 300000); // Cada 5 minutos
    }

    /**
     * Validación de formularios
     */
    function initFormValidation() {
        // Validación del Client ID
        $('#kick_wp_client_id').on('blur', function() {
            const clientId = $(this).val().trim();
            const $feedback = $(this).siblings('.validation-feedback');
            
            // Remover feedback anterior
            $feedback.remove();
            
            if (clientId && !/^[a-zA-Z0-9\-_]+$/.test(clientId)) {
                $(this).after('<div class="validation-feedback error">El Client ID contiene caracteres inválidos</div>');
                $(this).addClass('error');
            } else {
                $(this).removeClass('error');
            }
        });

        // Validación del cache duration
        $('#kick_wp_cache_duration').on('input', function() {
            const duration = parseInt($(this).val());
            const $feedback = $(this).siblings('.validation-feedback');
            
            $feedback.remove();
            
            if (duration < 60) {
                $(this).after('<div class="validation-feedback warning">Se recomienda un mínimo de 60 segundos</div>');
                $(this).addClass('warning');
            } else {
                $(this).removeClass('warning');
            }
        });

        // Validación del número de streams
        $('#kick_wp_streams_per_page').on('input', function() {
            const count = parseInt($(this).val());
            const $feedback = $(this).siblings('.validation-feedback');
            
            $feedback.remove();
            
            if (count > 50) {
                $(this).val(50);
                $(this).after('<div class="validation-feedback warning">Máximo 50 streams por página</div>');
            } else if (count < 1) {
                $(this).val(1);
                $(this).after('<div class="validation-feedback warning">Mínimo 1 stream por página</div>');
            }
        });
    }

    /**
     * Tooltips informativos
     */
    function initTooltips() {
        // Crear tooltips para elementos con data-tooltip
        $('[data-tooltip]').each(function() {
            const $element = $(this);
            const tooltip = $element.data('tooltip');
            
            $element.on('mouseenter', function(e) {
                showTooltip(e.pageX, e.pageY, tooltip);
            }).on('mouseleave', function() {
                hideTooltip();
            });
        });

        // Tooltips específicos para campos de configuración
        $('.form-table input, .form-table select').each(function() {
            const $input = $(this);
            const $description = $input.siblings('.description');
            
            if ($description.length) {
                $input.attr('title', $description.text().trim());
            }
        });
    }

    /**
     * Diálogos de confirmación
     */
    function initConfirmDialogs() {
        // Confirmación para limpiar caché
        $('a[href*="clear_cache"]').on('click', function(e) {
            if (!confirm('¿Estás seguro de que quieres limpiar el caché? Esto forzará la actualización de todos los datos.')) {
                e.preventDefault();
                return false;
            }
            
            // Mostrar indicador de carga
            const $link = $(this);
            $link.addClass('kick-wp-loading').text('Limpiando...');
        });

        // Confirmación para acciones destructivas
        $('.destructive-action').on('click', function(e) {
            const action = $(this).data('action') || 'realizar esta acción';
            if (!confirm(`¿Estás seguro de que quieres ${action}? Esta acción no se puede deshacer.`)) {
                e.preventDefault();
                return false;
            }
        });
    }

    /**
     * Test de conexiones
     */
    function initTestConnections() {
        // Botón de test de API (si existe)
        $('#test-api-connection').on('click', function(e) {
            e.preventDefault();
            testApiConnection();
        });

        // Auto-test al cambiar credenciales
        $('#kick_wp_client_id, #kick_wp_client_secret').on('blur', debounce(function() {
            const clientId = $('#kick_wp_client_id').val().trim();
            const clientSecret = $('#kick_wp_client_secret').val().trim();
            
            if (clientId && clientSecret) {
                validateCredentials(clientId, clientSecret);
            }
        }, 1000));
    }

    /**
     * Auto-guardado de configuraciones
     */
    function initAutoSave() {
        let autoSaveTimer;
        const $form = $('#kick-wp-settings-form');
        
        if (!$form.length) return;

        // Detectar cambios en el formulario
        $form.find('input, select, textarea').on('change input', function() {
            clearTimeout(autoSaveTimer);
            
            // Mostrar indicador de cambios no guardados
            showUnsavedChanges();
            
            // Auto-guardar después de 3 segundos de inactividad
            autoSaveTimer = setTimeout(() => {
                if (hasUnsavedChanges()) {
                    autoSaveSettings();
                }
            }, 3000);
        });

        // Prevenir salir sin guardar
        $(window).on('beforeunload', function() {
            if (hasUnsavedChanges()) {
                return 'Tienes cambios sin guardar. ¿Estás seguro de que quieres salir?';
            }
        });
    }

    /**
     * Verificar estado del token
     */
    function checkTokenStatus() {
        const tokenExpires = $('[data-token-expires]').data('token-expires');
        if (!tokenExpires) return;

        const now = Math.floor(Date.now() / 1000);
        const expires = parseInt(tokenExpires);
        
        if (expires <= now) {
            showTokenExpiredWarning();
        } else if (expires - now < 3600) { // Expira en menos de 1 hora
            showTokenExpiringWarning(expires - now);
        }
    }

    /**
     * Test de conexión con la API
     */
    function testApiConnection() {
        const $button = $('#test-api-connection');
        const $status = $('#api-connection-status');
        
        $button.addClass('kick-wp-loading').text('Probando conexión...');
        $status.removeClass('success error').addClass('testing').text('Probando conexión con la API...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'kick_wp_test_connection',
                nonce: $('#kick_wp_nonce').val()
            },
            timeout: 15000,
            success: function(response) {
                if (response.success) {
                    $status.removeClass('testing').addClass('success').text('✅ ' + response.data.message);
                } else {
                    $status.removeClass('testing').addClass('error').text('❌ ' + response.data.message);
                }
            },
            error: function() {
                $status.removeClass('testing').addClass('error').text('❌ Error de conexión');
            },
            complete: function() {
                $button.removeClass('kick-wp-loading').text('Probar Conexión');
            }
        });
    }

    /**
     * Validar credenciales OAuth
     */
    function validateCredentials(clientId, clientSecret) {
        // Mostrar indicador de validación
        const $indicator = $('<div class="validation-indicator">Validando credenciales...</div>');
        $('#kick_wp_client_secret').after($indicator);

        setTimeout(() => {
            $('.validation-indicator').remove();
            
            // Validación básica del formato
            if (clientId.length < 10 || clientSecret.length < 20) {
                $('#kick_wp_client_secret').after(
                    '<div class="validation-feedback warning">Las credenciales parecen incompletas</div>'
                );
            } else {
                $('.validation-feedback').remove();
                $('#kick_wp_client_id, #kick_wp_client_secret').removeClass('error warning');
            }
        }, 1500);
    }

    /**
     * Mostrar tooltip
     */
    function showTooltip(x, y, text) {
        hideTooltip();
        
        const $tooltip = $('<div class="kick-wp-tooltip">' + text + '</div>');
        $('body').append($tooltip);
        
        $tooltip.css({
            position: 'absolute',
            left: x + 10,
            top: y - 10,
            zIndex: 9999
        }).fadeIn(200);
    }

    /**
     * Ocultar tooltip
     */
    function hideTooltip() {
        $('.kick-wp-tooltip').remove();
    }

    /**
     * Mostrar cambios no guardados
     */
    function showUnsavedChanges() {
        if (!$('.unsaved-changes-notice').length) {
            const $notice = $('<div class="unsaved-changes-notice">Tienes cambios sin guardar</div>');
            $('.kick-wp-dashboard').prepend($notice);
        }
    }

    /**
     * Verificar si hay cambios no guardados
     */
    function hasUnsavedChanges() {
        return $('.unsaved-changes-notice').length > 0;
    }

    /**
     * Auto-guardar configuraciones
     */
    function autoSaveSettings() {
        const $form = $('#kick-wp-settings-form');
        if (!$form.length) return;

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: $form.serialize() + '&action=kick_wp_autosave',
            success: function(response) {
                $('.unsaved-changes-notice').fadeOut(300, function() {
                    $(this).remove();
                });
                
                // Mostrar confirmación temporal
                const $saved = $('<div class="auto-saved-notice">✅ Guardado automáticamente</div>');
                $('.kick-wp-dashboard').prepend($saved);
                
                setTimeout(() => {
                    $saved.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 2000);
            }
        });
    }

    /**
     * Mostrar advertencia de token expirado
     */
    function showTokenExpiredWarning() {
        if ($('.token-expired-warning').length) return;
        
        const $warning = $(
            '<div class="notice notice-error token-expired-warning">' +
                '<p><strong>Token expirado:</strong> Tu sesión con Kick.com ha expirado. ' +
                '<a href="#" class="reconnect-link">Reconectar ahora</a></p>' +
            '</div>'
        );
        
        $('.kick-wp-dashboard').prepend($warning);
        
        $warning.find('.reconnect-link').on('click', function(e) {
            e.preventDefault();
            $('.kick-wp-oauth-button').trigger('click');
        });
    }

    /**
     * Mostrar advertencia de token por expirar
     */
    function showTokenExpiringWarning(secondsLeft) {
        if ($('.token-expiring-warning').length) return;
        
        const minutes = Math.floor(secondsLeft / 60);
        const $warning = $(
            '<div class="notice notice-warning token-expiring-warning">' +
                '<p><strong>Token expirando:</strong> Tu sesión expira en ' + minutes + ' minutos. ' +
                '<a href="#" class="renew-link">Renovar ahora</a></p>' +
            '</div>'
        );
        
        $('.kick-wp-dashboard').prepend($warning);
        
        $warning.find('.renew-link').on('click', function(e) {
            e.preventDefault();
            renewToken();
        });
    }

    /**
     * Renovar token
     */
    function renewToken() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'kick_wp_renew_token',
                nonce: $('#kick_wp_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    $('.token-expiring-warning, .token-expired-warning').fadeOut(300, function() {
                        $(this).remove();
                    });
                    location.reload();
                } else {
                    alert('Error al renovar el token: ' + response.data);
                }
            }
        });
    }

    /**
     * Función debounce para limitar llamadas
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Agregar estilos CSS dinámicos
    const adminStyles = `
        <style id="kick-wp-admin-dynamic-styles">
            .kick-wp-loading {
                position: relative;
                opacity: 0.6;
                pointer-events: none;
            }
            
            .validation-feedback {
                display: block;
                font-size: 13px;
                margin-top: 5px;
                padding: 5px 8px;
                border-radius: 3px;
            }
            
            .validation-feedback.error {
                background: #ffeaea;
                color: #d63638;
                border-left: 3px solid #d63638;
            }
            
            .validation-feedback.warning {
                background: #fff8e5;
                color: #dba617;
                border-left: 3px solid #dba617;
            }
            
            .kick-wp-tooltip {
                background: #1d2327;
                color: #fff;
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 13px;
                max-width: 250px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            }
            
            .unsaved-changes-notice,
            .auto-saved-notice {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                color: #856404;
                padding: 10px 15px;
                margin: 15px 0;
                border-radius: 4px;
                animation: slideDown 0.3s ease;
            }
            
            .auto-saved-notice {
                background: #d1e7dd;
                border-color: #badbcc;
                color: #0f5132;
            }
            
            .token-expired-warning,
            .token-expiring-warning {
                animation: slideDown 0.3s ease;
            }
            
            .validation-indicator {
                display: inline-block;
                color: #666;
                font-size: 13px;
                margin-left: 10px;
                font-style: italic;
            }
            
            .validation-indicator:before {
                content: "⏳ ";
            }
            
            .input.error {
                border-color: #d63638 !important;
                box-shadow: 0 0 0 1px #d63638 !important;
            }
            
            .input.warning {
                border-color: #dba617 !important;
                box-shadow: 0 0 0 1px #dba617 !important;
            }
            
            @keyframes slideDown {
                from {
                    opacity: 0;
                    transform: translateY(-10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            @keyframes pulse {
                0%, 100% {
                    opacity: 1;
                }
                50% {
                    opacity: 0.5;
                }
            }
            
            .kick-wp-loading:after {
                content: '';
                position: absolute;
                top: 50%;
                left: 50%;
                width: 16px;
                height: 16px;
                margin: -8px 0 0 -8px;
                border: 2px solid #f3f3f3;
                border-top: 2px solid #1a73e8;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>
    `;
    
    // Insertar estilos en el head
    $(adminStyles).appendTo('head');

})(jQuery);