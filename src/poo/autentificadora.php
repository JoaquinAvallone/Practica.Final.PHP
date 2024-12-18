<?php
use Firebase\JWT\JWT;

class Autentificadora
{
    private static string $secret_key = 'tuApellido.TuNombre'; //CAMBIALE ESTO
    private static array $encrypt = ['HS256'];
    
    public static function crearJWT(mixed $data, int $exp = (60*5)) : string
    {
        $ahora = time();

        $payload = array(
            'usuario' => $data,           
            'alumno' => "PONE ACA TU NOMBRE", //CAMBIALE ESTO
            'dni_alumno' => "PONE ACA TU DNI", //CAMBIALE ESTO
            'exp' => $ahora + $exp    
        );
            

        return JWT::encode($payload, self::$secret_key, "HS256");
    }
    

    public static function verificarJWT(string $token) : stdClass
    {
        $datos = new stdClass();
        $datos->exito = FALSE;
        $datos->mensaje = "";
        $datos->status = 403;

        try 
        {
            if( ! isset($token))
            {
                $datos->exito = FALSE;
            }
            else
            {          
                JWT::decode(
                    $token,
                    self::$secret_key,
                    self::$encrypt
                );

                $datos->exito = true;
                $datos->status = 200;
            }          
        } 
        catch (Exception $e) 
        {
            $datos->exito = false;
            $datos->status = 403;
            $datos->mensaje = "Token no válido!!! --> " . $e->getMessage();

        }
    
        return $datos;
    }
    
    public static function obtenerPayLoad(string $token) : object
    {
        $datos = new stdClass();
        $datos->exito = FALSE;
        $datos->payloadData = NULL;
        $datos->mensaje = "";

        try {

            $datos->payloadData = JWT::decode(
                                            $token,
                                            self::$secret_key,
                                            self::$encrypt
                                        )->usuario;
            $datos->exito = TRUE;

        } catch (Exception $e) { 

            $datos->mensaje = $e->getMessage();
        }

        return $datos;
    }
    

}