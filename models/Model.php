<?php
// models/Model.php

require_once __DIR__ . '/../config/database.php';

class Model {
    /** @var mysqli */
    protected $conexion;

    public function __construct() {
        global $conexion;
        $this->conexion = $conexion;

        // Que MySQLi lance excepciones (necesario para que las transacciones hagan rollback)
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $this->conexion->set_charset('utf8mb4');
    }

    // ============================================================
    //  CATEGORÍAS
    // ============================================================
    public function obtenerCategorias() {
        $result = $this->conexion->query("SELECT * FROM categorias WHERE estado = 'activo' ORDER BY nombre ASC");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function obtenerCategoriaPorId($id) {
        $stmt = $this->conexion->prepare("SELECT * FROM categorias WHERE id = ? AND estado = 'activo'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function crearCategoria($nombre, $descripcion, $imagen) {
        $stmt = $this->conexion->prepare("INSERT INTO categorias (nombre, descripcion, imagen) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nombre, $descripcion, $imagen);
        return $stmt->execute();
    }

    public function actualizarCategoria($id, $nombre, $descripcion, $imagen) {
        $stmt = $this->conexion->prepare("UPDATE categorias SET nombre = ?, descripcion = ?, imagen = ? WHERE id = ?");
        $stmt->bind_param("sssi", $nombre, $descripcion, $imagen, $id);
        return $stmt->execute();
    }

    public function eliminarCategoria($id) {
        $stmt = $this->conexion->prepare("UPDATE categorias SET estado = 'inactivo' WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // ============================================================
    //  MARCAS
    // ============================================================
    public function obtenerMarcas() {
        $result = $this->conexion->query("SELECT * FROM marcas WHERE estado = 'activo' ORDER BY nombre ASC");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function crearMarca($nombre, $descripcion, $logo) {
        $stmt = $this->conexion->prepare("INSERT INTO marcas (nombre, descripcion, logo) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nombre, $descripcion, $logo);
        return $stmt->execute();
    }

    // ============================================================
    //  PRODUCTOS
    // ============================================================
    public function obtenerProductos($categoria_id = null, $marca_id = null) {
        $query = "SELECT p.*, c.nombre AS categoria, m.nombre AS marca,
                         GROUP_CONCAT(ip.url_imagen ORDER BY ip.es_principal DESC SEPARATOR ',') AS imagenes
                  FROM productos p
                  LEFT JOIN categorias c ON p.categoria_id = c.id
                  LEFT JOIN marcas m ON p.marca_id = m.id
                  LEFT JOIN imagenes_productos ip ON p.id = ip.producto_id
                  WHERE p.estado = 'activo'";

        $tipos  = '';
        $params = [];

        if ($categoria_id) { $query .= " AND p.categoria_id = ?"; $tipos .= 'i'; $params[] = (int) $categoria_id; }
        if ($marca_id)     { $query .= " AND p.marca_id = ?";     $tipos .= 'i'; $params[] = (int) $marca_id; }

        $query .= " GROUP BY p.id ORDER BY p.nombre ASC";

        $stmt = $this->conexion->prepare($query);
        if ($params) $stmt->bind_param($tipos, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function obtenerProductoPorId($id) {
        $query = "SELECT p.*, c.nombre AS categoria, m.nombre AS marca,
                         GROUP_CONCAT(ip.url_imagen ORDER BY ip.es_principal DESC SEPARATOR ',') AS imagenes
                  FROM productos p
                  LEFT JOIN categorias c ON p.categoria_id = c.id
                  LEFT JOIN marcas m ON p.marca_id = m.id
                  LEFT JOIN imagenes_productos ip ON p.id = ip.producto_id
                  WHERE p.id = ? AND p.estado = 'activo'
                  GROUP BY p.id";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function crearProducto($nombre, $descripcion, $precio, $descuento, $stock, $categoria_id, $marca_id) {
        $stmt = $this->conexion->prepare(
            "INSERT INTO productos (nombre, descripcion, precio, descuento, stock, categoria_id, marca_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        // Corregido: 7 valores → 7 tipos (antes había 8)
        $stmt->bind_param("ssddiii", $nombre, $descripcion, $precio, $descuento, $stock, $categoria_id, $marca_id);
        return $stmt->execute() ? $this->conexion->insert_id : false;
    }

    public function actualizarProducto($id, $nombre, $descripcion, $precio, $descuento, $stock, $categoria_id, $marca_id) {
        $stmt = $this->conexion->prepare(
            "UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, descuento = ?,
                    stock = ?, categoria_id = ?, marca_id = ? WHERE id = ?"
        );
        $stmt->bind_param("ssddiiii", $nombre, $descripcion, $precio, $descuento, $stock, $categoria_id, $marca_id, $id);
        return $stmt->execute();
    }

    public function eliminarProducto($id) {
        $stmt = $this->conexion->prepare("UPDATE productos SET estado = 'inactivo' WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // ============================================================
    //  IMÁGENES DE PRODUCTOS
    // ============================================================
    public function obtenerImagenesProducto($producto_id) {
        $stmt = $this->conexion->prepare(
            "SELECT * FROM imagenes_productos WHERE producto_id = ? ORDER BY es_principal DESC, id ASC"
        );
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function agregarImagenProducto($producto_id, $url_imagen, $es_principal = false) {
        $principal = $es_principal ? 1 : 0;
        $stmt = $this->conexion->prepare(
            "INSERT INTO imagenes_productos (producto_id, url_imagen, es_principal) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("isi", $producto_id, $url_imagen, $principal);
        return $stmt->execute();
    }

    // ============================================================
    //  USUARIOS
    // ============================================================
    public function obtenerUsuarios() {
        $result = $this->conexion->query(
            "SELECT id, nombre, email, rol, estado, fecha_creacion FROM usuarios ORDER BY fecha_creacion DESC"
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /** Busca un usuario por correo (sin contraseña). Devuelve array o null. */
    public function obtenerUsuarioPorEmail($email) {
        $stmt = $this->conexion->prepare(
            "SELECT id, nombre, email, rol, estado FROM usuarios WHERE email = ? LIMIT 1"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    /**
     * Login compatible con contraseñas antiguas (MD5) y nuevas (password_hash).
     * Si el usuario aún tiene MD5, se migra automáticamente a password_hash al ingresar.
     */
    public function verificarUsuario($email, $contraseña) {
        $stmt = $this->conexion->prepare(
            "SELECT id, nombre, email, rol, contraseña FROM usuarios WHERE email = ? AND estado = 'activo' LIMIT 1"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc();
        if (!$usuario) return null;

        $hash = $usuario['contraseña'];
        $ok   = false;

        if (password_verify($contraseña, $hash)) {
            $ok = true;
        } elseif (strlen($hash) === 32 && hash_equals($hash, md5($contraseña))) {
            // Contraseña vieja en MD5 → la actualizamos al formato seguro
            $ok = true;
            $this->actualizarContraseña($usuario['id'], $contraseña);
        }

        if (!$ok) return null;

        unset($usuario['contraseña']);
        return $usuario;
    }

    /**
     * Crea un usuario y devuelve su id.
     * Si $contraseña es null/vacía, genera una aleatoria (cuenta de invitado).
     */
    public function crearUsuario($nombre, $email, $contraseña = null, $rol = 'cliente') {
        if ($contraseña === null || $contraseña === '') {
            $contraseña = bin2hex(random_bytes(8));
        }
        $hash = password_hash($contraseña, PASSWORD_DEFAULT);

        $stmt = $this->conexion->prepare(
            "INSERT INTO usuarios (nombre, email, contraseña, rol, estado) VALUES (?, ?, ?, ?, 'activo')"
        );
        $stmt->bind_param("ssss", $nombre, $email, $hash, $rol);
        $stmt->execute();
        return (int) $this->conexion->insert_id;
    }

    public function actualizarContraseña($usuario_id, $contraseña) {
        $hash = password_hash($contraseña, PASSWORD_DEFAULT);
        $stmt = $this->conexion->prepare("UPDATE usuarios SET contraseña = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $usuario_id);
        return $stmt->execute();
    }

    // ============================================================
    //  CARRITO (persistente en BD, opcional)
    // ============================================================
    public function agregarAlCarrito($usuario_id, $producto_id, $cantidad) {
        $stmt = $this->conexion->prepare(
            "INSERT INTO carritos (usuario_id, producto_id, cantidad) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE cantidad = cantidad + ?"
        );
        $stmt->bind_param("iiii", $usuario_id, $producto_id, $cantidad, $cantidad);
        return $stmt->execute();
    }

    public function obtenerCarrito($usuario_id) {
        $stmt = $this->conexion->prepare(
            "SELECT c.*, p.nombre, p.precio, p.descuento,
                    GROUP_CONCAT(ip.url_imagen ORDER BY ip.es_principal DESC SEPARATOR ',') AS imagenes
             FROM carritos c
             JOIN productos p ON c.producto_id = p.id
             LEFT JOIN imagenes_productos ip ON p.id = ip.producto_id
             WHERE c.usuario_id = ?
             GROUP BY c.id"
        );
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function eliminarDelCarrito($usuario_id, $producto_id) {
        $stmt = $this->conexion->prepare("DELETE FROM carritos WHERE usuario_id = ? AND producto_id = ?");
        $stmt->bind_param("ii", $usuario_id, $producto_id);
        return $stmt->execute();
    }

    public function vaciarCarrito($usuario_id) {
        $stmt = $this->conexion->prepare("DELETE FROM carritos WHERE usuario_id = ?");
        $stmt->bind_param("i", $usuario_id);
        return $stmt->execute();
    }

    // ============================================================
    //  PEDIDOS (checkout)
    // ============================================================

    /**
     * Crea el pedido completo dentro de una transacción:
     *   1. Resuelve / crea el usuario
     *   2. Valida stock y recalcula precios en el servidor
     *   3. Inserta en pedidos
     *   4. Inserta en detalles_pedidos y descuenta stock
     *   5. Genera numero_pedido (FE-2026-00012)
     *
     * @param  array $datos  JSON que envía checkout.js
     * @return array ['pedido_id', 'numero_pedido', 'usuario_id', 'total']
     * @throws Exception con mensaje legible para el usuario
     */
    public function crearPedido(array $datos) {

        // ---------- Validaciones básicas ----------
        if (empty($datos['items']) || !is_array($datos['items'])) {
            throw new Exception('El pedido no tiene productos.');
        }
        if (empty($datos['email']) || !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Correo electrónico inválido.');
        }
        if (empty($datos['nombre_completo']) || empty($datos['documento']) || empty($datos['telefono'])) {
            throw new Exception('Faltan datos obligatorios del afiliado.');
        }
        if (empty($datos['punto_entrega'])) {
            throw new Exception('Debes seleccionar un punto de entrega.');
        }

        $email  = strtolower(trim($datos['email']));
        $nombre = trim($datos['nombre_completo']);

        try {
            $this->conexion->begin_transaction();

            // ---------- 1. Usuario ----------
            $usuarioId = !empty($datos['usuario_id']) ? (int) $datos['usuario_id'] : 0;

            if (!$usuarioId) {
                $existente = $this->obtenerUsuarioPorEmail($email);
                if ($existente) {
                    $usuarioId = (int) $existente['id'];
                } else {
                    $password  = !empty($datos['crear_cuenta']) ? ($datos['password'] ?? null) : null;
                    $usuarioId = $this->crearUsuario($nombre, $email, $password);
                }
            }

            // ---------- 2. Validar productos y stock ----------
            $stmtProd = $this->conexion->prepare(
                "SELECT id, nombre, precio, descuento, stock FROM productos
                 WHERE id = ? AND estado = 'activo' FOR UPDATE"
            );

            $itemsValidados = [];
            $totalCalculado = 0;

            foreach ($datos['items'] as $item) {
                $productoId = (int) ($item['producto_id'] ?? 0);
                $cantidad   = (int) ($item['cantidad'] ?? 0);

                if ($productoId <= 0 || $cantidad <= 0) {
                    throw new Exception('Producto o cantidad inválida en el pedido.');
                }

                $stmtProd->bind_param("i", $productoId);
                $stmtProd->execute();
                $producto = $stmtProd->get_result()->fetch_assoc();

                if (!$producto) {
                    throw new Exception("El producto #{$productoId} ya no está disponible.");
                }
                if ((int) $producto['stock'] < $cantidad) {
                    throw new Exception("Stock insuficiente para \"{$producto['nombre']}\" (disponibles: {$producto['stock']}).");
                }

                // Precio recalculado en servidor: no se confía en el total del navegador
                $precio    = (float) $producto['precio'];
                $descuento = (float) ($producto['descuento'] ?? 0);
                $precioUni = round($precio - ($precio * $descuento / 100), 2);
                $subtotal  = round($precioUni * $cantidad, 2);

                $itemsValidados[] = [
                    'producto_id'     => $productoId,
                    'cantidad'        => $cantidad,
                    'precio_unitario' => $precioUni,
                    'subtotal'        => $subtotal
                ];
                $totalCalculado += $subtotal;
            }

            // ---------- 3. Insertar pedido ----------
            $tipoDoc      = $datos['tipo_documento'] ?? 'CC';
            $documento    = trim($datos['documento']);
            $telefono     = trim($datos['telefono']);
            $departamento = $datos['departamento'] ?? null;
            $ciudad       = $datos['ciudad'] ?? null;
            $direccion    = $datos['direccion'] ?? null;
            $puntoEntrega = $datos['punto_entrega'];
            $notas        = $datos['notas'] ?? null;
            $metodoPago   = $datos['metodo_pago'] ?? 'pago_en_cis';

            $stmt = $this->conexion->prepare(
                "INSERT INTO pedidos
                    (usuario_id, nombre_completo, tipo_documento, documento, telefono, email,
                     departamento, ciudad, direccion, punto_entrega, notas, metodo_pago, total, estado)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')"
            );
            $stmt->bind_param(
                "isssssssssssd",
                $usuarioId, $nombre, $tipoDoc, $documento, $telefono, $email,
                $departamento, $ciudad, $direccion, $puntoEntrega, $notas, $metodoPago, $totalCalculado
            );
            $stmt->execute();
            $pedidoId = (int) $this->conexion->insert_id;

            // ---------- 4. Detalles + descontar stock ----------
            $stmtDetalle = $this->conexion->prepare(
                "INSERT INTO detalles_pedidos (pedido_id, producto_id, cantidad, precio_unitario, subtotal)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmtStock = $this->conexion->prepare(
                "UPDATE productos SET stock = stock - ? WHERE id = ?"
            );

            foreach ($itemsValidados as $it) {
                $stmtDetalle->bind_param(
                    "iiidd",
                    $pedidoId, $it['producto_id'], $it['cantidad'], $it['precio_unitario'], $it['subtotal']
                );
                $stmtDetalle->execute();

                $stmtStock->bind_param("ii", $it['cantidad'], $it['producto_id']);
                $stmtStock->execute();
            }

            // ---------- 5. Número de pedido legible ----------
            $numeroPedido = 'FE-' . date('Y') . '-' . str_pad($pedidoId, 5, '0', STR_PAD_LEFT);
            $stmtNum = $this->conexion->prepare("UPDATE pedidos SET numero_pedido = ? WHERE id = ?");
            $stmtNum->bind_param("si", $numeroPedido, $pedidoId);
            $stmtNum->execute();

            $this->conexion->commit();

            return [
                'pedido_id'     => $pedidoId,
                'numero_pedido' => $numeroPedido,
                'usuario_id'    => $usuarioId,
                'total'         => $totalCalculado
            ];

        } catch (Throwable $e) {
            $this->conexion->rollback();
            throw new Exception($e->getMessage());
        }
    }

    /** Pedido con sus detalles (para admin o "mis pedidos"). */
    public function obtenerPedido($pedidoId) {
        $stmt = $this->conexion->prepare("SELECT * FROM pedidos WHERE id = ?");
        $stmt->bind_param("i", $pedidoId);
        $stmt->execute();
        $pedido = $stmt->get_result()->fetch_assoc();
        if (!$pedido) return null;

        $stmt = $this->conexion->prepare(
            "SELECT d.*, p.nombre AS producto
             FROM detalles_pedidos d
             INNER JOIN productos p ON p.id = d.producto_id
             WHERE d.pedido_id = ?"
        );
        $stmt->bind_param("i", $pedidoId);
        $stmt->execute();
        $pedido['detalles'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return $pedido;
    }

    /** Pedidos de un usuario, el más reciente primero. */
    public function obtenerPedidosPorUsuario($usuarioId) {
        $stmt = $this->conexion->prepare(
            "SELECT id, numero_pedido, total, estado, punto_entrega, fecha_creacion
             FROM pedidos WHERE usuario_id = ? ORDER BY fecha_creacion DESC"
        );
        $stmt->bind_param("i", $usuarioId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Todos los pedidos (panel admin). */
    public function obtenerPedidos($estado = null) {
        $query  = "SELECT p.*, u.nombre AS usuario FROM pedidos p LEFT JOIN usuarios u ON u.id = p.usuario_id";
        if ($estado) $query .= " WHERE p.estado = ?";
        $query .= " ORDER BY p.fecha_creacion DESC";

        $stmt = $this->conexion->prepare($query);
        if ($estado) $stmt->bind_param("s", $estado);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Cambia el estado: pendiente | confirmado | enviado | entregado | cancelado */
    public function actualizarEstadoPedido($pedidoId, $estado) {
        $permitidos = ['pendiente', 'confirmado', 'enviado', 'entregado', 'cancelado'];
        if (!in_array($estado, $permitidos, true)) {
            throw new Exception('Estado de pedido inválido.');
        }
        $stmt = $this->conexion->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
        $stmt->bind_param("si", $estado, $pedidoId);
        return $stmt->execute();
    }
}
