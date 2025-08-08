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

### Shortcodes

#### Shortcode Básico
Para mostrar los streams destacados en cualquier página o post:
```
[kick_wp_streams]
```

#### Mostrar Streamer Específico
Para mostrar un streamer en particular:
```
[kick_wp_streams streamer="nombredelstreamer"]
```

#### Opciones Avanzadas
El shortcode acepta varios parámetros:
```
[kick_wp_streams 
    streamer="auronplay"      # Nombre del streamer (opcional)
    count="4"                 # Número de streams a mostrar
    category="gaming"         # Categoría específica
    layout="grid"            # Estilo de visualización (grid/list)
]
```

### Uso con PHP
Para desarrolladores que quieran integrar streams en sus temas:
```php
<?php
if (function_exists('kick_wp_display_streams')) {
    // Mostrar un streamer específico
    kick_wp_display_streams(array(
        'streamer' => 'auronplay'
    ));

    // O mostrar streams destacados con opciones
    kick_wp_display_streams(array(
        'count' => 4,
        'category' => 'gaming',
        'layout' => 'grid'
    ));
}
?>
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
apply_filters('kick_wp_streams_data', $streams, $args);

// Filtrar datos de un streamer específico
apply_filters('kick_wp_streamer_data', $streamer_data, $username);

// Filtrar categorías
apply_filters('kick_wp_categories_data', $categories);

// Acción antes de mostrar streams
do_action('kick_wp_before_streams', $args);

// Acción después de mostrar streams
do_action('kick_wp_after_streams', $args);

// Acción cuando se muestra un streamer específico
do_action('kick_wp_show_streamer', $username);
```

### CSS Personalizado

Puedes sobrescribir los estilos por defecto usando las siguientes clases:

#### Contenedores
- `.kick-wp-container`: Contenedor principal
- `.kick-wp-streams-grid`: Vista en cuadrícula
- `.kick-wp-streams-list`: Vista en lista

#### Tarjetas de Stream
- `.kick-wp-stream-card`: Tarjeta individual de stream
- `.kick-wp-stream-thumbnail`: Contenedor de la miniatura
- `.kick-wp-stream-info`: Contenedor de información
- `.kick-wp-stream-title`: Título del stream
- `.kick-wp-stream-meta`: Metadatos del stream

#### Elementos Informativos
- `.kick-wp-viewer-count`: Contador de espectadores
- `.kick-wp-category-tag`: Etiqueta de categoría
- `.kick-wp-watch-button`: Botón de "Ver Stream"

#### Estados y Mensajes
- `.kick-wp-error`: Mensajes de error
- `.kick-wp-no-streams`: Mensaje cuando no hay streams

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
