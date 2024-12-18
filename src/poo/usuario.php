<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Firebase\JWT\JWT;

require_once "islimeable.php";
require_once "autentificadora.php";

define("USUARIO", "root");
define("CLAVE", "");

class Usuario implements ISlimeable{
    public string $correo;
    public int $clave;
    public string $nombre;
    public string $apellido;
    public string $perfil;
    public string $foto;


    public function TraerTodos(Request $request, Response $response, array $args): Response 
	{
        $obj_rta = new stdClass();
        $obj_rta->exito = false;
        $obj_rta->mensaje = "No se pudo obtener los datos de la base de datos";
        $obj_rta->dato = [];
        $obj_rta->status = 424;

		$todosLosUsuarios = Usuario::traerTodosLosUsuarios();

        if(count($todosLosUsuarios) > 0)
        {
            $obj_rta->exito = true;
            $obj_rta->mensaje = "Datos obtenidos de la base de datos";
            $obj_rta->dato = $todosLosUsuarios;
            $obj_rta->status = 200;
        }
      
        $newResponse = $response->withStatus($obj_rta->status);
      
        $newResponse->getBody()->write(json_encode($obj_rta));
      
        return $newResponse->withHeader('Content-Type', 'application/json');
	}


    public static function traerTodosLosUsuarios() : array {
        try {
            
            $objetoPDO = new PDO("mysql:host=localhost;dbname=jugueteria_bd;charset=utf8", USUARIO, CLAVE);
    
            
            $consulta = $objetoPDO->prepare("
                SELECT id, correo, clave, nombre, apellido, foto, perfil
                FROM usuarios
            ");
            $consulta->execute();
    
            
            $resultados = $consulta->fetchAll(PDO::FETCH_ASSOC);
    
            
            return $resultados;

        } catch (PDOException $error) {
            echo "Error\n" . $error->getMessage();
            return []; 
        }
    }
    
    
    public function Crear(Request $request, Response $response, array $args) : Response
    {
        $obj_rta = new stdClass();
        $obj_rta->exito = false;
        $obj_rta->jwt = null;
        $obj_rta->status = 403;
        
         
        $arrayDeParametros = $request->getParsedBody();
        
        $obj = json_decode(($arrayDeParametros["user"]));

        $correo = $obj->correo;
        $clave = $obj->clave;

        $usuarioDesdeBD = Usuario::TraerUno($correo, $clave);
        if($usuarioDesdeBD !== null)
        {
           $token = Autentificadora::crearJWT($usuarioDesdeBD, 120);
           $obj_rta->exito = true;
           $obj_rta->jwt = $token;
           $obj_rta->status = 200;
        }

        $newResponse = $response->withStatus($obj_rta->status);
        $newResponse->getBody()->write(json_encode($obj_rta));

        return $newResponse->withHeader('Content-Type', 'application/json');
    }


    public static function TraerUno(string $correo, int $clave) {
        try {
            $objetoPDO = new PDO("mysql:host=localhost;dbname=jugueteria_bd;charset=utf8", USUARIO, CLAVE);
            $consulta = $objetoPDO->prepare("
            SELECT id, correo, nombre, apellido, foto, perfil
            FROM usuarios
            WHERE correo = :correo AND clave = :clave
            ");
            $consulta->bindValue(':correo', $correo, PDO::PARAM_STR);
            $consulta->bindValue(':clave', $clave, PDO::PARAM_INT);
            $consulta->execute();
            
            
            $fila = $consulta->fetch(PDO::FETCH_ASSOC); 

            if ($fila) {
                
                $usuarioObj = new stdClass();
                $usuarioObj->id = $fila['id'];
                $usuarioObj->correo = $fila['correo'];
                $usuarioObj->nombre = $fila['nombre'];
                $usuarioObj->apellido = $fila['apellido'];
                $usuarioObj->foto = $fila['foto'];
                $usuarioObj->perfil = $fila['perfil'];
                return $usuarioObj; 
            } else {
                return null; 
            }
        } catch (PDOException $error) {
            echo "Error\n" . $error->getMessage();
        }
    }

    public static function ExisteUsuario(object $obj): bool {
        try {

            $correo = $obj->correo;
            $clave = $obj->clave;
    
            
            $objetoPDO = new PDO("mysql:host=localhost;dbname=jugueteria_bd;charset=utf8", USUARIO, CLAVE);
    
            
            $consulta = $objetoPDO->prepare("
                SELECT COUNT(*) AS cantidad
                FROM usuarios
                WHERE correo = :correo AND clave = :clave
            ");
            $consulta->bindValue(':correo', $correo, PDO::PARAM_STR);
            $consulta->bindValue(':clave', $clave, PDO::PARAM_STR);
            $consulta->execute();
    
            
            $fila = $consulta->fetch(PDO::FETCH_ASSOC);
    
            
            return $fila['cantidad'] > 0;
        } catch (PDOException $error) {
            echo "Error\n" . $error->getMessage();
            return false; 
        }
    }

    
    public function verificarPorAuth(Request $request, Response $response, array $args) : Response {

        $tokenBearer = $request->getHeader("Authorization")[0];
        
        $token = substr($tokenBearer, 7); 
        $obj_rta = Autentificadora::verificarJWT($token);

        $newResponse = $response->withStatus($obj_rta->status);

        if($obj_rta->exito === false)
        {
            $newResponse->getBody()->write(json_encode($obj_rta));
        }
    
        return $newResponse->withHeader('Content-Type', 'application/json');
    }

    
    function AgregarUno(Request $request, Response $response, array $args) : Response{

        
        $obj_rta = new stdClass();
        $obj_rta->exito = false;
        $obj_rta->mensaje = "No se pudo agregar a la base de datos";
        $obj_rta->status = 418;
        
        
        $arrayDeParametros = $request->getParsedBody();
        
        
        $obj = json_decode(($arrayDeParametros["usuario"]));
        
        $archivos = $request->getUploadedFiles();
        $nombreAnterior = $archivos['foto']->getClientFilename();
        
        $destino = __DIR__ . "/../fotos/";
        $extension = explode(".", $nombreAnterior);
        $extension = array_reverse($extension);
        
        $miUsuario = new Usuario();
        $miUsuario->correo = $obj->correo;
        $miUsuario->clave = $obj->clave;
        $miUsuario->nombre = $obj->nombre;
        $miUsuario->apellido = $obj->apellido;
        $miUsuario->perfil = $obj->perfil;
        
        $nombreFoto = $miUsuario->correo . "." . $extension[0];
        $miUsuario->foto = "/../fotos/" . $nombreFoto;

        $agregado = $miUsuario->Agregar();

        if($agregado)
        {

            $obj_rta->exito = true;
            $obj_rta->mensaje = "Agregado a la base de datos";
            $obj_rta->status = 200;

            $archivos['foto']->moveTo($destino . $nombreFoto);
        }

        $newResponse = $response->withStatus($obj_rta->status);
      
        $newResponse->getBody()->write(json_encode($obj_rta));
      
        return $newResponse->withHeader('Content-Type', 'application/json');

    }

    public function Agregar() : bool {
        
        try{
            $objetoPDO = new PDO("mysql:host=localhost;dbname=jugueteria_bd;charset=utf8", USUARIO, CLAVE);
            $consulta = $objetoPDO->prepare("INSERT INTO usuarios (correo, clave, nombre, apellido, foto, perfil)"
                                            . "VALUES(:correo, :clave, :nombre, :apellido, :foto, :perfil)");
            
            
            $consulta->bindValue(':correo', $this->correo, PDO::PARAM_STR);
            $consulta->bindValue(':clave', $this->clave, PDO::PARAM_INT);
            $consulta->bindValue(':nombre', $this->nombre, PDO::PARAM_STR);
            $consulta->bindValue(':apellido', $this->apellido, PDO::PARAM_STR);
            $consulta->bindValue(':perfil', $this->perfil, PDO::PARAM_STR);
            $consulta->bindValue(':foto', $this->foto, PDO::PARAM_STR);
            $consulta->execute();

            return true;

        } catch(PDOException $error) {
            echo "Error\n" . $error->getMessage();
            return false;
        }
    }

    public static function ExisteCorreo(string $correo): bool 
    {
        try {
        
            
            $objetoPDO = new PDO("mysql:host=localhost;dbname=jugueteria_bd;charset=utf8", USUARIO, CLAVE);
    
            
            $consulta = $objetoPDO->prepare("
                SELECT COUNT(*) AS cantidad
                FROM usuarios
                WHERE correo = :correo
            ");
            $consulta->bindValue(':correo', $correo, PDO::PARAM_STR);
            $consulta->execute();
    
            
            $fila = $consulta->fetch(PDO::FETCH_ASSOC);
    
            
            return $fila['cantidad'] > 0;
        } catch (PDOException $error) {
            echo "Error\n" . $error->getMessage();
            return false; 
        }
    }

}