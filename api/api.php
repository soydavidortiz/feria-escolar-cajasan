<?php
// api/api.php

// ============================================================
//  HEADERS
// ============================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ============================================================
//  ERRORES
//  display_errors en 0 para que los warnings de PHP nunca
//  "ensucien" el JSON; todo queda en api/error.log
// ============================================================
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');
error_reporting(E_ALL);

define('API_DEBUG', true); // Cambiar a false en producción

// ============================================================
//  HELPERS
// ============================================================

/** Responde JSON y termina. */
function responder(array $payload, int $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function ok($data = null, string $message = null, array $extra = []) {
    $res = ['success' => true];
    if ($data !== null)    $res['data']    = $data;
    if ($message !== null) $res['message'] = $message;
    responder(array_merge($res, $extra));
}

function fallo(string $message, int $codigo = 400, array $extra = []) {
    responder(array_merge(['success' => false, 'message' => $message, 'error' => $message], $extra), $codigo);
}

/** Lee el body JSON. Lanza excepción si no es válido. */
function leerJson(bool $obligatorio = true): array {
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        if ($obligatorio) throw new InvalidArgumentException('Cuerpo de la petición inválido (se esperaba JSON).');
        return [];
    }
    return $data;
}

/** Exige que la petición sea de cierto método (o varios). */
function requiereMetodo(string ...$permitidos) {
    if (!in_array($_SERVER['REQUEST_METHOD'], $permitidos, true)) {
        fallo('Método no permitido. Usa: ' . implode(', ', $permitidos), 405);
    }
}

/** Exige claves en el array de datos. */
function requiereCampos(array $data, array $campos) {
    foreach ($campos as $c) {
        if (!isset($data[$c]) || $data[$c] === '') {
            throw new InvalidArgumentException("El campo '$c' es obligatorio.");
        }
    }
}

// ============================================================
//  BOOTSTRAP
// ============================================================
try {
    $config_path = __DIR__ . '/../config/database.php';
    $model_path  = __DIR__ . '/../models/Model.php';

    if (!file_exists($config_path)) throw new RuntimeException("Archivo de configuración no encontrado: $config_path");
    if (!file_exists($model_path))  throw new RuntimeException("Archivo de modelo no encontrado: $model_path");

    require_once $config_path;
    require_once $model_path;

    if (!isset($conexion)) throw new RuntimeException('Conexión a base de datos no establecida');

    $model  = new Model();
    $metodo = $_SERVER['REQUEST_METHOD'];
    $accion = $_GET['accion'] ?? '';

    if ($accion === '') fallo('Acción no especificada', 400);

    switch ($accion) {

        // ========================================================
        //  CATEGORÍAS
        // ========================================================
        case 'obtenerCategorias':
            ok($model->obtenerCategorias() ?: []);
            break;

        case 'obtenerCategoriaPorId':
            $id = intval($_GET['id'] ?? 0);
            if ($id <= 0) fallo('ID de categoría inválido');
            ok($model->obtenerCategoriaPorId($id));
            break;

        case 'crearCategoria':
            requiereMetodo('POST');
            $data = leerJson();
            requiereCampos($data, ['nombre']);
            $r = $model->crearCategoria($data['nombre'], $data['descripcion'] ?? '', $data['imagen'] ?? '');
            $r ? ok(null, 'Categoría creada') : fallo('Error al crear la categoría', 500);
            break;

        case 'actualizarCategoria':
            requiereMetodo('PUT', 'POST');
            $data = leerJson();
            requiereCampos($data, ['id', 'nombre']);
            $r = $model->actualizarCategoria($data['id'], $data['nombre'], $data['descripcion'] ?? '', $data['imagen'] ?? '');
            $r ? ok(null, 'Categoría actualizada') : fallo('Error al actualizar la categoría', 500);
            break;

        case 'eliminarCategoria':
            requiereMetodo('DELETE', 'POST');
            $data = leerJson();
            requiereCampos($data, ['id']);
            $r = $model->eliminarCategoria($data['id']);
            $r ? ok(null, 'Categoría eliminada') : fallo('Error al eliminar la categoría', 500);
            break;

        // ========================================================
        //  MARCAS
        // ========================================================
        case 'obtenerMarcas':
            ok($model->obtenerMarcas() ?: []);
            break;

        case 'crearMarca':
            requiereMetodo('POST');
            $data = leerJson();
            requiereCampos($data, ['nombre']);
            $r = $model->crearMarca($data['nombre'], $data['descripcion'] ?? '', $data['logo'] ?? '');
            $r ? ok(null, 'Marca creada') : fallo('Error al crear la marca', 500);
            break;

        // ========================================================
        //  PRODUCTOS
        // ========================================================
        case 'obtenerProductos':
            $categoria_id = !empty($_GET['categoria_id']) ? intval($_GET['categoria_id']) : null;
            $marca_id     = !empty($_GET['marca_id'])     ? intval($_GET['marca_id'])     : null;
            ok($model->obtenerProductos($categoria_id, $marca_id) ?: []);
            break;

        case 'obtenerProductoPorId':
            $id = intval($_GET['id'] ?? 0);
            if ($id <= 0) fallo('ID de producto inválido');
            $producto = $model->obtenerProductoPorId($id);
            if (!$producto) fallo('Producto no encontrado', 404);
            ok($producto, null, ['imagenes' => $model->obtenerImagenesProducto($id) ?: []]);
            break;

        case 'crearProducto':
            requiereMetodo('POST');
            $data = leerJson();
            requiereCampos($data, ['nombre']);
            $id = $model->crearProducto(
                $data['nombre'],
                $data['descripcion'] ?? '',
                (float) ($data['precio'] ?? 0),
                (float) ($data['descuento'] ?? 0),
                (int)   ($data['stock'] ?? 0),
                (int)   ($data['categoria_id'] ?? 0),
                (int)   ($data['marca_id'] ?? 0)
            );
            $id ? ok(null, 'Producto creado', ['id' => $id]) : fallo('Error al crear el producto', 500);
            break;

        case 'actualizarProducto':
            requiereMetodo('PUT', 'POST');
            $data = leerJson();
            requiereCampos($data, ['id', 'nombre']);
            $r = $model->actualizarProducto(
                (int)   $data['id'],
                $data['nombre'],
                $data['descripcion'] ?? '',
                (float) ($data['precio'] ?? 0),
                (float) ($data['descuento'] ?? 0),
                (int)   ($data['stock'] ?? 0),
                (int)   ($data['categoria_id'] ?? 0),
                (int)   ($data['marca_id'] ?? 0)
            );
            $r ? ok(null, 'Producto actualizado') : fallo('Error al actualizar el producto', 500);
            break;

        case 'eliminarProducto':
            requiereMetodo('DELETE', 'POST');
            $data = leerJson();
            requiereCampos($data, ['id']);
            $r = $model->eliminarProducto($data['id']);
            $r ? ok(null, 'Producto eliminado') : fallo('Error al eliminar el producto', 500);
            break;

        // ========================================================
        //  IMÁGENES
        // ========================================================
        case 'obtenerImagenesProducto':
            $producto_id = intval($_GET['producto_id'] ?? 0);
            if ($producto_id <= 0) fallo('ID de producto inválido');
            ok($model->obtenerImagenesProducto($producto_id) ?: []);
            break;

        case 'agregarImagenProducto':
            requiereMetodo('POST');
            $data = leerJson();
            requiereCampos($data, ['producto_id', 'url_imagen']);
            $r = $model->agregarImagenProducto($data['producto_id'], $data['url_imagen'], !empty($data['es_principal']));
            $r ? ok(null, 'Imagen agregada') : fallo('Error al agregar la imagen', 500);
            break;

        // ========================================================
        //  USUARIOS
        // ========================================================
        case 'obtenerUsuarios':
            ok($model->obtenerUsuarios() ?: []);
            break;

        case 'verificarUsuario':
            requiereMetodo('POST');
            $data = leerJson();
            // Acepta "contraseña" (como envía app.js/checkout.js) o "password"
            $pass = $data['contraseña'] ?? $data['password'] ?? null;
            if (empty($data['email']) || $pass === null || $pass === '') {
                fallo('Email o contraseña no especificados');
            }
            $usuario = $model->verificarUsuario(strtolower(trim($data['email'])), $pass);
            $usuario ? ok($usuario) : fallo('Credenciales inválidas', 401);
            break;

        case 'crearUsuario':
            requiereMetodo('POST');
            $data = leerJson();
            requiereCampos($data, ['nombre', 'email']);
            $email = strtolower(trim($data['email']));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fallo('Correo electrónico inválido');
            if ($model->obtenerUsuarioPorEmail($email)) fallo('Ya existe un usuario con ese correo', 409);

            $id = $model->crearUsuario(
                trim($data['nombre']),
                $email,
                $data['contraseña'] ?? $data['password'] ?? null,
                $data['rol'] ?? 'cliente'
            );
            $id ? ok(['id' => $id], 'Usuario creado') : fallo('Error al crear el usuario', 500);
            break;

        // ========================================================
        //  CARRITO (persistente en BD, opcional)
        // ========================================================
        case 'agregarAlCarrito':
            requiereMetodo('POST');
            $data = leerJson();
            requiereCampos($data, ['usuario_id', 'producto_id']);
            $r = $model->agregarAlCarrito((int) $data['usuario_id'], (int) $data['producto_id'], (int) ($data['cantidad'] ?? 1));
            $r ? ok(null, 'Agregado al carrito') : fallo('Error al agregar al carrito', 500);
            break;

        case 'obtenerCarrito':
            $usuario_id = intval($_GET['usuario_id'] ?? 0);
            if ($usuario_id <= 0) fallo('ID de usuario inválido');
            ok($model->obtenerCarrito($usuario_id) ?: []);
            break;

        case 'eliminarDelCarrito':
            requiereMetodo('DELETE', 'POST');
            $data = leerJson();
            requiereCampos($data, ['usuario_id', 'producto_id']);
            $r = $model->eliminarDelCarrito((int) $data['usuario_id'], (int) $data['producto_id']);
            $r ? ok(null, 'Eliminado del carrito') : fallo('Error al eliminar del carrito', 500);
            break;

        case 'vaciarCarrito':
            requiereMetodo('DELETE', 'POST');
            $data = leerJson();
            requiereCampos($data, ['usuario_id']);
            $r = $model->vaciarCarrito((int) $data['usuario_id']);
            $r ? ok(null, 'Carrito vaciado') : fallo('Error al vaciar el carrito', 500);
            break;

        // ========================================================
        //  PEDIDOS (checkout)
        // ========================================================

        /**
         * POST api.php?accion=crearPedido
         * Body: JSON que envía checkout.js
         * Respuesta: { success, data: { pedido_id, numero_pedido, usuario_id, total } }
         */
        case 'crearPedido':
            requiereMetodo('POST');
            $data = leerJson();
            requiereCampos($data, ['nombre_completo', 'documento', 'telefono', 'email', 'punto_entrega', 'items']);

            if (!is_array($data['items']) || count($data['items']) === 0) {
                fallo('El pedido no tiene productos.');
            }

            // crearPedido valida stock, recalcula precios y hace la transacción.
            // Si algo falla lanza Exception con mensaje legible → se captura abajo como 400.
            $resultado = $model->crearPedido($data);
            ok($resultado, 'Pedido registrado correctamente', ['codigo' => 201]);
            break;

        /** GET api.php?accion=obtenerPedido&id=12 */
        case 'obtenerPedido':
            $id = intval($_GET['id'] ?? 0);
            if ($id <= 0) fallo('ID de pedido inválido');
            $pedido = $model->obtenerPedido($id);
            $pedido ? ok($pedido) : fallo('Pedido no encontrado', 404);
            break;

        /** GET api.php?accion=obtenerPedidosPorUsuario&usuario_id=3 */
        case 'obtenerPedidosPorUsuario':
            $usuario_id = intval($_GET['usuario_id'] ?? 0);
            if ($usuario_id <= 0) fallo('ID de usuario inválido');
            ok($model->obtenerPedidosPorUsuario($usuario_id) ?: []);
            break;

        /** GET api.php?accion=obtenerPedidos[&estado=pendiente]  (admin) */
        case 'obtenerPedidos':
            $estado = !empty($_GET['estado']) ? $_GET['estado'] : null;
            ok($model->obtenerPedidos($estado) ?: []);
            break;

        /** PUT/POST api.php?accion=actualizarEstadoPedido  Body: { id, estado }  (admin) */
        case 'actualizarEstadoPedido':
            requiereMetodo('PUT', 'POST');
            $data = leerJson();
            requiereCampos($data, ['id', 'estado']);
            $r = $model->actualizarEstadoPedido((int) $data['id'], $data['estado']);
            $r ? ok(null, 'Estado del pedido actualizado') : fallo('Error al actualizar el estado', 500);
            break;

        // ========================================================
        default:
            fallo('Acción no reconocida: ' . $accion, 404);
    }

} catch (InvalidArgumentException $e) {
    // Datos del cliente incorrectos → 400
    fallo($e->getMessage(), 400);

} catch (mysqli_sql_exception $e) {
    // Error de base de datos → 500 (detalle solo en modo debug)
    error_log("Error SQL en API [$accion]: " . $e->getMessage());
    fallo(
        'Error en la base de datos.',
        500,
        API_DEBUG ? ['detalle' => $e->getMessage(), 'line' => $e->getLine()] : []
    );

} catch (Throwable $e) {
    // Excepciones del modelo (p. ej. "Stock insuficiente…") → 400 con el mensaje tal cual
    error_log("Error en API [$accion]: " . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    fallo(
        $e->getMessage(),
        400,
        API_DEBUG ? ['file' => $e->getFile(), 'line' => $e->getLine()] : []
    );
}
