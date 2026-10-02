<?php
// models/Model.php

require_once __DIR__ . '/../config/database.php';

class Model {
    protected $conexion;
    
    public function __construct() {
        global $conexion;
        $this->conexion = $conexion;
    }
    
    // ========== CATEGORÍAS ==========
    public function obtenerCategorias() {
        $query = "SELECT * FROM categorias WHERE estado = 'activo' ORDER BY nombre ASC";
        $result = $this->conexion->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function obtenerCategoriaPorId($id) {
        $query = "SELECT * FROM categorias WHERE id = ? AND estado = 'activo'";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function crearCategoria($nombre, $descripcion, $imagen) {
        $query = "INSERT INTO categorias (nombre, descripcion, imagen) VALUES (?, ?, ?)";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("sss", $nombre, $descripcion, $imagen);
        return $stmt->execute();
    }
    
    public function actualizarCategoria($id, $nombre, $descripcion, $imagen) {
        $query = "UPDATE categorias SET nombre = ?, descripcion = ?, imagen = ? WHERE id = ?";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("sssi", $nombre, $descripcion, $imagen, $id);
        return $stmt->execute();
    }
    
    public function eliminarCategoria($id) {
        $query = "UPDATE categorias SET estado = 'inactivo' WHERE id = ?";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    // ========== MARCAS ==========
    public function obtenerMarcas() {
        $query = "SELECT * FROM marcas WHERE estado = 'activo' ORDER BY nombre ASC";
        $result = $this->conexion->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function crearMarca($nombre, $descripcion, $logo) {
        $query = "INSERT INTO marcas (nombre, descripcion, logo) VALUES (?, ?, ?)";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("sss", $nombre, $descripcion, $logo);
        return $stmt->execute();
    }
    
    // ========== PRODUCTOS ==========
    public function obtenerProductos($categoria_id = null, $marca_id = null) {
        $query = "SELECT p.*, c.nombre as categoria, m.nombre as marca, 
                  GROUP_CONCAT(ip.url_imagen ORDER BY ip.es_principal DESC SEPARATOR ',') as imagenes
                  FROM productos p
                  LEFT JOIN categorias c ON p.categoria_id = c.id
                  LEFT JOIN marcas m ON p.marca_id = m.id
                  LEFT JOIN imagenes_productos ip ON p.id = ip.producto_id
                  WHERE p.estado = 'activo'";
        
        if ($categoria_id) {
            $query .= " AND p.categoria_id = " . intval($categoria_id);
        }
        if ($marca_id) {
            $query .= " AND p.marca_id = " . intval($marca_id);
        }
        
        $query .= " GROUP BY p.id ORDER BY p.nombre ASC";
        $result = $this->conexion->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function obtenerProductoPorId($id) {
        $query = "SELECT p.*, c.nombre as categoria, m.nombre as marca,
                  GROUP_CONCAT(ip.url_imagen ORDER BY ip.es_principal DESC SEPARATOR ',') as imagenes
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
        $query = "INSERT INTO productos (nombre, descripcion, precio, descuento, stock, categoria_id, marca_id) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("ssdiiiii", $nombre, $descripcion, $precio, $descuento, $stock, $categoria_id, $marca_id);
        
        if ($stmt->execute()) {
            return $this->conexion->insert_id;
        }
        return false;
    }
    
    public function actualizarProducto($id, $nombre, $descripcion, $precio, $descuento, $stock, $categoria_id, $marca_id) {
        $query = "UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, descuento = ?, 
                  stock = ?, categoria_id = ?, marca_id = ? WHERE id = ?";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("ssdiiiii", $nombre, $descripcion, $precio, $descuento, $stock, $categoria_id, $marca_id, $id);
        return $stmt->execute();
    }
    
    public function eliminarProducto($id) {
        $query = "UPDATE productos SET estado = 'inactivo' WHERE id = ?";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    // ========== IMÁGENES DE PRODUCTOS ==========
    public function obtenerImagenesProducto($producto_id) {
        $query = "SELECT * FROM imagenes_productos WHERE producto_id = ? ORDER BY es_principal DESC, id ASC";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function agregarImagenProducto($producto_id, $url_imagen, $es_principal = false) {
        $query = "INSERT INTO imagenes_productos (producto_id, url_imagen, es_principal) VALUES (?, ?, ?)";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("isi", $producto_id, $url_imagen, $es_principal);
        return $stmt->execute();
    }
    
    // ========== USUARIOS ==========
    public function obtenerUsuarios() {
        $query = "SELECT id, nombre, email, rol, estado, fecha_creacion FROM usuarios ORDER BY fecha_creacion DESC";
        $result = $this->conexion->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function verificarUsuario($email, $contraseña) {
        $query = "SELECT id, nombre, email, rol FROM usuarios WHERE email = ? AND contraseña = MD5(?) AND estado = 'activo'";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("ss", $email, $contraseña);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function crearUsuario($nombre, $email, $contraseña, $rol = 'cliente') {
        $query = "INSERT INTO usuarios (nombre, email, contraseña, rol) VALUES (?, ?, MD5(?), ?)";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("ssss", $nombre, $email, $contraseña, $rol);
        return $stmt->execute();
    }
    
    // ========== CARRITO ==========
    public function agregarAlCarrito($usuario_id, $producto_id, $cantidad) {
        $query = "INSERT INTO carritos (usuario_id, producto_id, cantidad) 
                  VALUES (?, ?, ?) 
                  ON DUPLICATE KEY UPDATE cantidad = cantidad + ?";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("iiii", $usuario_id, $producto_id, $cantidad, $cantidad);
        return $stmt->execute();
    }
    
    public function obtenerCarrito($usuario_id) {
        $query = "SELECT c.*, p.nombre, p.precio, p.descuento,
                  GROUP_CONCAT(ip.url_imagen ORDER BY ip.es_principal DESC SEPARATOR ',') as imagenes
                  FROM carritos c
                  JOIN productos p ON c.producto_id = p.id
                  LEFT JOIN imagenes_productos ip ON p.id = ip.producto_id
                  WHERE c.usuario_id = ?
                  GROUP BY c.id";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function eliminarDelCarrito($usuario_id, $producto_id) {
        $query = "DELETE FROM carritos WHERE usuario_id = ? AND producto_id = ?";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("ii", $usuario_id, $producto_id);
        return $stmt->execute();
    }
}
?>
