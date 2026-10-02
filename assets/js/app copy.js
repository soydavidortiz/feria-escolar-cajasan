// ============================================================
//  FERIA ESCOLAR CAJASAN 2026 — app.js
// ============================================================

const API_URL = 'api/api.php';
const RUTA_PRODUCTOS  = 'assets/img/productos/';
const RUTA_CATEGORIAS = 'assets/img/categorias/';
const RUTA_MARCAS     = 'assets/img/marcas/';

let usuarioActual   = null;
let carrito         = JSON.parse(localStorage.getItem('carrito_cajasan') || '[]');
let productosCache  = [];   // Última respuesta de la API (para búsqueda)
let categoriasCache = [];

const ICONOS_CATEGORIAS = {
    'Tablets': '📱', 'Portátiles': '💻', 'Impresoras': '🖨️',
    'Bebidas': '🥤', 'Lonchera': '🍱',  'Cuadernos': '📓',
    'Colores': '🎨', 'Lápices': '✏️',   'Maletines': '🎒'
};

const IMG_FALLBACK = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 200 200%22%3E%3Crect fill=%22%23f7f7f7%22 width=%22200%22 height=%22200%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 font-size=%2260%22%3E📦%3C/text%3E%3C/svg%3E';

// ========== INICIALIZACIÓN ==========
document.addEventListener('DOMContentLoaded', () => {
    inicializarEventListeners();
    inicializarNavbar();
    inicializarReveal();
    inicializarBackToTop();
    actualizarContadorCarrito();

    cargarCategorias();
    cargarMarcas();
    cargarProductos();
});

// ========== EVENT LISTENERS ==========
function inicializarEventListeners() {
    const filtroCategoria = document.getElementById('filtro-categoria');
    const filtroMarca     = document.getElementById('filtro-marca');
    const btnLimpiar      = document.getElementById('btn-limpiar-filtros');
    const formLogin       = document.getElementById('form-login');
    const formNewsletter  = document.getElementById('form-newsletter');
    const inputBuscar     = document.getElementById('input-buscar');

    filtroCategoria?.addEventListener('change', cargarProductos);
    filtroMarca?.addEventListener('change', cargarProductos);
    btnLimpiar?.addEventListener('click', limpiarFiltros);
    formLogin?.addEventListener('submit', iniciarSesion);
    formNewsletter?.addEventListener('submit', suscribirNewsletter);
    inputBuscar?.addEventListener('input', e => filtrarPorTexto(e.target.value));

    // Mosaico promocional → filtra por nombre de categoría
    document.querySelectorAll('.promo-tile[data-categoria]').forEach(tile => {
        tile.addEventListener('click', e => {
            e.preventDefault();
            filtrarPorNombreCategoria(tile.dataset.categoria);
        });
    });

    // Cerrar modales con clic fuera o tecla Escape
    window.addEventListener('click', e => {
        if (e.target.classList.contains('modal')) e.target.style.display = 'none';
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal').forEach(m => m.style.display = 'none');
            cerrarBusqueda();
        }
    });
}

// ========== NAVBAR ==========
function inicializarNavbar() {
    const navbar     = document.getElementById('navbar');
    const hamburger  = document.getElementById('hamburger');
    const navMenu    = document.getElementById('nav-menu');
    const btnBuscar  = document.getElementById('btn-buscar');
    const searchBar  = document.getElementById('search-bar');
    const searchClose= document.getElementById('search-close');
    const navLinks   = document.querySelectorAll('.nav-link');
    const secciones  = document.querySelectorAll('section[id], footer[id]');

    // Menú móvil
    hamburger?.addEventListener('click', () => {
        hamburger.classList.toggle('active');
        navMenu.classList.toggle('active');
    });
    navLinks.forEach(link => link.addEventListener('click', () => {
        hamburger?.classList.remove('active');
        navMenu?.classList.remove('active');
    }));

    // Búsqueda
    btnBuscar?.addEventListener('click', () => {
        searchBar.classList.toggle('active');
        if (searchBar.classList.contains('active')) {
            document.getElementById('input-buscar')?.focus();
        }
    });
    searchClose?.addEventListener('click', cerrarBusqueda);

    // Sombra al hacer scroll + enlace activo
    window.addEventListener('scroll', () => {
        navbar?.classList.toggle('scrolled', window.scrollY > 10);

        let actual = '';
        secciones.forEach(sec => {
            if (window.scrollY >= sec.offsetTop - 160) actual = sec.id;
        });
        navLinks.forEach(link => {
            link.classList.toggle('active', link.getAttribute('href') === `#${actual}`);
        });
    });
}

function cerrarBusqueda() {
    const searchBar = document.getElementById('search-bar');
    const input     = document.getElementById('input-buscar');
    if (!searchBar) return;
    searchBar.classList.remove('active');
    if (input && input.value) {
        input.value = '';
        mostrarProductos(productosCache);
    }
}

// ========== REVEAL AL HACER SCROLL ==========
let observerReveal = null;

function inicializarReveal() {
    observerReveal = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observerReveal.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12 });

    document.querySelectorAll('.section-title, .requisito-card, .promo-tile, .beneficio, .cta-content, .newsletter-inner')
        .forEach(el => {
            el.classList.add('reveal');
            observerReveal.observe(el);
        });
}

function observarReveal(elemento) {
    elemento.classList.add('reveal');
    observerReveal ? observerReveal.observe(elemento) : elemento.classList.add('visible');
}

// ========== BOTÓN VOLVER ARRIBA ==========
function inicializarBackToTop() {
    const btn = document.getElementById('back-to-top');
    if (!btn) return;
    window.addEventListener('scroll', () => btn.classList.toggle('visible', window.scrollY > 500));
    btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
}

// ========== PETICIONES ==========
async function apiGet(accion, params = {}) {
    const query = new URLSearchParams({ accion, ...params }).toString();
    try {
        const res  = await fetch(`${API_URL}?${query}`);
        const text = await res.text();
        const data = JSON.parse(text);
        if (!data.success) console.warn(`API ${accion}:`, data.error || data.message);
        return data;
    } catch (err) {
        console.error(`Error en ${accion}:`, err);
        return { success: false, data: [] };
    }
}

// ========== CATEGORÍAS ==========
async function cargarCategorias() {
    const data = await apiGet('obtenerCategorias');
    if (!data.success) return;
    categoriasCache = data.data;
    mostrarCategorias(data.data);
    llenarFiltroCategoria(data.data);
}

function mostrarCategorias(categorias) {
    const contenedor = document.getElementById('lista-categorias');
    if (!contenedor) return;
    contenedor.innerHTML = '';

    categorias.forEach(categoria => {
        const emoji = ICONOS_CATEGORIAS[categoria.nombre] || '📦';
        const card  = document.createElement('div');
        card.className = 'categoria-card';

        const imagen = categoria.imagen
            ? `<img src="${RUTA_CATEGORIAS}${categoria.imagen}" alt="${categoria.nombre}" loading="lazy"
                    onerror="this.outerHTML='<div class=&quot;categoria-imagen-emoji&quot;>${emoji}</div>'">`
            : `<div class="categoria-imagen-emoji">${emoji}</div>`;

        card.innerHTML = `
            <div class="categoria-imagen">
                ${imagen}
                <div class="categoria-overlay"></div>
            </div>
            <div class="categoria-info">
                <h3>${categoria.nombre}</h3>
                <p>${categoria.descripcion || ''}</p>
            </div>
        `;

        card.addEventListener('click', () => {
            const filtro = document.getElementById('filtro-categoria');
            if (!filtro) return;
            filtro.value = categoria.id;
            cargarProductos();
            document.getElementById('productos')?.scrollIntoView({ behavior: 'smooth' });
        });

        contenedor.appendChild(card);
        observarReveal(card);
    });
}

function llenarFiltroCategoria(categorias) {
    const filtro = document.getElementById('filtro-categoria');
    if (!filtro) return;
    filtro.querySelectorAll('option:not([value=""])').forEach(o => o.remove());
    categorias.forEach(c => {
        const option = document.createElement('option');
        option.value = c.id;
        option.textContent = c.nombre;
        filtro.appendChild(option);
    });
}

function filtrarPorNombreCategoria(nombre) {
    const categoria = categoriasCache.find(c => c.nombre.toLowerCase() === nombre.toLowerCase());
    const filtro    = document.getElementById('filtro-categoria');
    if (filtro) {
        filtro.value = categoria ? categoria.id : '';
        cargarProductos();
    }
    document.getElementById('productos')?.scrollIntoView({ behavior: 'smooth' });
}

// ========== MARCAS ==========
async function cargarMarcas() {
    const data = await apiGet('obtenerMarcas');
    if (!data.success) return;
    llenarFiltroMarca(data.data);
    mostrarLogosMarcas(data.data);
}

function llenarFiltroMarca(marcas) {
    const filtro = document.getElementById('filtro-marca');
    if (!filtro) return;
    filtro.querySelectorAll('option:not([value=""])').forEach(o => o.remove());
    marcas.forEach(m => {
        const option = document.createElement('option');
        option.value = m.id;
        option.textContent = m.nombre;
        filtro.appendChild(option);
    });
}

function mostrarLogosMarcas(marcas) {
    const contenedor = document.getElementById('lista-marcas-logos');
    if (!contenedor) return;
    contenedor.innerHTML = '';

    marcas.forEach(marca => {
        const item = document.createElement('div');
        item.className = 'marca-logo';
        item.title = marca.nombre;

        item.innerHTML = marca.logo
            ? `<img src="${RUTA_MARCAS}${marca.logo}" alt="${marca.nombre}" loading="lazy"
                    onerror="this.outerHTML='<span>${marca.nombre}</span>'">`
            : `<span>${marca.nombre}</span>`;

        item.addEventListener('click', () => {
            const filtro = document.getElementById('filtro-marca');
            if (!filtro) return;
            filtro.value = marca.id;
            cargarProductos();
            document.getElementById('productos')?.scrollIntoView({ behavior: 'smooth' });
        });

        contenedor.appendChild(item);
        observarReveal(item);
    });
}

// ========== PRODUCTOS ==========
async function cargarProductos() {
    const contenedor      = document.getElementById('lista-productos');
    const filtroCategoria = document.getElementById('filtro-categoria');
    const filtroMarca     = document.getElementById('filtro-marca');
    if (!contenedor) return;

    mostrarSkeleton(contenedor, 4);

    const params = {};
    if (filtroCategoria?.value) params.categoria_id = filtroCategoria.value;
    if (filtroMarca?.value)     params.marca_id     = filtroMarca.value;

    const data = await apiGet('obtenerProductos', params);
    productosCache = data.success ? data.data : [];

    const texto = document.getElementById('input-buscar')?.value || '';
    texto ? filtrarPorTexto(texto) : mostrarProductos(productosCache);
}

function mostrarSkeleton(contenedor, cantidad) {
    contenedor.innerHTML = Array.from({ length: cantidad }, () => `
        <div class="skeleton-card">
            <div class="skeleton skeleton-img"></div>
            <div class="skeleton skeleton-line"></div>
            <div class="skeleton skeleton-line short"></div>
        </div>
    `).join('');
}

function filtrarPorTexto(texto) {
    const t = texto.trim().toLowerCase();
    if (!t) return mostrarProductos(productosCache);
    const filtrados = productosCache.filter(p =>
        p.nombre.toLowerCase().includes(t) ||
        (p.marca || '').toLowerCase().includes(t) ||
        (p.categoria || '').toLowerCase().includes(t)
    );
    mostrarProductos(filtrados);
}

function limpiarFiltros() {
    const fc = document.getElementById('filtro-categoria');
    const fm = document.getElementById('filtro-marca');
    const ib = document.getElementById('input-buscar');
    if (fc) fc.value = '';
    if (fm) fm.value = '';
    if (ib) ib.value = '';
    cargarProductos();
}

function obtenerImagenPrincipal(producto) {
    if (!producto.imagenes) return null;
    const primera = producto.imagenes.split(',')[0]?.trim();
    return primera ? `${RUTA_PRODUCTOS}${primera}` : null;
}

function formatearPrecio(valor) {
    return Number(valor).toLocaleString('es-CO', { maximumFractionDigits: 0 });
}

function mostrarProductos(productos) {
    const contenedor = document.getElementById('lista-productos');
    if (!contenedor) return;
    contenedor.innerHTML = '';

    if (!productos.length) {
        contenedor.innerHTML = `
            <div class="sin-productos">
                <p>No encontramos productos con esos criterios.</p>
                <button class="btn-outline" onclick="limpiarFiltros()" style="margin-top:15px">Ver todos los productos</button>
            </div>`;
        return;
    }

    productos.forEach(producto => {
        const precio      = Number(producto.precio);
        const descuento   = Number(producto.descuento) || 0;
        const stock       = Number(producto.stock) || 0;
        const precioFinal = Math.round(precio - (precio * descuento / 100));
        const imagenSrc   = obtenerImagenPrincipal(producto);
        const agotado     = stock <= 0;

        const card = document.createElement('div');
        card.className = 'producto-card';
        card.innerHTML = `
            <div class="producto-imagen">
                ${descuento > 0 ? `<span class="producto-descuento">-${descuento}%</span>` : ''}
                ${agotado ? `<span class="producto-agotado">AGOTADO</span>` : ''}
                ${imagenSrc
                    ? `<img src="${imagenSrc}" alt="${producto.nombre}" loading="lazy" onerror="this.src='${IMG_FALLBACK}'">`
                    : `<img src="${IMG_FALLBACK}" alt="${producto.nombre}">`}
            </div>
            <div class="producto-info">
                <h3>${producto.nombre}</h3>
                <p class="producto-marca">${producto.marca || 'Sin marca'}${producto.categoria ? ` · ${producto.categoria}` : ''}</p>
                <div class="producto-precio-row">
                    <div>
                        <span class="producto-precio">$${formatearPrecio(precioFinal)}</span>
                        ${descuento > 0 ? `<span class="producto-precio-antes">$${formatearPrecio(precio)}</span>` : ''}
                    </div>
                    <span class="producto-estrellas">★★★★★</span>
                </div>
                <p class="producto-stock">${agotado ? 'Sin existencias' : `Disponibles: ${stock}`}</p>
                <div class="producto-acciones">
                    <button class="btn-agregar" ${agotado ? 'disabled' : ''}>
                        ${agotado ? 'No disponible' : 'Agregar al carrito'}
                    </button>
                </div>
            </div>
        `;

        card.querySelector('.btn-agregar').addEventListener('click', () => {
            agregarAlCarrito({
                id: Number(producto.id),
                nombre: producto.nombre,
                precio: precioFinal,
                imagen: imagenSrc,
                stock
            });
        });

        contenedor.appendChild(card);
        observarReveal(card);
    });
}

// ========== CARRITO ==========
function guardarCarrito() {
    localStorage.setItem('carrito_cajasan', JSON.stringify(carrito));
}

function agregarAlCarrito(producto) {
    const existente = carrito.find(p => p.id === producto.id);

    if (existente) {
        if (existente.cantidad >= producto.stock) {
            mostrarNotificacion(`Solo hay ${producto.stock} unidades de ${producto.nombre}`);
            return;
        }
        existente.cantidad++;
    } else {
        carrito.push({ ...producto, cantidad: 1 });
    }

    guardarCarrito();
    actualizarContadorCarrito(true);
    mostrarNotificacion(`${producto.nombre} agregado al carrito`);
}

function actualizarContadorCarrito(animar = false) {
    const contador = document.getElementById('contador-carrito');
    if (!contador) return;
    contador.textContent = carrito.reduce((sum, i) => sum + i.cantidad, 0);
    if (animar) {
        contador.classList.remove('pop');
        void contador.offsetWidth; // reinicia la animación
        contador.classList.add('pop');
    }
}

function abrirCarrito() {
    const modal     = document.getElementById('carrito-modal');
    const contenido = document.getElementById('contenido-carrito');
    if (!modal || !contenido) return;

    if (!carrito.length) {
        contenido.innerHTML = `<p class="carrito-vacio">Tu carrito está vacío.</p>`;
    } else {
        contenido.innerHTML = `
            <table class="carrito-tabla">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Precio</th>
                        <th style="text-align:center">Cantidad</th>
                        <th style="text-align:right">Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    ${carrito.map((item, index) => `
                        <tr>
                            <td>
                                <div class="carrito-producto">
                                    <img src="${item.imagen || IMG_FALLBACK}" alt="${item.nombre}" onerror="this.src='${IMG_FALLBACK}'">
                                    <span>${item.nombre}</span>
                                </div>
                            </td>
                            <td>$${formatearPrecio(item.precio)}</td>
                            <td style="text-align:center">
                                <input type="number" value="${item.cantidad}" min="1" max="${item.stock || 99}"
                                       onchange="actualizarCantidad(${index}, this.value)">
                            </td>
                            <td style="text-align:right"><strong>$${formatearPrecio(item.precio * item.cantidad)}</strong></td>
                            <td style="text-align:center">
                                <button class="carrito-eliminar" onclick="eliminarDelCarrito(${index})" title="Eliminar">&times;</button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    }

    const total = carrito.reduce((sum, i) => sum + i.precio * i.cantidad, 0);
    const totalSpan = document.getElementById('total-carrito');
    if (totalSpan) totalSpan.textContent = formatearPrecio(total);

    modal.style.display = 'block';
}

function actualizarCantidad(index, cantidad) {
    const item = carrito[index];
    if (!item) return;
    let nueva = parseInt(cantidad) || 1;
    if (item.stock && nueva > item.stock) {
        nueva = item.stock;
        mostrarNotificacion(`Máximo ${item.stock} unidades disponibles`);
    }
    item.cantidad = Math.max(1, nueva);
    guardarCarrito();
    actualizarContadorCarrito();
    abrirCarrito();
}

function eliminarDelCarrito(index) {
    carrito.splice(index, 1);
    guardarCarrito();
    actualizarContadorCarrito();
    abrirCarrito();
}

function cerrarCarrito() {
    const modal = document.getElementById('carrito-modal');
    if (modal) modal.style.display = 'none';
}

function procederCompra() {
    if (!carrito.length) {
        mostrarNotificacion('Tu carrito está vacío');
        return;
    }
    if (!usuarioActual) {
        mostrarNotificacion('Inicia sesión para continuar con la compra');
        cerrarCarrito();
        abrirLogin();
        return;
    }
    alert('Proceder a compra — Integración de pago pendiente');
    carrito = [];
    guardarCarrito();
    actualizarContadorCarrito();
    cerrarCarrito();
}

// ========== AUTENTICACIÓN ==========
function abrirLogin() {
    const modal = document.getElementById('login-modal');
    if (modal) modal.style.display = 'block';
}

function cerrarLogin() {
    const modal = document.getElementById('login-modal');
    if (modal) modal.style.display = 'none';
}

function abrirRegistro() {
    mostrarNotificacion('El registro estará disponible próximamente');
}

async function iniciarSesion(e) {
    e.preventDefault();

    const emailInput = document.getElementById('email');
    const passInput  = document.getElementById('contraseña');
    if (!emailInput || !passInput) return;

    const boton = e.target.querySelector('button[type="submit"]');
    const textoOriginal = boton.textContent;
    boton.textContent = 'Ingresando...';
    boton.disabled = true;

    try {
        const res  = await fetch(`${API_URL}?accion=verificarUsuario`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: emailInput.value, contraseña: passInput.value })
        });
        const data = JSON.parse(await res.text());

        if (data.success) {
            usuarioActual = data.data;
            mostrarNotificacion(`Bienvenido/a, ${usuarioActual.nombre}`);
            cerrarLogin();
            e.target.reset();
        } else {
            marcarError(emailInput);
            marcarError(passInput);
            mostrarNotificacion(data.message || 'Credenciales inválidas');
        }
    } catch (err) {
        console.error('Error en login:', err);
        mostrarNotificacion('No se pudo conectar con el servidor');
    } finally {
        boton.textContent = textoOriginal;
        boton.disabled = false;
    }
}

function marcarError(input) {
    input.classList.remove('error');
    void input.offsetWidth;
    input.classList.add('error');
    setTimeout(() => input.classList.remove('error'), 1500);
}

// ========== NEWSLETTER ==========
function suscribirNewsletter(e) {
    e.preventDefault();
    const input = document.getElementById('newsletter-email');
    if (!input) return;

    const email = input.value.trim();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        marcarError(input);
        mostrarNotificacion('Ingresa un correo válido');
        return;
    }

    // TODO: conectar con endpoint "suscribirNewsletter" en api.php
    mostrarNotificacion('¡Gracias por suscribirte! Te mantendremos informado.');
    e.target.reset();
}

// ========== UTILIDADES ==========
function mostrarNotificacion(mensaje, duracion = 3000) {
    document.querySelectorAll('.notificacion').forEach(n => n.remove());

    const notif = document.createElement('div');
    notif.className = 'notificacion';
    notif.textContent = mensaje;
    document.body.appendChild(notif);

    setTimeout(() => {
        notif.classList.add('saliendo');
        notif.addEventListener('animationend', () => notif.remove(), { once: true });
    }, duracion);
}
