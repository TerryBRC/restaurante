<?php
require_once __DIR__ . '/../config/database.php';

class ProductModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function getAllProducts() {
        try {
            $query = 'SELECT p.*, c.Nombre_Categoria, c.is_food 
                     FROM productos p 
                     LEFT JOIN categorias c ON p.ID_Categoria = c.ID_Categoria 
                     ORDER BY p.Nombre_Producto';
            
            $stmt = $this->conn->query($query);
            
            if (!$stmt) {
                return [];
            }
            
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $productos;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getAllCategories() {
        try {
            $query = 'SELECT * FROM categorias ORDER BY Nombre_Categoria';
            
            $stmt = $this->conn->query($query);
            
            if (!$stmt) {
                return [];
            }
            
            $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $categorias;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function addProduct($nombre, $precioCosto, $precioVenta, $idCategoria, $stock) {
        try {
            $stmt = $this->conn->prepare('CALL sp_AddProduct(?, ?, ?, ?, ?)');
            $stmt->execute([
                $nombre,
                $precioCosto,
                $precioVenta,
                $idCategoria,
                $stock
            ]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function updateProduct($idProducto, $nombre, $precioCosto, $precioVenta, $idCategoria, $stock) {
        try {
            $stmt = $this->conn->prepare('CALL sp_UpdateProduct(?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $idProducto,
                $nombre,
                $precioCosto,
                $precioVenta,
                $idCategoria,
                $stock
            ]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getProductsByCategory($idCategoria) {
        try {
            $stmt = $this->conn->prepare('SELECT * FROM productos WHERE ID_Categoria = ?');
            $stmt->execute([$idCategoria]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }
        /**
     * Devuelve el total de productos en la base de datos.
     * @return int
     */
    public function getTotalProducts() {
        try {
            $stmt = $this->conn->query('SELECT COUNT(*) as total FROM productos');
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? (int)$row['total'] : 0;
        } catch (PDOException $e) {
            return 0;
        }
    }
}