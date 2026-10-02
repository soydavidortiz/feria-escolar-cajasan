# 🎒 Feria Escolar Cajasan 2026 - Héroes por el Planeta

Sitio web de la Feria Escolar Cajasan 2026, donde los beneficiarios pueden consultar el catálogo de útiles escolares, ver requisitos de participación y gestionar su carrito de compras.

![Estado](https://img.shields.io/badge/estado-en%20desarrollo-yellow)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange)

---

## 📋 Tabla de Contenidos

- [Descripción General](#-descripción-general)
- [Estructura del Proyecto](#-estructura-del-proyecto)
- [Requisitos Previos](#-requisitos-previos)
- [Instalación](#-instalación)
- [Base de Datos](#-base-de-datos)
- [Endpoints de la API](#-endpoints-de-la-api)
- [Funcionalidades](#-funcionalidades)
- [Assets e Imágenes](#-assets-e-imágenes)
- [Estilos y Diseño](#-estilos-y-diseño)
- [Solución de Problemas](#-solución-de-problemas)
- [Próximos Pasos](#-próximos-pasos)

---

## 📖 Descripción General

Este proyecto es una plataforma web que permite a los beneficiarios de **Cajasan** (Caja de Compensación Familiar de Santander) explorar el catálogo de productos de la Feria Escolar 2026, filtrar por categorías y marcas, y agregar productos a un carrito de compras.

**Características principales:**
- 🖼️ Banner principal con imagen del evento
- 📦 Catálogo de productos con filtros por categoría y marca
- 🛒 Carrito de compras funcional (front-end)
- 🔐 Sistema de login para usuarios
- ✅ Sección de requisitos para participar (Trabajador, Beneficiario, Actualización de datos)
- 📱 Diseño responsive (móvil, tablet, escritorio)

---

## 📁 Estructura del Proyecto

```text
feria-escolar/
├── index.html                          # Página principal
├── api/
│   ├── api.php                         # Controlador único de la API (REST)
│   └── error.log                       # Log de errores del servidor
├── assets/
│   ├── css/
│   │   ├── styles.css                  # Estilos principales
│   │   ├── animations.css              # Animaciones (fade, slide, etc.)
│   │   └── responsive.css              # Media queries adicionales
│   ├── js/
│   │   └── app.js                      # Lógica del front-end (fetch, carrito, modales)
│   └── img/
│       ├── favicon.png                 # Ícono del sitio
│       ├── banner.jpeg                 # Banner principal (1920x676)
│       ├── categorias/                 # Imágenes de categorías
│       │   ├── tablets.jpg
│       │   ├── portatiles.jpg
│       │   ├── impresoras.jpg
│       │   ├── bebidas.jpg
│       │   ├── lonchera.jpg
│       │   ├── cuadernos.jpg
│       │   ├── colores.jpg
│       │   ├── lapices.jpg
│       │   └── maletines.jpg
│       ├── productos/                  # Imágenes de productos (BD)
│       │   ├── tablet-lenovo-1.jpg
│       │   ├── hp-laptop-1.jpg
│       │   ├── impresora-hp-1.jpg
│       │   ├── marcadores-faber-1.jpg
│       │   ├── cuaderno-1.jpg
│       │   ├── lapices-scribe-1.jpg
│       │   ├── mochila-1.jpg
│       │   ├── coca-cola-1.webp
│       │   └── pepsi-1.jpg
│       └── requisitos/                 # Imágenes de la sección requisitos
│           ├── actualiza-datos.jpg
│           ├── requisitos-independiente.jpg
│           └── requisitos-trabajador.jpg
├── config/
│   └── database.php                    # Configuración de conexión MySQL
├── models/
│   └── Model.php                       # Clase con toda la lógica de acceso a datos
└── README.md                           # Este archivo
```

---

## ⚙️ Requisitos Previos

| Software | Versión mínima |
|----------|----------------|
| PHP | 7.4 o superior |
| MySQL / MariaDB | 5.7 o superior |
| Servidor web | Apache / Nginx (o XAMPP / WAMP / Laragon para desarrollo local) |
| Navegador | Chrome, Firefox, Edge (actualizados) |

Extensiones PHP necesarias:
- `mysqli`
- `json`

---

## 🚀 Instalación

### 1. Clonar o descargar el proyecto

```bash
git clone <url-del-repositorio> feria-escolar
cd feria-escolar
```

### 2. Configurar el servidor local

Si usas **XAMPP/WAMP/Laragon**, coloca la carpeta del proyecto dentro de `htdocs` (o `www`):

```text
htdocs/
└── feria-escolar/
    └── ... (archivos del proyecto)
```

### 3. Configurar la base de datos

Edita el archivo `config/database.php` con tus credenciales:

```php
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'feria_escolar_cajasan');
```

### 4. Crear la base de datos

Importa el script SQL (ver sección [Base de Datos](#-base-de-datos)) usando phpMyAdmin o la línea de comandos:

```bash
mysql -u root -p feria_escolar_cajasan < feria_escolar_cajasan.sql
```

### 5. Acceder al sitio

Abre tu navegador en:

```text
http://localhost/feria-escolar/
```

---

## 🗄️ Base de Datos

Nombre sugerido: **`feria_escolar_cajasan`**

### Tablas principales

#### `categorias`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT (PK) | Identificador único |
| nombre | VARCHAR | Nombre de la categoría |
| descripcion | VARCHAR | Descripción corta |
| imagen | VARCHAR | Nombre de archivo (ruta: `assets/img/categorias/`) |
| estado | ENUM('activo','inactivo') | Estado de la categoría |

#### `marcas`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT (PK) | Identificador único |
| nombre | VARCHAR | Nombre de la marca |
| descripcion | VARCHAR | Descripción |
| logo | VARCHAR | Ruta del logo |
| estado | ENUM('activo','inactivo') | Estado de la marca |

#### `productos`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT (PK) | Identificador único |
| nombre | VARCHAR | Nombre del producto |
| descripcion | TEXT | Descripción del producto |
| precio | DECIMAL(10,2) | Precio base |
| descuento | INT | Porcentaje de descuento (0-100) |
| stock | INT | Cantidad disponible |
| categoria_id | INT (FK → categorias.id) | Categoría asociada |
| marca_id | INT (FK → marcas.id) | Marca asociada |
| estado | ENUM('activo','inactivo') | Estado del producto |

#### `imagenes_productos`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT (PK) | Identificador único |
| producto_id | INT (FK → productos.id) | Producto relacionado |
| url_imagen | VARCHAR | Nombre del archivo (ruta: `assets/img/productos/`) |
| es_principal | TINYINT(1) | 1 = imagen principal, 0 = secundaria |
| fecha_creacion | DATETIME | Fecha de registro |

#### `usuarios`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT (PK) | Identificador único |
| nombre | VARCHAR | Nombre completo |
| email | VARCHAR (UNIQUE) | Correo electrónico |
| contraseña | VARCHAR | Hash MD5 de la contraseña |
| rol | ENUM('admin','cliente') | Rol del usuario |
| estado | ENUM('activo','inactivo') | Estado de la cuenta |
| fecha_creacion | DATETIME | Fecha de registro |

#### `carritos`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT (PK) | Identificador único |
| usuario_id | INT (FK → usuarios.id) | Usuario dueño del carrito |
| producto_id | INT (FK → productos.id) | Producto agregado |
| cantidad | INT | Cantidad seleccionada |

> ⚠️ **Nota:** Se recomienda una clave única compuesta (`usuario_id`, `producto_id`) en `carritos` para que funcione correctamente el `ON DUPLICATE KEY UPDATE`.

---

## 🔌 Endpoints de la API

Todos los endpoints se consumen desde `api/api.php?accion=<nombre_accion>`

### Categorías
| Acción | Método | Descripción |
|--------|--------|-------------|
| `obtenerCategorias` | GET | Lista todas las categorías activas |
| `crearCategoria` | POST | Crea una nueva categoría |
| `actualizarCategoria` | PUT | Actualiza una categoría existente |
| `eliminarCategoria` | DELETE | Desactiva una categoría (soft delete) |

### Marcas
| Acción | Método | Descripción |
|--------|--------|-------------|
| `obtenerMarcas` | GET | Lista todas las marcas activas |
| `crearMarca` | POST | Crea una nueva marca |

### Productos
| Acción | Método | Descripción |
|--------|--------|-------------|
| `obtenerProductos` | GET | Lista productos (filtros opcionales: `categoria_id`, `marca_id`) |
| `obtenerProductoPorId` | GET | Obtiene un producto específico con sus imágenes |
| `crearProducto` | POST | Crea un nuevo producto |
| `actualizarProducto` | PUT | Actualiza un producto existente |
| `eliminarProducto` | DELETE | Desactiva un producto (soft delete) |

### Imágenes de productos
| Acción | Método | Descripción |
|--------|--------|-------------|
| `obtenerImagenesProducto` | GET | Lista imágenes de un producto (`producto_id`) |
| `agregarImagenProducto` | POST | Agrega una imagen a un producto |

### Usuarios
| Acción | Método | Descripción |
|--------|--------|-------------|
| `obtenerUsuarios` | GET | Lista todos los usuarios |
| `verificarUsuario` | POST | Valida credenciales (login) |
| `crearUsuario` | POST | Registra un nuevo usuario |

### Carrito
| Acción | Método | Descripción |
|--------|--------|-------------|
| `agregarAlCarrito` | POST | Agrega/incrementa producto en el carrito |
| `obtenerCarrito` | GET | Obtiene el carrito de un usuario (`usuario_id`) |
| `eliminarDelCarrito` | DELETE | Elimina un producto del carrito |

### Ejemplo de respuesta exitosa

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nombre": "Tablet Lenovo",
      "precio": 350000,
      "descuento": 10,
      "stock": 25,
      "categoria": "Tablets",
      "marca": "Lenovo",
      "imagenes": "tablet-lenovo-1.jpg,tablet-lenovo-2.jpg"
    }
  ]
}
```

### Ejemplo de respuesta con error

```json
{
  "success": false,
  "error": "ID de producto inválido",
  "file": "/api/api.php",
  "line": 87
}
```

---

## ✨ Funcionalidades

### 🖼️ Banner Principal
- Imagen a ancho completo (1920x676) sin degradado
- Se adapta con `aspect-ratio` en todos los dispositivos

### 📦 Categorías
- Cargadas dinámicamente desde la BD (`obtenerCategorias`)
- Imagen + nombre + descripción en cada card
- Fallback a emoji si la imagen no carga
- Al hacer clic, filtra productos y hace scroll automático

### 🛍️ Productos
- Filtro por categoría y marca (selects dinámicos)
- Muestra imagen principal desde `imagenes_productos`
- Cálculo automático de precio con descuento
- Botón "Agregar al Carrito"

### 🛒 Carrito de Compras
- Modal con tabla de productos agregados
- Edición de cantidad en tiempo real
- Cálculo automático de subtotales y total
- Eliminación de productos individuales

### 🔐 Login
- Modal de inicio de sesión
- Validación contra la API (`verificarUsuario`)
- Mensaje de bienvenida al usuario autenticado

### ✅ Requisitos
- 3 tarjetas con imagen: **Actualizar Datos**, **Del Beneficiario**, **Del Trabajador**
- Lista de requisitos específicos por categoría

---

## 🖼️ Assets e Imágenes

| Carpeta | Uso | Formato recomendado |
|---------|-----|---------------------|
| `assets/img/categorias/` | Imágenes de categorías | `.jpg`, tamaño 400x300px |
| `assets/img/productos/` | Imágenes de productos (múltiples por producto) | `.jpg` / `.webp`, tamaño 500x500px |
| `assets/img/requisitos/` | Imágenes decorativas de requisitos | `.jpg`, tamaño 400x300px |
| `assets/img/banner.jpeg` | Banner principal | `.jpeg`, 1920x676px |
| `assets/img/favicon.png` | Ícono del sitio | `.png`, 40x40px |

> 💡 **Tip:** Todas las imágenes tienen manejo de error (`onerror`) que muestra un emoji o placeholder si el archivo no existe.

---

## 🎨 Estilos y Diseño

### Paleta de colores

| Variable CSS | Color | Uso |
|---------------|-------|-----|
| `--primary-dark` | `#001a4d` | Fondo oscuro, header, footer |
| `--primary-blue` | `#003d99` | Degradados |
| `--accent-cyan` | `#00d4ff` | Acentos, títulos, botones |
| `--accent-orange` | `#ff9800` | Botones de acción (carrito, login) |
| `--white` | `#ffffff` | Fondos claros |
| `--bg-light` | `#f5f5f5` | Fondo general |

### Tipografía
- **Poppins** (principal) — pesos 300 a 800
- **Montserrat** (secundaria) — pesos 400 a 700

### Archivos CSS
| Archivo | Contenido |
|---------|-----------|
| `styles.css` | Estructura general, componentes, responsive |
| `animations.css` | Keyframes reutilizables (fadeIn, slideIn, etc.) |
| `responsive.css` | Ajustes adicionales para breakpoints específicos |

---

## 🐛 Solución de Problemas

### Error 500 en la API
1. Verifica que `config/database.php` tenga las credenciales correctas
2. Revisa que la base de datos `feria_escolar_cajasan` exista
3. Consulta el log en `api/error.log`

### `JSON.parse: unexpected end of data`
- El API está devolviendo una respuesta vacía → revisar errores PHP
- Habilita `display_errors` temporalmente para depurar

### Las imágenes no se muestran
1. Verifica que el nombre en la BD coincida **exactamente** con el archivo (mayúsculas/minúsculas)
2. Confirma que la imagen esté en la carpeta correcta (`categorias/`, `productos/` o `requisitos/`)
3. Revisa la consola del navegador → pestaña **Network** → busca error `404`

### `Uncaught TypeError: ... is null`
- Un `document.getElementById()` no encontró el elemento
- Verifica que el script se cargue **después** del HTML (`<script>` al final del `<body>`)
- Asegúrate de que los IDs en el HTML coincidan con los usados en `app.js`

---

## 🔜 Próximos Pasos

- [ ] Panel de administrador (`/admin`) para gestionar productos, categorías y marcas
- [ ] Persistencia del carrito en base de datos (actualmente solo en memoria/front-end)
- [ ] Sistema de registro de nuevos usuarios
- [ ] Integración de pasarela de pagos
- [ ] Protección de sesión con `$_SESSION`
- [ ] Optimización de imágenes (WebP, lazy loading)
- [ ] Certificado SSL para producción
- [ ] Panel de reportes de productos más solicitados

---

## 📞 Contacto

**Cajasan**
📍 Carrera 27 No. 61 - 78, Bucaramanga
📱 +57 300 910 94 14
🌐 [www.cajasan.com](https://www.cajasan.com)

---

<p align="center">
  <sub>© 2026 Cajasan. Todos los derechos reservados. | Vigilado Supersubsidio</sub>
</p>
