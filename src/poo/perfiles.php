<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Avallone\Joaquin\AccesoDatos;

require_once "autentificadora.php";
require_once "accesoDatos.php";

class Perfil 
{
    public int $id;
    public string $descripcion;
    public int $estado;


    function AgregarUno(Request $request, Response $response, array $args) : Response{

        
        $obj_rta = new stdClass();
        $obj_rta->exito = false;
        $obj_rta->mensaje = "No se pudo agregar a la base de datos";
        $obj_rta->status = 418;
        
        
        $arrayDeParametros = $request->getParsedBody();
        
        
        $obj = json_decode(($arrayDeParametros["perfil"]));
        
        $miPerfil = new Perfil();
        $miPerfil->descripcion = $obj->descripcion;
        $miPerfil->estado = $obj->estado;
        

        $agregado = $miPerfil->Agregar();

        if($agregado)
        {
            $obj_rta->exito = true;
            $obj_rta->mensaje = "Agregado a la base de datos";
            $obj_rta->status = 200;

        }

        $newResponse = $response->withStatus($obj_rta->status);
      
        $newResponse->getBody()->write(json_encode($obj_rta));
      
        return $newResponse->withHeader('Content-Type', 'application/json');

    }

    public function Agregar() : bool {
        
        try{
            $pdo = AccesoDatos::dameUnObjetoAcceso()->retornarConsulta("INSERT INTO perfiles (descripcion, estado)"
                                            . "VALUES(:descripcion, :estado)");          
            
            $pdo->bindValue(':descripcion', $this->descripcion, PDO::PARAM_STR);
            $pdo->bindValue(':estado', $this->estado, PDO::PARAM_INT);
            $pdo->execute();

            return true;

        } catch(PDOException $error) {
            echo "Error\n" . $error->getMessage();
            return false;
        }
    }


    public function TraerTodos(Request $request, Response $response, array $args): Response 
	{
        $obj_rta = new stdClass();
        $obj_rta->exito = false;
        $obj_rta->mensaje = "No se pudo obtener los datos de la base de datos";
        $obj_rta->dato = [];
        $obj_rta->status = 424;

		$todosLosPerfiles = Perfil::traerTodosLosPerfiles();

        if(count($todosLosPerfiles) > 0)
        {
            $obj_rta->exito = true;
            $obj_rta->mensaje = "Datos obtenidos de la base de datos";
            $obj_rta->dato = $todosLosPerfiles;
            $obj_rta->status = 200;
        }
      
        $newResponse = $response->withStatus($obj_rta->status);
      
        $newResponse->getBody()->write(json_encode($obj_rta));
      
        return $newResponse->withHeader('Content-Type', 'application/json');
	}


    public static function traerTodosLosPerfiles() : array {
        try {
            $pdo = AccesoDatos::dameUnObjetoAcceso()->retornarConsulta("SELECT * FROM perfiles");
            $pdo->execute();
            $resultados = $pdo->fetchAll(PDO::FETCH_ASSOC);    
            
            return $resultados;

        } catch (PDOException $error) {
            echo "Error\n" . $error->getMessage();
            return []; 
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


    public function borrarUno(Request $request, Response $response, array $args): Response 
	{		
        $authResponse = $this->verificarPorAuth($request, $response, $args);
        $authBody = json_decode((string)$authResponse->getBody());

        if (!$authBody->exito) 
        {
            return $authResponse;
        }

        $obj_rta = new stdClass();
        $obj_rta->exito = false;
        $obj_rta->mensaje = "";
        $obj_rta->status = 418;

        $body = $request->getBody();
        $datos = json_decode($body, true);
        if (!isset($datos['id_perfil'])) 
        {
            $obj_rta->mensaje = "El parámetro id_perfil es requerido.";
        } 
        else 
        {
            $id = $datos['id_perfil'];
            $eliminado = Perfil::Eliminar($id);

            if($eliminado)
            {
                $obj_rta->exito = true;
                $obj_rta->mensaje = "ID $id eliminado de la base de datos";
                $obj_rta->status = 200;
            }
            else
            {
                $obj_rta->mensaje = "EL ID $id no se pudo eliminar de la base de datos";
            }
        }		 

		$newResponse = $response->withStatus($obj_rta->status);
      
        $newResponse->getBody()->write(json_encode($obj_rta));
      
        return $newResponse->withHeader('Content-Type', 'application/json');
    }

    public static function Eliminar(string $id) : bool {
        try{
            $pdo = AccesoDatos::dameUnObjetoAcceso()->retornarConsulta("DELETE FROM perfiles WHERE id = :id");             
            $pdo->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $pdo->execute();

            
            if ($pdo->rowCount() > 0) {
                return true; 
            } else {
                return false; 
            }

        } catch(PDOException $error) {
            echo "Error\n" . $error->getMessage();
            return false;
        }
    }


    public function ModificarUno(Request $request, Response $response, array $args): Response 
    {
        $obj_rta = new stdClass();
        $obj_rta->exito = false;
        $obj_rta->mensaje = "No se pudo modificar el perfil en la base de datos";
        $obj_rta->status = 418;

        try 
        {
            $datos = json_decode($request->getBody()->getContents(), true);

            if (!isset($datos['id_perfil'], $datos['perfil']['descripcion'], $datos['perfil']['estado'])) 
            {
                throw new Exception("Datos insuficientes o mal formateados");
            }

            $id = $datos['id_perfil'];
            $descripcion = $datos['perfil']['descripcion'];
            $estado = $datos['perfil']['estado'];

            $miPerfil = new Perfil();
            $miPerfil->id = $id;
            $miPerfil->descripcion = $descripcion;
            $miPerfil->estado = $estado;

            $modificado = $miPerfil->Modificar();

            if ($modificado) 
            {
                $obj_rta->exito = true;
                $obj_rta->mensaje = "Perfil modificado en la base de datos";
                $obj_rta->status = 200;
            }

        } 
        catch (Exception $e) 
        {
            $obj_rta->mensaje = $e->getMessage();
        }

        $newResponse = $response->withStatus($obj_rta->status);
        $newResponse->getBody()->write(json_encode($obj_rta));
        return $newResponse->withHeader('Content-Type', 'application/json');
    }


    public function Modificar() : bool
    {
        try
        {
            $pdo = AccesoDatos::dameUnObjetoAcceso()->retornarConsulta("UPDATE perfiles SET descripcion = :descripcion, estado = :estado WHERE id = :id");
            $pdo->bindValue(':id', $this->id, PDO::PARAM_INT);
            $pdo->bindValue(':descripcion', $this->descripcion, PDO::PARAM_STR);
            $pdo->bindValue(':estado', $this->estado, PDO::PARAM_INT);
            $pdo->execute();

            
            if ($pdo->rowCount() > 0) {
                return true; 
            } else {
                return false; 
            }

        } 
        catch(PDOException $error) 
        {
            echo "Error\n" . $error->getMessage();
            return false;
        }
    }
}