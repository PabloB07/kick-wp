# Kick WP Plugin

Este plugin de WordPress permite integrar streams de Kick.com en tu sitio web de manera fácil y elegante.

## Características

- 📺 Muestra streams destacados de Kick.com
- 🎮 Lista de categorías/juegos disponibles
- 🔄 Actualización automática de streams
- 💻 Panel de administración intuitivo
- 🎨 Diseño responsivo y moderno
- 🚀 Sistema de caché para mejor rendimiento
- 🌐 Preparado para internacionalización

## Instalación

1. Descarga el archivo ZIP del plugin
2. Ve a tu panel de WordPress > Plugins > Añadir nuevo
3. Haz clic en "Subir Plugin" y selecciona el archivo ZIP
4. Activa el plugin

## Uso

### Shortcode Básico

Para mostrar los streams destacados en cualquier página o post, usa el shortcode:

```
[kick_wp_streams]
```

### Panel de Administración

1. Ve a "Kick WP" en el menú lateral del panel de WordPress
2. Encontrarás tres secciones:
   - Streams Destacados: Vista previa de los streams actuales
   - Categorías: Lista de categorías/juegos disponibles
   - Configuración: Opciones del plugin

### Configuración

En la pestaña de configuración puedes ajustar:
- Duración del caché (en segundos)
- Otras opciones de visualización

## Endpoints de la API

El plugin expone los siguientes endpoints de la API de WordPress:

### 1. Obtener Streams Destacados
```
GET /wp-json/kick-wp/v1/featured
```

### 2. Obtener Información de Canal
```
GET /wp-json/kick-wp/v1/channels/{channel_name}
```

### 3. Obtener Categorías
```
GET /wp-json/kick-wp/v1/categories
```

## Estructura del Plugin

```
kick-wp/
├── admin/                     # Archivos de administración
│   ├── css/                  # Estilos de admin
│   ├── js/                   # JavaScript de admin
│   └── partials/             # Plantillas de admin
├── includes/                 # Clases principales
│   ├── class-kick-wp.php    # Clase principal
│   ├── class-kick-wp-api.php # Manejo de API
│   └── ...
├── languages/               # Archivos de traducción
├── public/                  # Archivos públicos
│   ├── css/                # Estilos públicos
│   ├── js/                 # JavaScript público
│   └── partials/           # Plantillas públicas
└── kick-wp.php             # Archivo principal del plugin
```

## Desarrollo

### Requerimientos

- WordPress 5.0 o superior
- PHP 7.4 o superior
- MySQL 5.6 o superior

### Hooks Disponibles

```php
// Filtrar streams antes de mostrarlos
apply_filters('kick_wp_streams_data', $streams);

// Filtrar categorías
apply_filters('kick_wp_categories_data', $categories);

// Acción antes de mostrar streams
do_action('kick_wp_before_streams');

// Acción después de mostrar streams
do_action('kick_wp_after_streams');
```

### CSS Personalizado

Puedes sobrescribir los estilos por defecto usando las siguientes clases:

- `.kick-wp-container`: Contenedor principal
- `.kick-wp-streams-grid`: Grid de streams
- `.kick-wp-stream-card`: Tarjeta individual de stream
- `.kick-wp-watch-button`: Botón de "Ver Stream"

## Internacionalización

El plugin está preparado para traducción. Los archivos de idioma se encuentran en la carpeta `languages/`.

## Cache

El plugin implementa un sistema de caché para evitar llamadas innecesarias a la API:

- Tiempo de caché por defecto: 5 minutos
- Configurable desde el panel de administración
- Limpieza automática del caché

## Licencia

Este plugin está licenciado bajo MIT.

## Créditos

Desarrollado por Pablo Blanco (PabloB07)
