    
<?php
class usuarios
{
    // Datos de la tabla "usuario"
    const NOMBRE_TABLA = "usuario";
    const ID_USUARIO = "idUsuario";
    const USER = "user";
    const CONTRASENA = "password";
    const NOMBRE = "nombre";
    const APELLIDO = "apellido";
    const CORREO = "correo";
    const TELEFONO = "telefono";
    const CLAVE_API = "claveApi";
    const idTrabajador = "idTrabajador";

    const ESTADO_CREACION_EXITOSA = "Creación exitosa";
    const ESTADO_URL_INCORRECTA = "Ruta incorrecta";
    const ESTADO_CREACION_FALLIDA = "Creación fallida";
    const ESTADO_FALLA_DESCONOCIDA = "Falla desconocida";
    const ESTADO_ERROR_BD = "Error de Base de Datos";
    
    public static function post($peticion) {
        if ($peticion[0] == 'registro') {            
            return self::registrar();
        } else if ($peticion[0] == 'login') {
            return self::loguear();
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);
        }
    }    
   
    private function registrar() {
        $cuerpo = file_get_contents('php://input');
        $usuario = json_decode($cuerpo);        
        $resultado = self::crear($usuario);

        switch ($resultado) {
            case self::ESTADO_CREACION_EXITOSA:
                http_response_code(200);
                return
                    [
                        "estado" => self::ESTADO_CREACION_EXITOSA,
                        "mensaje" => utf8_encode("¡Registro con éxito!")
                    ];
                break;
            case self::ESTADO_CREACION_FALLIDA:
                throw new ExcepcionApi(self::ESTADO_CREACION_FALLIDA, "Ha ocurrido un error");
                break;
            default:
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA, "Falla desconocida", 400);
        }
    }

    private function crear($datosUsuario) {
        $user = $datosUsuario->user;
        $password_user = $datosUsuario->password;
        $contrasenaEncriptada = self::encriptarContrasena($password_user);
        $nombre = $datosUsuario->nombre;
        $apellido = $datosUsuario->apellido;
        $correo_user = $datosUsuario->correo;
        $telefono = $datosUsuario->telefono;
        $claveApi = self::generarClaveApi();
        $id_trabajador = $datosUsuario->idTrabajador;

        try {

            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia INSERT
            $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .

                self::USER . "," .
                self::CONTRASENA . "," .
                self::NOMBRE . "," .
                self::APELLIDO . "," .
                self::CORREO . "," .
                self::TELEFONO . "," .
                self::CLAVE_API . "," .
                self::idTrabajador . ")" .
                " VALUES(?,?,?,?,?,?,?,?)";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $user);
            $sentencia->bindParam(2, $contrasenaEncriptada);
            $sentencia->bindParam(3, $nombre);
            $sentencia->bindParam(4, $apellido);
            $sentencia->bindParam(5, $correo_user);
            $sentencia->bindParam(6, $telefono);
            $sentencia->bindParam(7, $claveApi);
            $sentencia->bindParam(8, $idTrabajador);

            $resultado = $sentencia->execute();

            if ($resultado) {
                return self::ESTADO_CREACION_EXITOSA;
            } else {
                return self::ESTADO_CREACION_FALLIDA;
            }
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private function encriptarContrasena($contrasenaPlana) {
        if ($contrasenaPlana)
            return password_hash($contrasenaPlana, PASSWORD_DEFAULT);
        else return null;
    }

    private function generarClaveApi() {
        return md5(microtime().rand());
    }

}

?>