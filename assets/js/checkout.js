// ============================================================
//  FERIA ESCOLAR CAJASAN 2026 — checkout.js
//  Lee el carrito de localStorage, valida el formulario,
//  permite login inline y envía el pedido a api.php
// ============================================================

const API_URL     = 'api/api.php';
const CART_KEY    = 'carrito_cajasan';
const USER_KEY    = 'usuario_cajasan';
const IMG_FALLBACK = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 200 200%22%3E%3Crect fill=%22%23f7f7f7%22 width=%22200%22 height=%22200%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 font-size=%2260%22%3E📦%3C/text%3E%3C/svg%3E';

let carrito       = JSON.parse(localStorage.getItem(CART_KEY) || '[]');
let usuarioActual = JSON.parse(localStorage.getItem(USER_KEY) || 'null');

// Atajos
const $  = (sel) => document.querySelector(sel);
const $$ = (sel) => document.querySelectorAll(sel);

// ========== INICIALIZACIÓN ==========
document.addEventListener('DOMContentLoaded', () => {
    inicializarNavbar();
    inicializarEventos();
    aplicarUsuario();
    renderizarCarrito();
});

// ========== NAVBAR (versión reducida para esta página) ==========
function inicializarNavbar() {
    const navbar    = $('#navbar');
    const hamburger = $('#hamburger');
    const navMenu   = $('#nav-menu');

    hamburger?.addEventListener('click', () => {
        hamburger.classList.toggle('active');
        navMenu?.classList.toggle('active');
    });

    window.addEventListener('scroll', () => {
        navbar?.classList.toggle('scrolled', window.scrollY > 10);
    });
}

// ========== EVENTOS ==========
function inicializarEventos() {
    // Login inline
    $('#toggle-login')?.addEventListener('click', (e) => {
        e.preventDefault();
        const form = $('#form-login-inline');
        form.hidden = !form.hidden;
        if (!form.hidden) $('#login-email')?.focus();
    });
    $('#form-login-inline')?.addEventListener('submit', iniciarSesion);
    $('#cerrar-sesion')?.addEventListener('click', (e) => {
        e.preventDefault();
        cerrarSesion();
    });

    // Crear cuenta → mostrar contraseña
    $('#crear-cuenta')?.addEventListener('change', (e) => {
        const bloque = $('#bloque-password');
        bloque.hidden = !e.target.checked;
        $('#password').required = e.target.checked;
        if (!e.target.checked) $('#password').value = '';
    });

    // Validación en vivo: quita el error al escribir
    $$('#form-checkout input, #form-checkout select, #form-checkout textarea').forEach(campo => {
        campo.addEventListener('input', () => limpiarError(campo));
        campo.addEventListener('change', () => limpiarError(campo));
    });

    // Solo números en documento y teléfono
    ['#documento', '#telefono'].forEach(sel => {
        $(sel)?.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^\d\s+]/g, '');
        });
    });

    // Enviar pedido
    $('#form-checkout')?.addEventListener('submit', enviarPedido);
}

// ========== CARRITO ==========
function guardarCarrito() {
    localStorage.setItem(CART_KEY, JSON.stringify(carrito));
}

function formatearPrecio(valor) {
    return Number(valor).toLocaleString('es-CO', { maximumFractionDigits: 0 });
}

function renderizarCarrito() {
    const vacio    = $('#checkout-empty');
    const wrapper  = $('#checkout-wrapper');
    const tbody    = $('#order-items');
    const contador = $('#contador-carrito');

    const totalUnidades = carrito.reduce((s, i) => s + i.cantidad, 0);
    if (contador) contador.textContent = totalUnidades;

    if (!carrito.length) {
        vacio.hidden   = false;
        wrapper.hidden = true;
        return;
    }

    vacio.hidden   = true;
    wrapper.hidden = false;

    tbody.innerHTML = carrito.map((item, index) => `
        <tr>
            <td>
                <div class="order-product">
                    <img src="${item.imagen || IMG_FALLBACK}" alt="${escapar(item.nombre)}" onerror="this.src='${IMG_FALLBACK}'">
                    <div class="order-product-name">
                        ${escapar(item.nombre)}
                        <small>$${formatearPrecio(item.precio)} c/u</small>
                    </div>
                </div>
            </td>
            <td class="text-center">
                <div class="order-qty">
                    <button type="button" onclick="cambiarCantidad(${index}, -1)" aria-label="Quitar uno">−</button>
                    <span>${item.cantidad}</span>
                    <button type="button" onclick="cambiarCantidad(${index}, 1)" aria-label="Agregar uno">+</button>
                </div>
                <button type="button" class="order-remove" onclick="eliminarItem(${index})" title="Eliminar">&times;</button>
            </td>
            <td class="text-right order-subtotal-cell">$${formatearPrecio(item.precio * item.cantidad)}</td>
        </tr>
    `).join('');

    actualizarTotales();
}

function actualizarTotales() {
    const subtotal  = carrito.reduce((s, i) => s + i.precio * i.cantidad, 0);
    const descuento = 0; // El descuento ya viene aplicado en el precio de cada producto
    const total     = subtotal - descuento;

    $('#order-subtotal').textContent  = formatearPrecio(subtotal);
    $('#order-descuento').textContent = formatearPrecio(descuento);
    $('#order-total').textContent     = formatearPrecio(total);
}

function cambiarCantidad(index, delta) {
    const item = carrito[index];
    if (!item) return;

    const nueva = item.cantidad + delta;

    if (nueva < 1) {
        eliminarItem(index);
        return;
    }
    if (item.stock && nueva > item.stock) {
        mostrarNotificacion(`Solo hay ${item.stock} unidades disponibles`);
        return;
    }

    item.cantidad = nueva;
    guardarCarrito();
    renderizarCarrito();
}

function eliminarItem(index) {
    const nombre = carrito[index]?.nombre;
    carrito.splice(index, 1);
    guardarCarrito();
    renderizarCarrito();
    if (nombre) mostrarNotificacion(`${nombre} eliminado del pedido`);
}

// ========== SESIÓN ==========
async function iniciarSesion(e) {
    e.preventDefault();
    const email = $('#login-email').value.trim();
    const pass  = $('#login-pass').value;
    const boton = e.target.querySelector('button');

    boton.disabled = true;
    boton.textContent = 'Ingresando…';

    try {
        const res  = await fetch(`${API_URL}?accion=verificarUsuario`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, contraseña: pass })
        });
        const data = await res.json();

        if (data.success) {
            usuarioActual = data.data;
            localStorage.setItem(USER_KEY, JSON.stringify(usuarioActual));
            aplicarUsuario();
            e.target.reset();
            $('#form-login-inline').hidden = true;
            mostrarNotificacion(`Bienvenido/a, ${usuarioActual.nombre}`);
        } else {
            mostrarNotificacion(data.message || 'Correo o contraseña incorrectos');
            marcarError($('#login-email'));
            marcarError($('#login-pass'));
        }
    } catch (err) {
        console.error(err);
        mostrarNotificacion('No se pudo conectar con el servidor');
    } finally {
        boton.disabled = false;
        boton.textContent = 'Ingresar';
    }
}

function cerrarSesion() {
    usuarioActual = null;
    localStorage.removeItem(USER_KEY);
    aplicarUsuario();
    mostrarNotificacion('Sesión cerrada');
}

/** Muestra/oculta avisos y autocompleta el formulario con el usuario logueado */
function aplicarUsuario() {
    const noticeLogin   = $('.checkout-notice:not(.checkout-notice-success)');
    const noticeUsuario = $('#notice-usuario');
    const bloqueCuenta  = $('#bloque-crear-cuenta');

    if (usuarioActual) {
        noticeLogin.hidden   = true;
        noticeUsuario.hidden = false;
        bloqueCuenta.hidden  = true;
        $('#notice-usuario-nombre').textContent = usuarioActual.nombre;

        // Autocompletar nombre y correo
        const partes = (usuarioActual.nombre || '').trim().split(/\s+/);
        if (partes.length > 1) {
            $('#nombres').value   = partes.slice(0, Math.ceil(partes.length / 2)).join(' ');
            $('#apellidos').value = partes.slice(Math.ceil(partes.length / 2)).join(' ');
        } else {
            $('#nombres').value = usuarioActual.nombre || '';
        }
        $('#email').value = usuarioActual.email || '';
        $('#email').readOnly = true;

        // Desactivar "crear cuenta"
        $('#crear-cuenta').checked = false;
        $('#bloque-password').hidden = true;
        $('#password').required = false;
    } else {
        noticeLogin.hidden   = false;
        noticeUsuario.hidden = true;
        bloqueCuenta.hidden  = false;
        $('#email').readOnly = false;
    }
}

// ========== VALIDACIÓN ==========
function validarFormulario() {
    let valido = true;
    let primerError = null;

    const reglas = [
        { id: 'nombres',        test: v => v.length >= 2,                        msg: 'Ingresa tus nombres' },
        { id: 'apellidos',      test: v => v.length >= 2,                        msg: 'Ingresa tus apellidos' },
        { id: 'documento',      test: v => /^\d{5,20}$/.test(v),                 msg: 'Documento inválido (solo números)' },
        { id: 'email',          test: v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v), msg: 'Correo electrónico inválido' },
        { id: 'telefono',       test: v => v.replace(/\D/g, '').length >= 7,     msg: 'Teléfono inválido' },
        { id: 'ciudad',         test: v => v.length >= 3,                        msg: 'Ingresa tu ciudad' },
        { id: 'direccion',      test: v => v.length >= 5,                        msg: 'Ingresa tu dirección' },
        { id: 'punto-entrega',  test: v => v !== '',                             msg: 'Selecciona un punto de entrega' },
    ];

    if ($('#crear-cuenta').checked && !usuarioActual) {
        reglas.push({ id: 'password', test: v => v.length >= 6, msg: 'La contraseña debe tener mínimo 6 caracteres' });
    }

    reglas.forEach(({ id, test, msg }) => {
        const campo = $(`#${id}`);
        if (!campo) return;
        if (!test(campo.value.trim())) {
            marcarError(campo, msg);
            valido = false;
            primerError ??= campo;
        }
    });

    // Términos
    const terminos = $('#acepta-terminos');
    if (!terminos.checked) {
        mostrarErrorGlobal('Debes aceptar los términos y condiciones para continuar.');
        valido = false;
        primerError ??= terminos;
    } else {
        ocultarErrorGlobal();
    }

    if (primerError) {
        primerError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        primerError.focus?.();
    }

    return valido;
}

function marcarError(campo, mensaje) {
    campo.classList.add('invalid');
    const grupo = campo.closest('.form-group');
    if (grupo && mensaje) {
        grupo.classList.add('has-error');
        let msg = grupo.querySelector('.field-msg');
        if (!msg) {
            msg = document.createElement('span');
            msg.className = 'field-msg';
            grupo.appendChild(msg);
        }
        msg.textContent = mensaje;
    }
    // Sacudida (definida en animations.css)
    campo.classList.remove('error');
    void campo.offsetWidth;
    campo.classList.add('error');
    setTimeout(() => campo.classList.remove('error'), 600);
}

function limpiarError(campo) {
    campo.classList.remove('invalid');
    campo.closest('.form-group')?.classList.remove('has-error');
}

function mostrarErrorGlobal(texto) {
    const box = $('#form-error');
    box.textContent = texto;
    box.hidden = false;
}

function ocultarErrorGlobal() {
    const box = $('#form-error');
    box.hidden = true;
    box.textContent = '';
}

// ========== ENVÍO DEL PEDIDO ==========
async function enviarPedido(e) {
    e.preventDefault();

    if (!carrito.length) {
        mostrarNotificacion('Tu carrito está vacío');
        return;
    }
    if (!validarFormulario()) return;

    const form  = e.target;
    const boton = $('#btn-realizar-pedido');
    const total = carrito.reduce((s, i) => s + i.precio * i.cantidad, 0);

    const payload = {
        usuario_id:     usuarioActual?.id || null,
        crear_cuenta:   !usuarioActual && $('#crear-cuenta').checked,
        password:       !usuarioActual && $('#crear-cuenta').checked ? $('#password').value : null,

        nombre_completo: `${$('#nombres').value.trim()} ${$('#apellidos').value.trim()}`,
        tipo_documento:  $('#tipo-documento').value,
        documento:       $('#documento').value.trim(),
        email:           $('#email').value.trim().toLowerCase(),
        telefono:        $('#telefono').value.trim(),
        departamento:    $('#departamento').value,
        ciudad:          $('#ciudad').value.trim(),
        direccion:       $('#direccion').value.trim(),
        punto_entrega:   $('#punto-entrega').value,
        notas:           $('#notas').value.trim(),
        metodo_pago:     form.querySelector('input[name="metodo_pago"]:checked')?.value || 'pago_en_cis',

        total: total,
        items: carrito.map(i => ({
            producto_id:     i.id,
            cantidad:        i.cantidad,
            precio_unitario: i.precio,
            subtotal:        i.precio * i.cantidad
        }))
    };

    boton.classList.add('loading');
    boton.disabled = true;
    ocultarErrorGlobal();

    try {
        const res  = await fetch(`${API_URL}?accion=crearPedido`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const text = await res.text();
        let data;
        try { data = JSON.parse(text); }
        catch { throw new Error('Respuesta inválida del servidor: ' + text.slice(0, 200)); }

        if (!data.success) {
            mostrarErrorGlobal(data.message || data.error || 'No se pudo registrar el pedido.');
            $('#form-error').scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        mostrarConfirmacion(data.data, payload);
    } catch (err) {
        console.error('Error al crear pedido:', err);
        mostrarErrorGlobal('No se pudo conectar con el servidor. Intenta de nuevo.');
    } finally {
        boton.classList.remove('loading');
        boton.disabled = false;
    }
}

function mostrarConfirmacion(resultado, payload) {
    // Vaciar carrito
    carrito = [];
    guardarCarrito();
    const contador = $('#contador-carrito');
    if (contador) contador.textContent = '0';

    // Pasos
    $$('.checkout-steps li').forEach(li => { li.classList.remove('current'); li.classList.add('done'); });
    const ultimo = $('.checkout-steps li:last-child');
    ultimo.classList.remove('done');
    ultimo.classList.add('current');

    // Datos
    $('#success-numero').textContent = resultado.numero_pedido || `#${resultado.pedido_id}`;
    $('#success-email').textContent  = payload.email;
    $('#success-punto').textContent  = payload.punto_entrega;

    $('#checkout-wrapper').hidden = true;
    $('#checkout-success').hidden = false;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ========== UTILIDADES ==========
function escapar(texto) {
    return String(texto ?? '')
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function mostrarNotificacion(mensaje, duracion = 3000) {
    $$('.notificacion').forEach(n => n.remove());
    const notif = document.createElement('div');
    notif.className = 'notificacion';
    notif.textContent = mensaje;
    document.body.appendChild(notif);
    setTimeout(() => {
        notif.classList.add('saliendo');
        notif.addEventListener('animationend', () => notif.remove(), { once: true });
    }, duracion);
}
