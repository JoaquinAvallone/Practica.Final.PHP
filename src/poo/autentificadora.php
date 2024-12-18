<?php
namespace Avallone\Joaquin;
use Firebase\JWT\JWT;

class Autentificadora
{
    private static string $secret_key = 'Avallone.Joaquin';
    private static array $encrypt = ['HS256'];
    
    public static function crearJWT(mixed $data, int $exp = (60*5)) : string
    {
        $ahora = time();

        $payload = array(
            'usuario' => $data,           
            'alumno' => "Joaquin",
            'dni_alumno' => "40796568", 
            'exp' => $ahora + $exp    
        );
            

        return JWT::encode($payload, self::$secret_key, "HS256");
    }
    

    public static function verificarJWT(string $token) : \stdClass
    {
        $datos = new \stdClass();
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
                $datos->mensaje = "Token valido!!!";

            }          
        } 
        catch (\Exception $e) 
        {
            $datos->exito = false;
            $datos->status = 403;
            $datos->mensaje = "Token no valido!!! --> " . $e->getMessage();

        }
    
        return $datos;
    }    

}