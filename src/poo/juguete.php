<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once "islimeable.php";

class Juguete implements ISlimeable{
    public int $precio;
    public int $id;
    public string $marca;
    public string $path_foto;

    function AgregarUno(Request $request, Response $response, array $args) : Response{

        
        $obj_rta = new stdClass();
        $obj_rta->exito = false;
        $obj_rta->mensaje = "No se pudo agregar a la base de datos";
        $obj_rta->status = 418;
        
        
        $arrayDeParametros = $request->getParsedBody();
        
        $obj = json_decode(($arrayDeParametros["juguete_json"]));
        
        $archivos = $request->getUploadedFiles();
        $nombreAnterior = $archivos['foto']->getClientFilename();

        $miJuguete = new Juguete();
        $miJuguete->marca = $obj->marca;
        $miJuguete->precio = $obj->precio;
        $miJuguete->path_foto = $nombreAnterior;

        $agregado = $miJuguete->Agregar();

        if($agregado)
        {

            $obj_rta->exito = true;
            $obj_rta->mensaje = "Agregado a la base de datos";
            $obj_rta->status = 200;

            $destino = __DIR__ . "/../fotos/";
            $extension = explode(".", $nombreAnterior);
            $extension = array_reverse($extension);
            $archivos['foto']->moveTo($destino .$miJuguete->marca . "." . $extension[0]);
        }

        $newResponse = $response->withStatus($obj_rta->status);
      
        $newResponse->getBody()->write(json_encode($obj_rta));
      
        return $newResponse->withHeader('Content-Type', 'application/json');

    }

    public function Agregar() : bool {
        
        try{
            $objetoPDO = new PDO("mysql:host=localhost;dbname=jugueteria_bd;charset=utf8", USUARIO, CLAVE);
            $consulta = $objetoPDO->prepare("INSERT INTO juguetes (marca, precio, path_foto)"
                                            . "VALUES(:marca, :precio, :path_foto)");
            
            
            $consulta->bindValue(':marca', $this->marca, PDO::PARAM_STR);
            $consulta->bindValue(':precio', $this->precio, PDO::PARAM_INT);
            $consulta->bindValue(':path_foto', $this->path_foto, PDO::PARAM_STR);
            $consulta->execute();

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
        $obj_rta->mensaje = null;
        $obj_rta->dato = [];
        $obj_rta->status = 424;

		$todosLosJuguetes = Juguete::traerTodosLosJuguetes();

        if(count($todosLosJuguetes) > 0)
        {
            $obj_rta->exito = true;
            $obj_rta->mensaje = "Datos obtenidos de la base de datos";
            $obj_rta->dato = $todosLosJuguetes;
            $obj_rta->status = 200;
        }
      
        $newResponse = $response->withStatus($obj_rta->status);
      
        $newResponse->getBody()->write(json_encode($obj_rta));
      
        return $newResponse->withHeader('Content-Type', 'application/json');
	}


    public static function traerTodosLosJuguetes() : array {
        try {
            
            $objetoPDO = new PDO("mysql:host=localhost;dbname=jugueteria_bd;charset=utf8", USUARIO, CLAVE);
    
            
            $consulta = $objetoPDO->prepare("
                SELECT id, marca, precio, path_foto
                FROM juguetes
            ");
            $consulta->execute();
    
            
            $resultados = $consulta->fetchAll(PDO::FETCH_ASSOC);
    
            
            return $resultados;

        } catch (PDOException $error) {
            echo "Error\n" . $error->getMessage();
            return []; 
        }
    }

    public function borrarUno(Request $request, Response $response, array $args): Response 
	{		
        $obj_rta = new stdClass();
        $obj_rta->exito = false;
        $obj_rta->mensaje = "";
        $obj_rta->status = 418;

     	$id = $args['id_juguete'];
        $eliminado = Juguete::Eliminar($id);
		 
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

		$newResponse = $response->withStatus($obj_rta->status);
      
        $newResponse->getBody()->write(json_encode($obj_rta));
      
        return $newResponse->withHeader('Content-Type', 'application/json');
    }

    public static function Eliminar(string $id) : bool {
        try{
            $objetoPDO = new PDO("mysql:host=localhost;dbname=jugueteria_bd;charset=utf8", USUARIO, CLAVE);
            $consulta = $objetoPDO->prepare("DELETE FROM juguetes WHERE id = :id");
            
            $consulta->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $consulta->execute();

            
            if ($consulta->rowCount() > 0) {
                return true; 
            } else {
                return false; 
            }

        } catch(PDOException $error) {
            echo "Error\n" . $error->getMessage();
            return false;
        }
    }

    public function ModificarUno(Request $request, Response $response, array $args) : Response{

        
        $obj_rta = new stdClass();
        $obj_rta->exito = false;
        $obj_rta->mensaje = "No se pudo modificar el juguete en la base de datos";
        $obj_rta->status = 418;
        
        
        $arrayDeParametros = $request->getParsedBody();
        
        $obj = json_decode(($arrayDeParametros["juguete"]));
        
        $archivos = $request->getUploadedFiles();
        $nombreAnterior = $archivos['foto']->getClientFilename();

        $extension = explode(".", $nombreAnterior);
        $extension = array_reverse($extension);
        
        $miJuguete = new Juguete();

        $miJuguete->id = (int)$obj->id;
        $miJuguete->marca = $obj->marca;
        $miJuguete->precio = $obj->precio;

        $nombreModificado = $miJuguete->marca ."_modificacion" ."." . $extension[0];
        $miJuguete->path_foto = $nombreModificado;

        $modificado = $miJuguete->Modificar();

        if($modificado)
        {

            $obj_rta->exito = true;
            $obj_rta->mensaje = " Juguete modificado en la base de datos";
            $obj_rta->status = 200;

            $destino = __DIR__ . "/../fotos/";
            $archivos['foto']->moveTo($destino . $nombreModificado);
        }

        $newResponse = $response->withStatus($obj_rta->status);
      
        $newResponse->getBody()->write(json_encode($obj_rta));
      
        return $newResponse->withHeader('Content-Type', 'application/json');

    }

    public function Modificar() : bool{
        try{
            $objetoPDO = new PDO("mysql:host=localhost;dbname=jugueteria_bd;charset=utf8", USUARIO, CLAVE);
            $consulta = $objetoPDO->prepare("UPDATE juguetes SET marca = :marca, precio = :precio, path_foto = :path_foto 
                                            WHERE id = :id");
            
            $consulta->bindValue(':id', $this->id, PDO::PARAM_INT);
            $consulta->bindValue(':marca', $this->marca, PDO::PARAM_STR);
            $consulta->bindValue(':precio', $this->precio, PDO::PARAM_INT);
            $consulta->bindValue(':path_foto', $this->path_foto, PDO::PARAM_STR);
            $consulta->execute();

            
            if ($consulta->rowCount() > 0) {
                return true; 
            } else {
                return false; 
            }

        } catch(PDOException $error) {
            echo "Error\n" . $error->getMessage();
            return false;
        }
    }
}