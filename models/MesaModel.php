<?php
require_once __DIR__ . '/../config/database.php';

class MesaModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function getAllTables() {
        try {
            $stmt = $this->conn->prepare('SELECT * FROM mesas ORDER BY Numero_Mesa');
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }
    public function getTotalTables() {
        try {
            $stmt = $this->conn->query('SELECT COUNT(*) as total FROM mesas');
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? (int)$row['total'] : 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function updateTableStatus($idMesa, $estado) {
        try {
            $stmt = $this->conn->prepare('CALL sp_UpdateTableStatus(?, ?)');
            return $stmt->execute([$idMesa, $estado]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function addTable($numeroMesa, $capacidad) {
        try {
            $stmt = $this->conn->prepare('INSERT INTO mesas (Numero_Mesa, Capacidad, Estado) VALUES (?, ?, 0)');
            return $stmt->execute([$numeroMesa, $capacidad]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function updateTable($idMesa, $numeroMesa, $capacidad) {
        try {
            $stmt = $this->conn->prepare('UPDATE mesas SET Numero_Mesa = ?, Capacidad = ? WHERE ID_Mesa = ?');
            return $stmt->execute([$numeroMesa, $capacidad, $idMesa]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getTableById($idMesa) {
        try {
            $stmt = $this->conn->prepare('SELECT * FROM mesas WHERE ID_Mesa = ?');
            $stmt->execute([$idMesa]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getAvailableTables() {
        try {
            $stmt = $this->conn->prepare('SELECT * FROM mesas WHERE Estado = 0 ORDER BY Numero_Mesa');
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }
}