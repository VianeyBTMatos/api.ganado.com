<?php
      
include_once '../v1/datos/ConexionBD.php';

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

    const ESTADO_CREACION_EXITOSA = 1;
    const ESTADO_CREACION_FALLIDA = 2;
    const ESTADO_ERROR_BD = 3;
    const ESTADO_AUSENCIA_CLAVE_API = 4;
    const ESTADO_CLAVE_NO_AUTORIZADA = 5;
    const ESTADO_URL_INCORRECTA = 6;
    const ESTADO_FALLA_DESCONOCIDA = 7;
    const ESTADO_PARAMETROS_INCORRECTOS = 8;
    //header('Access-Control-Allow-Origin: http://localhost/wservice/consumeWS.html', false);

    public static function get($peticion)
    {
       
        //consulta si el usuario tiene una clave de poder hacer cambios
        //$idUsuario = usuarios::autorizar();
    
        //si la variable peticion esta vacia
        if (empty($peticion[0]))
        {
            return self::obtenerIdUsuario($peticion[0]);
        }
        else
            return self::obtenerIdUsuario($peticion[0]);

    }

    public static function post($peticion)
    {
        if ($peticion[0] == 'registro') {
            return self::registrar();
        } else if ($peticion[0] == 'login') {
            return self::loguear();
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);
        }
    }

    public static function delete($peticion) //------------------delete
    {
        //$idUsuario = usuarios::autorizar();
        
        if (!empty($peticion[0])) {
            if (self::eliminar($peticion[0]) > 0) {
                http_response_code(200);
                return [
                    "estado" => self::CODIGO_EXITO,
                    "mensaje" => "Registro eliminado correctamente"
                ];
            } else {
                throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO,
                    "El contacto al que intentas acceder no existe", 404);
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_ERROR_PARAMETROS, "Falta id", 422);
        }

    }
    public static function put($peticion)  //------------------put
    {
        //$idUsuario = usuarios::autorizar();   
        $body = file_get_contents('php://input');
        $usuario = json_decode($body);
             
        if (!empty($peticion[0])) {
            //$body = file_get_contents('php://input');
            //$usuario = json_decode($body);
            
            if (self::actualizar($usuario, $peticion[0]) > 0) {
                http_response_code(200);
                return [
                    "estado" => self::CODIGO_EXITO,
                    "mensaje" => "Registro actualizado correctamente"
                ];
            } else {
                throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO,
                    "El producto al que intentas acceder no existe", 404);
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_ERROR_PARAMETROS, "Falta id", 422);
        }
    }



    /**
     * Crea un nuevo usuario en la base de datos
     */
    private function registrar()
    {
        $cuerpo = file_get_contents('php://input');
        $usuario =  json_decode($cuerpo);
        $resultado = self::crear($usuario);

        switch ($resultado) {
            case self::ESTADO_CREACION_EXITOSA:
                http_response_code(200);
                return
                    [
                        "estado" => self::ESTADO_CREACION_EXITOSA,
                        "mensaje" => utf8_encode("Registro con exito!")
                    ];                    
                break;
            case self::ESTADO_CREACION_FALLIDA:
                throw new ExcepcionApi(self::ESTADO_CREACION_FALLIDA, "Ha ocurrido un error");
                break;
            default:
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA, "Falla desconocida", 400);
        }
    }

    /**
     * Crea un nuevo usuario en la tabla "usuario"
     * @param mixed $datosUsuario columnas del registro
     * @return int codigo para determinar si la inserción fue exitosa
     */
    private function crear($datosUsuario){
        $user = $datosUsuario->user;
        $password_user = $datosUsuario->contrasena;
        $contrasenaEncriptada = self::encriptarContrasena($password_user);
        $nombre = $datosUsuario->nombre;
        $apellido = $datosUsuario->apellido;
        $correo_user = $datosUsuario->correo;
        $telefono = $datosUsuario->telefono;
        $claveApi = self::generarClaveApi();
        $id_trabajador = $datosUsuario->idTrabajador;

      //  $contrasena = $datosUsuario->contrasena;
      //  $contrasenaEncriptada = self::encriptarContrasena($contrasena);
      //  $claveApi = self::generarClaveApi();

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
            $sentencia->bindParam(8, $id_trabajador);

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
    private function actualizar($usuario, $idUsuario)
    {        
        try {
            
            // Creando consulta UPDATE
            $consulta = "UPDATE " . self::NOMBRE_TABLA .
                " SET " .
                self::USER . "=?," .
                self::CONTRASENA . "=?," .
                self::NOMBRE . "=?," .
                self::APELLIDO . "=?," .
                self::CORREO . "=?," .
                self::TELEFONO . "=?," .
                self::idTrabajador . "=?" .
                " WHERE " . self::ID_USUARIO . "=?";                                
            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);
            $sentencia->bindParam(1, $usuario->user);
            $sentencia->bindParam(2, $usuario->contrasena);
            $sentencia->bindParam(3, $usuario->nombre);
            $sentencia->bindParam(4, $usuario->apellido);
            $sentencia->bindParam(5, $usuario->correo);
            $sentencia->bindParam(6, $usuario->telefono);
            $sentencia->bindParam(7, $usuario->id_trabajador);
            $sentencia->bindParam(8, $idUsuario);
            


            // Ejecutar la sentencia
            $sentencia->execute();

            return $sentencia->rowCount();
            

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }
    private function eliminar($id_tipo)
    {
        try {
            
            // Sentencia DELETE
           $comando = "DELETE FROM " . self::NOMBRE_TABLA .
                " WHERE " . self::ID_USUARIO . "=? ";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

            $sentencia->bindParam(1, $id_tipo);

            $sentencia->execute();
            return $sentencia->rowCount();            

        } catch (PDOException $e) { 
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    /**
     * Protege la contrase�a con un algoritmo de encriptado
     * @param $contrasenaPlana
     * @return bool|null|string
     */
    private function encriptarContrasena($contrasenaPlana)
    {
        if ($contrasenaPlana) {
            return password_hash($contrasenaPlana, PASSWORD_DEFAULT);
        } else {
            return null;
        }
    }
    private function generarClaveApi()
    {
        return md5(microtime() . rand());
    }

    private function loguear() //----------------------loguear
    {
        $respuesta = array();
        $body = file_get_contents('php://input');
        $usuario = json_decode($body);
        $correo = $usuario->correo;
        $contrasena = $usuario->contrasena;

        if (self::autenticar($correo, $contrasena)){
            $usuarioBD = self::obtenerUsuarioPorCorreo($correo);
            if($usuarioBD != NULL){
                http_response_code(200);
                $respuesta["user"] = $usuarioBD["user"];
                $respuesta["password"] = $usuarioBD["password"];
                $respuesta["nombre"] = $usuarioBD["nombre"];
                $respuesta["apellido"] = $usuarioBD["apellido"];
                $respuesta["correo"] = $usuarioBD["correo"];
                $respuesta["telefono"] = $usuarioBD["telefono"];
                $respuesta["claveApi"] = $usuarioBD["claveApi"];
                $respuesta["idTrabajador"] = $usuarioBD["idTrabajador"];
                return ["estado" => 1, "usuario" => $respuesta];
            }else{
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                    "Ha ocurrido un error");
            }
        }else{
            throw new ExcepcionApi(self::ESTADO_PARAMETROS_INCORRECTOS,
                utf8_encode("Correo o contrasena invalidos"));
        }
        
    }
    
    private function autenticar($correo, $contrasena)
    {  

        //$comando = "SELECT usuario.password FROM " . self::NOMBRE_TABLA . " WHERE " . self::CORREO . "=?";
        $comando = "SELECT ". self::NOMBRE_TABLA . ".password FROM " . self::NOMBRE_TABLA . " WHERE " . self::CORREO . "=" . "'" . $correo . "'";
         try{
             $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
             $sentencia->bindParam(1, $correo);
             $sentencia->execute();
            // return $comando;
            
             if ($sentencia){
                 $resultado = $sentencia->fetch();
                                
                 if(self::validarContrasena($contrasena, $resultado["password"])){
                     return true;
                 } else {
                     return false;
                 }
             } else {
                 return false;
             }
         } catch (PDOException $e){
             throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
         }
    }

    private function validarContrasena($contrasenaPlana, $contrasenaHash)
    {             
        return password_verify($contrasenaPlana, $contrasenaHash);
    }


    private function obtenerUsuarioPorCorreo($correo)
    {
        $comando = "SELECT ".
            self::USER . ",".
            self::CONTRASENA . ",".
            self::NOMBRE . ",".
            self::APELLIDO . ",".
            self::CORREO . ",".
            self::TELEFONO . ",".
            self::CLAVE_API . ",".
            self::idTrabajador . "".
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::CORREO . "= ?" ;
         $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
         $sentencia->bindParam(1, $correo);

         if ($sentencia->execute()){
             return $sentencia->fetch(PDO::FETCH_ASSOC);
         } else {
             return null;
         }
    }

    /**
     * Otorga los permisos a un usuario para que acceda a los recursos
     * @return null o el id del usuario autorizado
     * @throws Exception
     */
    public static function autorizar()
    {
        $cabeceras = apache_request_headers();

        if (isset($cabeceras["authorization"])) {

            $claveApi = $cabeceras["authorization"];

            if (empleados::validarClaveApi($claveApi)) {
                return empleados::obtenerIdUsuario($claveApi);
            } else {
                throw new ExcepcionApi(
                    self::ESTADO_CLAVE_NO_AUTORIZADA, "Clave de API no autorizada", 401);
            }

        } else {
            throw new ExcepcionApi(
                self::ESTADO_AUSENCIA_CLAVE_API,
                utf8_encode("Se requiere Clave del API para autenticacion"));
        }
    }

    /**
     * Comprueba la existencia de la clave para la api
     * @param $claveApi
     * @return bool true si existe o false en caso contrario
     */
    private function validarClaveApi($claveApi)
    {
        $comando = "SELECT COUNT (" . self::ID_USUARIO . ")" .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::CLAVE_API . "=?";

        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
        $sentencia->bindParam(1, $claveApi);
        $sentencia->execute();
        return $sentencia->fetchColumn(0) > 0;
    }

    /**
     * Obtiene el valor de la columna "idUsuario" basado en la clave de api
     * @param $claveApi
     * @return null si este no fue encontrado
     */

    private function obtenerIdUsuario($idUsuario)
    {
        
        $comando = "SELECT " . self::NOMBRE .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::ID_USUARIO . "=?";            
            
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
            $sentencia->bindParam(1, $idUsuario);
            
            if($sentencia->execute()){
                $resultado = $sentencia->fetch();            
                return $resultado;
                
            }else {
                return null;
            }
    }


}