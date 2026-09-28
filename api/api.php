<?php
// api/api.php

// Configurar headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Mostrar errores en desarrollo
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Log de errores
ini_set('error_log', __DIR__ . '/error.log');

try {
    // Incluir archivos
    $config_path = __DIR__ . '/../config/database.php';
    $model_path = __DIR__ . '/../models/Model.php';
    
    if (!file_exists($config_path)) {
        throw new Exception("Archivo de configuración no encontrado: $config_path");
    }
    
    if (!file_exists($model_path)) {
        throw new Exception("Archivo de modelo no encontrado: $model_path");
    }
    
    require_once $config_path;
    require_once $model_path;
    
    // Verificar conexión
    if (!isset($conexion)) {
        throw new Exception("Conexión a base de datos no establecida");
    }
    
    $model = new Model();
    $metodo = $_SERVER['REQUEST_METHOD'];
    $accion = isset($_GET['accion']) ? $_GET['accion'] : '';
    
    if (empty($accion)) {
        throw new Exception("Acción no especificada");
    }
    
    switch ($accion) {
        
        // ========== CATEGORÍAS ==========
        case 'obtenerCategorias':
            $categorias = $model->obtenerCategorias();
            echo json_encode(['success' => true, 'data' => $categorias ?: []]);
            break;
        
        case 'crearCategoria':
            if ($metodo === 'POST') {
                $data = json_decode(file_get_contents("php://input"), true);
                if (!$data || !isset($data['nombre'])) {
                    throw new Exception("Datos inválidos");
                }
                $resultado = $model->crearCategoria($data['nombre'], $data['descripcion'] ?? '', $data['imagen'] ?? '');
                echo json_encode(['success' => $resultado, 'message' => $resultado ? 'Categoría creada' : 'Error al crear']);
            }
            break;
        
        case 'actualizarCategoria':
            if ($metodo === 'PUT') {
                $data = json_decode(file_get_contents("php://input"), true);
                if (!$data || !isset($data['id'])) {
                    throw new Exception("Datos inválidos");
                }
                $resultado = $model->actualizarCategoria($data['id'], $data['nombre'], $data['descripcion'], $data['imagen']);
                echo json_encode(['success' => $resultado, 'message' => $resultado ? 'Categoría actualizada' : 'Error al actualizar']);
            }
            break;
        
        case 'eliminarCategoria':
            if ($metodo === 'DELETE') {
                $data = json_decode(file_get_contents("php://input"), true);
                if (!$data || !isset($data['id'])) {
                    throw new Exception("ID no especificado");
                }
                $resultado = $model->eliminarCategoria($data['id']);
                echo json_encode(['success' => $resultado, 'message' => $resultado ? 'Categoría eliminada' : 'Error al eliminar']);
            }
            break;
        
        // ========== MARCAS ==========
        case 'obtenerMarcas':
            $marcas = $model->obtenerMarcas();
            echo json_encode(['success' => true, 'data' => $marcas ?: []]);
            break;
        
        case 'crearMarca':
            if ($metodo === 'POST') {
                $data = json_decode(file_get_contents("php://input"), true);
                if (!$data || !isset($data['nombre'])) {
                    throw new Exception("Datos inválidos");
                }
                $resultado = $model->crearMarca($data['nombre'], $data['descripcion'] ?? '', $data['logo'] ?? '');
                echo json_encode(['success' => $resultado, 'message' => $resultado ? 'Marca creada' : 'Error al crear']);
            }
            break;
        
        // ========== PRODUCTOS ==========
        case 'obtenerProductos':
            $categoria_id = isset($_GET['categoria_id']) ? intval($_GET['categoria_id']) : null;
            $marca_id = isset($_GET['marca_id']) ? intval($_GET['marca_id']) : null;
            $productos = $model->obtenerProductos($categoria_id, $marca_id);
            echo json_encode(['success' => true, 'data' => $productos ?: []]);
            break;
        
        case 'obtenerProductoPorId':
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            if ($id <= 0) {
                throw new Exception("ID de producto inválido");
            }
            $producto = $model->obtenerProductoPorId($id);
            $imagenes = $model->obtenerImagenesProducto($id);
            echo json_encode(['success' => true, 'data' => $producto, 'imagenes' => $imagenes ?: []]);
            break;
        
        case 'crearProducto':
            if ($metodo === 'POST') {
                $data = json_decode(file_get_contents("php://input"), true);
                if (!$data || !isset($data['nombre'])) {
                    throw new Exception("Datos inválidos");
                }
                $id = $model->crearProducto(
                    $data['nombre'],
                    $data['descripcion'] ?? '',
                    $data['precio'] ?? 0,
                    $data['descuento'] ?? 0,
                    $data['stock'] ?? 0,
                    $data['categoria_id'] ?? 0,
                    $data['marca_id'] ?? 0
                );
                echo json_encode(['success' => $id ? true : false, 'id' => $id, 'message' => $id ? 'Producto creado' : 'Error al crear']);
            }
            break;
        
        case 'actualizarProducto':
            if ($metodo === 'PUT') {
                $data = json_decode(file_get_contents("php://input"), true);
                if (!$data || !isset($data['id'])) {
                    throw new Exception("Datos inválidos");
                }
                $resultado = $model->actualizarProducto(
                    $data['id'],
                    $data['nombre'],
                    $data['descripcion'],
                    $data['precio'],
                    $data['descuento'],
                    $data['stock'],
                    $data['categoria_id'],
                    $data['marca_id']
                );
                echo json_encode(['success' => $resultado, 'message' => $resultado ? 'Producto actualizado' : 'Error al actualizar']);
            }
            break;
        
        case 'eliminarProducto':
            if ($metodo === 'DELETE') {
                $data = json_decode(file_get_contents("php://input"), true);
                if (!$data || !isset($data['id'])) {
                    throw new Exception("ID no especificado");
                }
                $resultado = $model->eliminarProducto($data['id']);
                echo json_encode(['success' => $resultado, 'message' => $resultado ? 'Producto eliminado' : 'Error al eliminar']);
            }
            break;
        
        // ========== IMÁGENES ==========
        case 'obtenerImagenesProducto':
            $producto_id = isset($_GET['producto_id']) ? intval($_GET['producto_id']) : 0;
            if ($producto_id <= 0) {
                throw new Exception("ID de producto inválido");
            }
            $imagenes = $model->obtenerImagenesProducto($producto_id);
            echo json_encode(['success' => true, 'data' => $imagenes ?: []]);
            break;
        
        case 'agregarImagenProducto':
            if ($metodo === 'POST') {
                $data = json_decode(file_get_contents("php://input"), true);
                if (!$data || !isset($data['producto_id'])) {
                    throw new Exception("Datos inválidos");
                }
                $resultado = $model->agregarImagenProducto(
                    $data['producto_id'],
                    $data['url_imagen'] ?? '',
                    $data['es_principal'] ?? false
                );
                echo json_encode(['success' => $resultado, 'message' => $resultado ? 'Imagen agregada' : 'Error al agregar']);
            }
            break;
        
        // ========== USUARIOS ==========
        case 'obtenerUsuarios':
            $usuarios = $model->obtenerUsuarios();
            echo json_encode(['success' => true, 'data' => $usuarios ?: []]);
            break;
        
        case 'verificarUsuario':
            if ($metodo === 'POST') {
                $data = json_decode(file_get_contents("php://input"), true);
                if (!$data || !isset($data['email']) || !isset($data['contraseña'])) {
                    throw new Exception("Email o contraseña no especificados");
                }
                $usuario = $model->verificarUsuario($data['email'], $data['contraseña']);
                if ($usuario) {
                    echo json_encode(['success' => true, 'data' => $usuario]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Credenciales inválidas']);
                }
            }
            break;
        
        case 'crearUsuario':
            if ($metodo === 'POST') {
                $data = json_decode(file_get_contents("php://input"), true);
                if (!$data || !isset($data['nombre']) || !isset($data['email'])) {
                    throw new Exception("Datos inválidos");
                }
                $resultado = $model->crearUsuario(
                    $data['nombre'],
                    $data['email'],
                    $data['contraseña'] ?? '',
                    $data['rol'] ?? 'cliente'
                );
                echo json_encode(['success' => $resultado, 'message' => $resultado ? 'Usuario creado' : 'Error al crear']);
            }
            break;
        
        // ========== CARRITO ==========
        case 'agregarAlCarrito':
            if ($metodo === 'POST') {
                $data = json_decode(file_get_contents("php://input"), true);
                if (!$data || !isset($data['usuario_id']) || !isset($data['producto_id'])) {
                    throw new Exception("Datos inválidos");
                }
                $resultado = $model->agregarAlCarrito(
                    $data['usuario_id'],
                    $data['producto_id'],
                    $data['cantidad'] ?? 1
                );
                echo json_encode(['success' => $resultado, 'message' => $resultado ? 'Agregado al carrito' : 'Error']);
            }
            break;
        
        case 'obtenerCarrito':
            $usuario_id = isset($_GET['usuario_id']) ? intval($_GET['usuario_id']) : 0;
            if ($usuario_id <= 0) {
                throw new Exception("ID de usuario inválido");
            }
            $carrito = $model->obtenerCarrito($usuario_id);
            echo json_encode(['success' => true, 'data' => $carrito ?: []]);
            break;
        
        case 'eliminarDelCarrito':
            if ($metodo === 'DELETE') {
                $data = json_decode(file_get_contents("php://input"), true);
                if (!$data || !isset($data['usuario_id']) || !isset($data['producto_id'])) {
                    throw new Exception("Datos inválidos");
                }
                $resultado = $model->eliminarDelCarrito($data['usuario_id'], $data['producto_id']);
                echo json_encode(['success' => $resultado, 'message' => $resultado ? 'Eliminado del carrito' : 'Error']);
            }
            break;
        
        default:
            throw new Exception("Acción no reconocida: " . $accion);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    // Log del error
    error_log("Error en API: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
}
?>
