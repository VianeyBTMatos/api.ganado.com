<?php

class ganado
{
    const NOMBRE_TABLA = "ganado";
    const ID_GANADO = "idGanado";
    const ID_ESPECIE = "idEspecie";
    const FECHA_NACIMIENTO = "fechaNacimiento";
    const GENERO = "genero";
    const APODO = "apodo";
    const PESO = "peso";

    const CODIGO_EXITO = 1;
    const ESTADO_EXITO = 1;
    const ESTADO_ERROR = 2;
    const ESTADO_ERROR_BD = 3;
    const ESTADO_ERROR_PARAMETROS = 4;
    const ESTADO_NO_ENCONTRADO = 5;

    public static function get($peticion)
    {
        //consulta si el usuario tiene una clave de poder hacer cambios
        $idUsuario = usuarios::autorizar();

        //si la variable peticion esta vacia

        if (empty($peticion[0]))
            return self::obtenerGanado($idUsuario);
        else
            return self::obtenerGanado($idUsuario, $peticion[0]);

    }

    public static function post($peticion) //------------------post
    {
        $idUsuario = usuarios::autorizar();

        $body = file_get_contents('php://input');
        $ganado = json_decode($body);
        $id_ganado = ganado::crear($ganado);

        http_response_code(201);
        return [
            "estado" => self::CODIGO_EXITO,
            "mensaje" => "Nuevo elemento incrustado",
            "id" => $id_ganado
        ];

    }

    public static function put($peticion)  //------------------put
    {
        $idUsuario = usuarios::autorizar();
        if (!empty($peticion[0])) {
            $body = file_get_contents('php://input');
            $ganado = json_decode($body);

            if (self::actualizar($ganado, $peticion[0]) > 0) {
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

    public static function delete($peticion) //------------------delete
    {
        $idUsuario = usuarios::autorizar();

        if (!empty($peticion[0])) {
            if (self::eliminar($idUsuario, $peticion[0]) > 0) {
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

    /**
     * Obtiene la colección de contactos o un solo contacto indicado por el identificador
     * @param int $idUsuario identificador del usuario
     * @param null $idContacto identificador del contacto (Opcional)
     * @return array registros de la tabla contacto
     * @throws Exception
     */
    private function obtenerGanado($idUsuario, $idganado = NULL)
    {        
        try {                        
            if (!$idganado) {
                $comando = "SELECT * FROM " . self::NOMBRE_TABLA ;                   

                // Preparar sentencia
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

            } else {                
                $comando = "SELECT * FROM " . self::NOMBRE_TABLA .
                    " WHERE " . self::ID_GANADO . "=" . "'" . $idganado . "'";
                // Preparar sentencia
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

            }

            // Ejecutar sentencia preparada
            if ($sentencia->execute()) {
                http_response_code(200);
                return
                    [
                        "estado" => self::ESTADO_EXITO,
                        "datos" => $sentencia->fetchAll(PDO::FETCH_ASSOC)
                    ];
            } else
                throw new ExcepcionApi(self::ESTADO_ERROR, "Se ha producido un error");

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    /**
     * Añade un nuevo contacto asociado a un usuario
     * @param int $idUsuario identificador del usuario
     * @param mixed $contacto datos del contacto
     * @return string identificador del contacto
     * @throws ExcepcionApi
     */

    private function crear($ganado)
    {
        if ($ganado) {
            try {
                $pdo = ConexionBD::obtenerInstancia()->obtenerBD();
                $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                    
                    self::ID_ESPECIE . "," .
                    self::FECHA_NACIMIENTO . "," .
                    self::GENERO . "," .
                    self::APODO . "," .
                    self::PESO . "," .
                    " VALUES(?,?,?,?,?,?)";

                // Preparar la sentencia
                $sentencia = $pdo->prepare($comando);

                
                $sentencia->bindParam(1, $ganado->idEspecie);
                $sentencia->bindParam(2, $ganado->fechaNacimiento);
                $sentencia->bindParam(3, $ganado->genero);
                $sentencia->bindParam(4, $ganado->apodo);
                $sentencia->bindParam(5, $ganado->peso);
                $sentencia->execute();

                // Retornar en el último id insertado
                return $pdo->lastInsertId();

            } catch (PDOException $e) {
                throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
            }
        } else {
            throw new ExcepcionApi(
                self::ESTADO_ERROR_PARAMETROS,
                utf8_encode("Error en existencia o sintaxis de parámetros"));
        }

    }

    /**
     * Actualiza el contacto especificado por idUsuario
     * @param int $idUsuario
     * @param object $contacto objeto con los valores nuevos del contacto
     * @param int $idContacto
     * @return PDOStatement
     * @throws Exception
     */
    private function actualizar($ganado, $idGanado)
    {
        try {
            // Creando consulta UPDATE
            $consulta = "UPDATE " . self::NOMBRE_TABLA .
                " SET " .
                self::ID_ESPECIE . "," .
                self::FECHA_NACIMIENTO . "," .
                self::GENERO . "," .
                self::APODO . "," .
                self::PESO . "," .
                " WHERE " . self::ID_GANADO . "=?";


            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

            //$sentencia->bindParam(1, $idCliente);
            $sentencia->bindParam(1, $idEspecie);
            $sentencia->bindParam(2, $Fecha_Nacimiento);
            $sentencia->bindParam(3, $genero);
            $sentencia->bindParam(4, $apodo);
            $sentencia->bindParam(5, $peso);

            //
            $idEspecie = $ganado->idEspecie;
            $Fecha_Nacimiento = $ganado->fechaNacimiento;
            $genero = $ganado->genero;
            $apodo = $ganado->apodo;
            $peso = $ganado->peso;

            // Ejecutar la sentencia
            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }


    /**
     * Elimina un contacto asociado a un usuario
     * @param int $idUsuario identificador del usuario
     * @param int $idContacto identificador del contacto
     * @return bool true si la eliminación se pudo realizar, en caso contrario false
     * @throws Exception excepcion por errores en la base de datos
     */
    private function eliminar($idUsuario, $idGanado)
    {
        try {
            // Sentencia DELETE
            $comando = "DELETE FROM " . self::NOMBRE_TABLA .
                " WHERE " . self::ID_GANADO . "=? ";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

            $sentencia->bindParam(1, $idGanado);          

            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }
}
