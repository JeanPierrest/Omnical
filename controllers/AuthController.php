<?php
namespace Controllers;
require_once __DIR__ . '/../models/AuthModel.php';
use Models\AuthModel;

class AuthController {
    private $modelo;

    public function __construct() {
        $this->modelo = new AuthModel();
    }

    public function mostrarLogin() {
        require_once __DIR__ . '/../views/login.php';
    }

    public function procesarLogin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $correo   = trim($_POST['correo']   ?? '');
            $password = trim($_POST['password'] ?? '');

            // Validación básica antes de consultar la BD
            if (empty($correo) || empty($password)) {
                header("Location: index.php?accion=login&error=1");
                exit;
            }

            $usuario = $this->modelo->validarUsuario($correo, $password);

            if ($usuario) {
                // Guardamos datos en sesión (session_start() ya fue llamado en index.php)
                $_SESSION['id_usuario']      = $usuario['ID_USUARIO'];
                $_SESSION['id_rol']          = $usuario['ID_ROL'];
                $_SESSION['nombre_rol']      = $usuario['NOMBRE_ROL'];
                $_SESSION['nombre_completo'] = $usuario['NOMBRES'] . ' ' . $usuario['APELLIDOS'];

                // Redirigimos según el rol al destino correcto
                switch ($usuario['NOMBRE_ROL']) {
                    case 'Super Admin':
                        header("Location: index.php?accion=dashboard");
                        break;
                    case 'Mini Admin':
                        header("Location: index.php?accion=mis_casos");
                        break;
                    case 'Mid Mini Admin':
                        header("Location: index.php?accion=mi_bolsa_especifica");
                        break;
                    case 'Solicitante':
                        header("Location: index.php?accion=mis_tickets");
                        break;
                    case 'Visualizador':
                        header("Location: index.php?accion=visualizador");
                        break;
                    default:
                        // Rol desconocido: forzar logout por seguridad
                        header("Location: index.php?accion=login&error=1");
                        break;
                }
                exit;

            } else {
                // Credenciales incorrectas
                header("Location: index.php?accion=login&error=1");
                exit;
            }
        }

        // Si alguien llega aquí por GET, lo mandamos al login
        header("Location: index.php?accion=login");
        exit;
    }

    public function logout() {
        // session_start() ya fue llamado en index.php
        $_SESSION = [];
        session_destroy();
        header("Location: index.php?accion=login");
        exit;
    }
}
?>