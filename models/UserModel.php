<?php
require_once __DIR__ . '/../config/database.php';

class UserModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function validateUser($username) {
        try {
            $stmt = $this->conn->prepare('CALL sp_ValidateUser(?)');
            $stmt->execute([$username]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function verifyPassword($password, $hashedPassword) {
        return password_verify($password, $hashedPassword);
    }

    public function checkUserExists($username) {
        try {
            $stmt = $this->conn->prepare('CALL sp_CheckUserExists(?, @exists)');
            $stmt->execute([$username]);
            $stmt->closeCursor();
            
            $result = $this->conn->query('SELECT @exists AS existe')->fetch(PDO::FETCH_ASSOC);
            return $result['existe'] == 1;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function createUserWithEmployee($userData) {
        try {
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            $stmt = $this->conn->prepare('CALL sp_CreateUserWithEmployee(?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $userData['username'],
                $hashedPassword,
                $userData['role_id'],
                $userData['nombre_completo'],
                $userData['correo'],
                $userData['telefono'],
                $userData['fecha_contratacion']
            ]);
            return true;
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    public function updateUser($userId, $username, $roleId, $password = null) {
        try {
            if ($password) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            } else {
                $hashedPassword = null;
            }
            $stmt = $this->conn->prepare('CALL sp_UpdateUser(?, ?, ?, ?)');
            $stmt->execute([$userId, $username, $hashedPassword, $roleId]);
            return true;
        } catch (PDOException $e) {
            error_log('Error en updateUser: ' . $e->getMessage());
            return $e->getMessage();
        }
    }

    public function getAllUsers() {
        try {
            $query = "SELECT u.ID_usuario, e.Nombre_Completo as Nombre, u.Nombre_Usuario as Usuario, 
                     r.Nombre_Rol as Rol, e.ID_Empleado, e.Correo, e.Telefono, e.Fecha_Contratacion, u.Estado
                     FROM usuarios u 
                     INNER JOIN empleados e ON u.ID_usuario = e.ID_Usuario 
                     INNER JOIN roles r ON u.ID_Rol = r.ID_Rol
                     order by u.ID_usuario ASC
                     ";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getEmpleadoIdByUserId($userId) {
        try {
            $stmt = $this->conn->prepare('SELECT ID_Empleado FROM empleados WHERE ID_Usuario = ?');
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['ID_Empleado'] : null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function deleteUser($userId) {
        try {
            $query = "UPDATE empleados SET Estado = 0 WHERE ID_Usuario = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$userId]);
            return true;
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    public function getUserById($userId) {
        try {
            $query = "SELECT u.ID_usuario, e.Nombre_Completo, u.Nombre_Usuario, 
                     r.ID_Rol, r.Nombre_Rol, e.Correo, e.Telefono, e.Fecha_Contratacion, u.Estado 
                     FROM usuarios u 
                     INNER JOIN empleados e ON u.ID_usuario = e.ID_Usuario 
                     INNER JOIN roles r ON u.ID_Rol = r.ID_Rol 
                     WHERE u.ID_usuario = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

        public function getAllUsersPaginated($offset = 0, $limit = 10) {
        try {
            $query = "SELECT u.ID_usuario, e.Nombre_Completo as Nombre, u.Nombre_Usuario as Usuario, 
                     r.Nombre_Rol as Rol, e.ID_Empleado, e.Correo, e.Telefono, e.Fecha_Contratacion, u.Estado
                     FROM usuarios u 
                     LEFT JOIN empleados e ON u.ID_usuario = e.ID_Usuario 
                     LEFT JOIN roles r ON u.ID_Rol = r.ID_Rol
                     ORDER BY u.ID_usuario ASC
                     LIMIT :limit OFFSET :offset";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    public function getTotalUsers() {
        try {
            $stmt = $this->conn->query('SELECT COUNT(*) as total FROM usuarios');
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? (int)$row['total'] : 0;
        } catch (PDOException $e) {
            return 0;
        }
    }
}