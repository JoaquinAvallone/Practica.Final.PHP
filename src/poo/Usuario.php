<?php
namespace Avallone\Joaquin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Avallone\Joaquin\AccesoDatos;
use Avallone\Joaquin\Autentificadora;
use PDO;
use PDOException;

require_once "autentificadora.php";
require_once "accesoDatos.php";

class Usuario 
{
    public string $correo;
    public int $clave;
    public string $nombre;
    public string $apellido;
    public string $perfil;
    public string $foto;

    public function __construct()
    {
        $this->correo = "";
        $this->clave = 0;
        $this->nombre = "";
        $this->apellido = "";
        $this->perfil = "";
        $this->foto = "";
    }


    function AgregarUno(Request $request, Response $response, array $args) : Response{

        
        $obj_rta = new \stdClass();
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
        
        $nombreFoto = $miUsuario->correo .".". $extension[0];
        $miUsuario->foto = $nombreFoto;

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
            $pdo = AccesoDatos::dameUnObjetoAcceso()->retornarConsulta("INSERT INTO usuarios (correo, clave, nombre, apellido, foto, perfil)"
                                            . "VALUES(:correo, :clave, :nombre, :apellido, :foto, :perfil)");          
            
            $pdo->bindValue(':correo', $this->correo, PDO::PARAM_STR);
            $pdo->bindValue(':clave', $this->clave, PDO::PARAM_INT);
            $pdo->bindValue(':nombre', $this->nombre, PDO::PARAM_STR);
            $pdo->bindValue(':apellido', $this->apellido, PDO::PARAM_STR);
            $pdo->bindValue(':perfil', $this->perfil, PDO::PARAM_STR);
            $pdo->bindValue(':foto', $this->foto, PDO::PARAM_STR);
            $pdo->execute();

            return true;

        } catch(PDOException $error) {
            echo "Error\n" . $error->getMessage();
            return false;
        }
    }


    public function TraerTodos(Request $request, Response $response, array $args): Response 
	{
        $obj_rta = new \stdClass();
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
            $pdo = AccesoDatos::dameUnObjetoAcceso()->retornarConsulta("SELECT * FROM usuarios");
            $pdo->execute();
            $resultados = $pdo->fetchAll(\PDO::FETCH_ASSOC);    
            
            return $resultados;

        } catch (PDOException $error) {
            echo "Error\n" . $error->getMessage();
            return []; 
        }
    }

 
    public function Crear(Request $request, Response $response, array $args) : Response
    {
        $obj_rta = new \stdClass();
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
           $token = Autentificadora::crearJWT($usuarioDesdeBD, 445);
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

            $pdo = AccesoDatos::dameUnObjetoAcceso()->retornarConsulta("SELECT * FROM usuarios WHERE correo = :correo AND clave = :clave");
            $pdo->bindValue(':correo', $correo, PDO::PARAM_STR);
            $pdo->bindValue(':clave', $clave, PDO::PARAM_INT);
            $pdo->execute();
            $resultado = $pdo->fetch(PDO::FETCH_ASSOC);    

            if ($resultado) {
                
                $usuarioObj = new \stdClass();
                $usuarioObj->id = $resultado['id'];
                $usuarioObj->correo = $resultado['correo'];
                $usuarioObj->nombre = $resultado['nombre'];
                $usuarioObj->apellido = $resultado['apellido'];
                $usuarioObj->foto = $resultado['foto'];
                $usuarioObj->perfil = $resultado['perfil'];
                return $usuarioObj; 
            } else {
                return null; 
            }
        } catch (PDOException $error) {
            echo "Error\n" . $error->getMessage();
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
        else
        {
            $newResponse->getBody()->write(json_encode($obj_rta));
        }
    
        return $newResponse->withHeader('Content-Type', 'application/json');
    }



}