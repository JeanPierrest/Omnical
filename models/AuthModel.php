<?php
namespace Models;
require_once __DIR__ . '/../config/Conexion.php';
use Config\Conexion;

class AuthModel {
    private $db;

    public function __construct() {
        $this->db = new Conexion();
    }

    public function validarUsuario($correo, $password) {
        $conexion = $this->db->conectar();

        // Traemos también el nombre del rol para no depender de IDs numéricos
        $sql = "SELECT 
                    u.id_usuario, 
                    u.id_rol, 
                    r.nombre_rol,
                    u.nombres, 
                    u.apellidos,
                    u.password_hash
                FROM usuarios u
                INNER JOIN roles r ON u.id_rol = r.id_rol
                WHERE u.correo  = :p_correo 
                  AND u.estado  = 1";

        $stmt = oci_parse($conexion, $sql);
        oci_bind_by_name($stmt, ':p_correo', $correo);
        oci_execute($stmt);

        $usuario = oci_fetch_assoc($stmt);

        oci_free_statement($stmt);
        $this->db->desconectar();

        // Si no existe el correo, retornamos false de inmediato
        if (!$usuario) {
            return false;
        }

        // Verificamos la contraseña con password_verify (bcrypt)
        // IMPORTANTE: Los usuarios de prueba tienen '123456' en texto plano.
        // Para migrar a hash, ejecuta en Oracle:
        //   UPDATE usuarios SET password_hash = '<hash_generado>' WHERE correo = '...';
        // El hash se genera con: echo password_hash('123456', PASSWORD_BCRYPT);
        if (password_verify($password, $usuario['PASSWORD_HASH'])) {
            return $usuario;
        }

        // Fallback temporal para usuarios con contraseña en texto plano (semilla de prueba)
        // ELIMINAR ESTE BLOQUE una vez que todos los usuarios tengan hash
        if ($password === $usuario['PASSWORD_HASH']) {
            return $usuario;
        }

        return false;
    }
}
?>