// js/app.js

const API_URL = 'api/api.php';
let usuarioActual = null;
let carrito = [];

// ========== INICIALIZACIÓN ==========
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado');
    
    setTimeout(() => {
        inicializarEventListeners();
        cargarCategorias();
        cargarProductos();
        cargarMarcas();
    }, 100);
});

function inicializarEventListeners() {
    const filtroCategoria = document.getElementById('filtro-categoria');
    const filtroMarca = document.getElementById('filtro-marca');
    const formLogin = document.getElementById('form-login');
    
    if (filtroCategoria) {
        filtroCategoria.addEventListener('change', cargarProductos);
    }
    
    if (filtroMarca) {
        filtroMarca.addEventListener('change', cargarProductos);
    }
    
    if (formLogin) {
        formLogin.addEventListener('submit', iniciarSesion);
    }
}

// ========== CATEGORÍAS ==========
function cargarCategorias() {
    console.log('Cargando categorías...');
    fetch(`${API_URL}?accion=obtenerCategorias`)
        .then(response => response.text())
        .then(text => {
            console.log('Response text:', text);
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    mostrarCategorias(data.data);
                    llenarFiltroCategoria(data.data);
                }
            } catch (e) {
                console.error('Error parsing JSON:', e);
            }
        })
        .catch(error => console.error('Error en fetch:', error));
}

function mostrarCategorias(categorias) {
    const contenedor = document.getElementById('lista-categorias');
    if (!contenedor) {
        console.error('Contenedor lista-categorias no encontrado');
        return;
    }
    
    contenedor.innerHTML = '';
    
    const iconosCategorias = {
        'Tablets': '📱',
        'Portátiles': '💻',
        'Impresoras': '🖨️',
        'Bebidas': '🥤',
        'Lonchera': '🍱',
        'Cuadernos': '📓',
        'Colores': '🎨',
        'Lápices': '✏️',
        'Maletines': '🎒'
    };
    
    categorias.forEach(categoria => {
        const card = document.createElement('div');
        card.className = 'categoria-card';
        
        let imagenHtml = '';
        
        if (categoria.imagen) {
            imagenHtml = `
                <div class="categoria-imagen">
                    <img src="assets/img/categorias/${categoria.imagen}" 
                         alt="${categoria.nombre}" 
                         onerror="this.parentElement.innerHTML='<div class=&quot;categoria-imagen-emoji&quot;>${iconosCategorias[categoria.nombre] || '📦'}</div>'">
                    <div class="categoria-overlay"></div>
                </div>
            `;
        } else {
            imagenHtml = `
                <div class="categoria-imagen">
                    <div class="categoria-imagen-emoji">
                        ${iconosCategorias[categoria.nombre] || '📦'}
                    </div>
                    <div class="categoria-overlay"></div>
                </div>
            `;
        }
        
        card.innerHTML = imagenHtml + `
            <div class="categoria-info">
                <h3>${categoria.nombre}</h3>
                <p>${categoria.descripcion || ''}</p>
            </div>
        `;
        
        card.addEventListener('click', () => {
            const filtro = document.getElementById('filtro-categoria');
            if (filtro) {
                filtro.value = categoria.id;
                cargarProductos();
                const productosSection = document.getElementById('productos');
                if (productosSection) {
                    productosSection.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
        
        contenedor.appendChild(card);
    });
}

function llenarFiltroCategoria(categorias) {
    const filtro = document.getElementById('filtro-categoria');
    if (!filtro) {
        console.error('Filtro categoría no encontrado');
        return;
    }
    
    categorias.forEach(categoria => {
        const option = document.createElement('option');
        option.value = categoria.id;
        option.textContent = categoria.nombre;
        filtro.appendChild(option);
    });
}

// ========== MARCAS ==========
function cargarMarcas() {
    console.log('Cargando marcas...');
    fetch(`${API_URL}?accion=obtenerMarcas`)
        .then(response => response.text())
        .then(text => {
            console.log('Marcas response:', text);
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    llenarFiltroMarca(data.data);
                }
            } catch (e) {
                console.error('Error parsing marcas JSON:', e);
            }
        })
        .catch(error => console.error('Error cargando marcas:', error));
}

function llenarFiltroMarca(marcas) {
    const filtro = document.getElementById('filtro-marca');
    if (!filtro) {
        console.error('Filtro marca no encontrado');
        return;
    }
    
    marcas.forEach(marca => {
        const option = document.createElement('option');
        option.value = marca.id;
        option.textContent = marca.nombre;
        filtro.appendChild(option);
    });
}

// ========== PRODUCTOS ==========
function cargarProductos() {
    const filtroCategoria = document.getElementById('filtro-categoria');
    const filtroMarca = document.getElementById('filtro-marca');
    
    if (!filtroCategoria || !filtroMarca) {
        console.error('Filtros no encontrados');
        return;
    }
    
    const categoria_id = filtroCategoria.value;
    const marca_id = filtroMarca.value;
    
    let url = `${API_URL}?accion=obtenerProductos`;
    if (categoria_id) url += `&categoria_id=${categoria_id}`;
    if (marca_id) url += `&marca_id=${marca_id}`;
    
    console.log('Cargando productos desde:', url);
    
    fetch(url)
        .then(response => response.text())
        .then(text => {
            console.log('Productos response:', text);
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    mostrarProductos(data.data);
                }
            } catch (e) {
                console.error('Error parsing productos JSON:', e);
            }
        })
        .catch(error => console.error('Error cargando productos:', error));
}

function mostrarProductos(productos) {
    const contenedor = document.getElementById('lista-productos');
    if (!contenedor) {
        console.error('Contenedor lista-productos no encontrado');
        return;
    }
    
    contenedor.innerHTML = '';
    
    if (productos.length === 0) {
        contenedor.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: #666;">No hay productos disponibles</p>';
        return;
    }
    
    productos.forEach(producto => {
        const precioFinal = producto.precio - (producto.precio * producto.descuento / 100);
        const card = document.createElement('div');
        card.className = 'producto-card';
        
        // Obtener la imagen principal o la primera imagen disponible
        let imagenHtml = '📦';
        if (producto.imagenes) {
            const imagenes = producto.imagenes.split(',');
            if (imagenes.length > 0 && imagenes[0]) {
                imagenHtml = `<img src="assets/img/productos/${imagenes[0]}" alt="${producto.nombre}" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 200 200%22%3E%3Crect fill=%22%23f0f0f0%22 width=%22200%22 height=%22200%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%23999%22 font-family=%22Arial%22 font-size=%2214%22%3E📦%3C/text%3E%3C/svg%3E'">`;
            }
        }
        
        card.innerHTML = `
            <div class="producto-imagen">
                ${imagenHtml}
            </div>
            <div class="producto-info">
                <h3>${producto.nombre}</h3>
                <p class="producto-marca">${producto.marca || 'Sin marca'}</p>
                <div class="producto-precio">$${precioFinal.toLocaleString('es-CO')}</div>
                ${producto.descuento > 0 ? `<div class="producto-descuento">-${producto.descuento}%</div>` : ''}
                <p class="producto-stock">Stock: ${producto.stock}</p>
                <div class="producto-acciones">
                    <button class="btn-agregar" onclick="agregarAlCarrito(${producto.id}, '${producto.nombre.replace(/'/g, "\\'")}', ${precioFinal})">
                        Agregar al Carrito
                    </button>
                </div>
            </div>
        `;
        contenedor.appendChild(card);
    });
}

// ========== CARRITO ==========
function agregarAlCarrito(producto_id, nombre, precio) {
    const item = {
        id: producto_id,
        nombre: nombre,
        precio: precio,
        cantidad: 1
    };
    
    const itemExistente = carrito.find(p => p.id === producto_id);
    if (itemExistente) {
        itemExistente.cantidad++;
    } else {
        carrito.push(item);
    }
    
    actualizarContadorCarrito();
    mostrarNotificacion(`${nombre} agregado al carrito`);
}

function actualizarContadorCarrito() {
    const contador = document.getElementById('contador-carrito');
    if (contador) {
        const total = carrito.reduce((sum, item) => sum + item.cantidad, 0);
        contador.textContent = total;
    }
}

function abrirCarrito() {
    const modal = document.getElementById('carrito-modal');
    const contenido = document.getElementById('contenido-carrito');
    
    if (!modal || !contenido) {
        console.error('Modal carrito no encontrado');
        return;
    }
    
    if (carrito.length === 0) {
        contenido.innerHTML = '<p style="text-align: center; color: #666;">Tu carrito está vacío</p>';
    } else {
        let html = '<table style="width: 100%; border-collapse: collapse;">';
        html += '<tr style="border-bottom: 2px solid #00d4ff;"><th style="text-align: left; padding: 10px;">Producto</th><th style="text-align: right; padding: 10px;">Precio</th><th style="text-align: center; padding: 10px;">Cantidad</th><th style="text-align: right; padding: 10px;">Subtotal</th><th style="text-align: center; padding: 10px;">Acción</th></tr>';
        
        carrito.forEach((item, index) => {
            const subtotal = item.precio * item.cantidad;
            html += `
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 10px;">${item.nombre}</td>
                    <td style="text-align: right; padding: 10px;">$${item.precio.toLocaleString('es-CO')}</td>
                    <td style="text-align: center; padding: 10px;">
                        <input type="number" value="${item.cantidad}" min="1" 
                               onchange="actualizarCantidad(${index}, this.value)" 
                               style="width: 50px; padding: 5px; border: 1px solid #ddd; border-radius: 3px;">
                    </td>
                    <td style="text-align: right; padding: 10px;">$${subtotal.toLocaleString('es-CO')}</td>
                    <td style="text-align: center; padding: 10px;"><button onclick="eliminarDelCarrito(${index})" style="background: #ff6b6b; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">Eliminar</button></td>
                </tr>
            `;
        });
        html += '</table>';
        contenido.innerHTML = html;
    }
    
    const total = carrito.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
    const totalSpan = document.getElementById('total-carrito');
    if (totalSpan) {
        totalSpan.textContent = total.toLocaleString('es-CO');
    }
    
    modal.style.display = 'block';
}

function actualizarCantidad(index, cantidad) {
    carrito[index].cantidad = parseInt(cantidad) || 1;
    actualizarContadorCarrito();
    abrirCarrito();
}

function eliminarDelCarrito(index) {
    carrito.splice(index, 1);
    actualizarContadorCarrito();
    abrirCarrito();
}

function cerrarCarrito() {
    const modal = document.getElementById('carrito-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function procederCompra() {
    if (usuarioActual) {
        alert('Proceder a compra - Integración de pago pendiente');
        carrito = [];
        actualizarContadorCarrito();
        cerrarCarrito();
    } else {
        alert('Debes iniciar sesión para comprar');
        abrirLogin();
    }
}

// ========== AUTENTICACIÓN ==========
function abrirLogin() {
    const modal = document.getElementById('login-modal');
    if (modal) {
        modal.style.display = 'block';
    }
}

function cerrarLogin() {
    const modal = document.getElementById('login-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function abrirRegistro() {
    alert('Función de registro pendiente');
}

function iniciarSesion(e) {
    e.preventDefault();
    
    const emailInput = document.getElementById('email');
    const contraseñaInput = document.getElementById('contraseña');
    
    if (!emailInput || !contraseñaInput) {
        console.error('Inputs de login no encontrados');
        return;
    }
    
    const email = emailInput.value;
    const contraseña = contraseñaInput.value;
    
    console.log('Intentando login con:', email);
    
    fetch(`${API_URL}?accion=verificarUsuario`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ email, contraseña })
    })
    .then(response => response.text())
    .then(text => {
        console.log('Login response:', text);
        try {
            const data = JSON.parse(text);
            if (data.success) {
                usuarioActual = data.data;
                mostrarNotificacion(`Bienvenido ${usuarioActual.nombre}`);
                cerrarLogin();
                document.getElementById('form-login').reset();
            } else {
                alert(data.message || 'Error al iniciar sesión');
            }
        } catch (e) {
            console.error('Error parsing login JSON:', e);
        }
    })
    .catch(error => console.error('Error en login:', error));
}

// ========== UTILIDADES ==========
function mostrarNotificacion(mensaje) {
    const notif = document.createElement('div');
    notif.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: #00d4ff;
        color: white;
        padding: 15px 20px;
        border-radius: 5px;
        z-index: 2000;
        box-shadow: 0 4px 15px rgba(0, 212, 255, 0.4);
        animation: slideIn 0.3s ease-out;
        font-family: 'Poppins', sans-serif;
    `;
    notif.textContent = mensaje;
    document.body.appendChild(notif);
    
    setTimeout(() => notif.remove(), 3000);
}

// Cerrar modales al hacer click fuera
window.onclick = function(event) {
    const carritoModal = document.getElementById('carrito-modal');
    const loginModal = document.getElementById('login-modal');
    
    if (event.target === carritoModal) {
        carritoModal.style.display = 'none';
    }
    if (event.target === loginModal) {
        loginModal.style.display = 'none';
    }
}
