<?php
namespace Config;

class Conexion {
    // Propiedades privadas para la seguridad de las credenciales
    private string $usuario = 'jean'; 
    private string $clave = 'Jp121312'; 
    private string $string_conexion = '//localhost:1521/FREEPDB1'; 
    private string $charset = 'AL32UTF8'; 
    private $conexion = null;

    /**
     * Establece y retorna la conexión a Oracle
     */
    public function conectar() {
        // La arroba (@) suprime los warnings nativos de PHP
        $this->conexion = @oci_connect($this->usuario, $this->clave, $this->string_conexion, $this->charset);

        if (!$this->conexion) {
            $error = oci_error();
            // Lanzamos una excepción para que el Controlador la maneje limpiamente
            throw new \Exception("Error de conexión a la Base de Datos: " . htmlentities($error['message'], ENT_QUOTES, 'UTF-8'));
        }

        return $this->conexion;
    }

    /**
     * Cierra la conexión a la base de datos de forma segura
     */
    public function desconectar() {
        if ($this->conexion) {
            oci_close($this->conexion);
            $this->conexion = null;
        }
    }
}
?>