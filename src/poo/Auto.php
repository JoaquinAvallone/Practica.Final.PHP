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


class Auto
{
    public string $color;
    public string $marca;
    public int $precio;
    public string $modelo;
    public string $foto;

    public function __construct()
    {
        $this->color = "";
        $this->marca = "";
        $this->precio = 0;
        $this->modelo = "";
        $this->foto = "";
    }


    function AgregarUno(Request $request, Response $response, array $args) : Response{

        
        $obj_rta = new \stdClass();
        $obj_rta->exito = false;
        $obj_rta->mensaje = "No se pudo agregar a la base de datos";
        $obj_rta->status = 418;
        
        
        $arrayDeParametros = $request->getParsedBody();
        
        
        $obj = json_decode(($arrayDeParametros["auto"]));
        
        $archivos = $request->getUploadedFiles();
        $nombreAnterior = $archivos['foto']->getClientFilename();
        
        $destino = __DIR__ . "/../fotos/";
        $extension = explode(".", $nombreAnterior);
        $extension = array_reverse($extension);
        
        $miAuto = new Auto();
        $miAuto->color = $obj->color;
        $miAuto->marca = $obj->marca;
        $miAuto->precio = $obj->precio;
        $miAuto->modelo = $obj->modelo;
        
        $nombreFoto = $miAuto->marca .".". $extension[0];
        $miAuto->foto = $nombreFoto;

        $agregado = $miAuto->Agregar();

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
            $pdo = AccesoDatos::dameUnObjetoAcceso()->retornarConsulta("INSERT INTO autos (color, marca, precio, modelo, foto)"
                                            . "VALUES(:color, :marca, :precio, :modelo, :foto)");          
            
            $pdo->bindValue(':color', $this->color, PDO::PARAM_STR);
            $pdo->bindValue(':marca', $this->marca, PDO::PARAM_STR);
            $pdo->bindValue(':precio', $this->precio, PDO::PARAM_INT);
            $pdo->bindValue(':modelo', $this->modelo, PDO::PARAM_STR);
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

		$todosLosUsuarios = Auto::traerTodosLosAutos();

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


    public static function traerTodosLosAutos() : array {
        try {
            $pdo = AccesoDatos::dameUnObjetoAcceso()->retornarConsulta("SELECT * FROM autos");
            $pdo->execute();
            $resultados = $pdo->fetchAll(PDO::FETCH_ASSOC);    
            
            return $resultados;

        } catch (PDOException $error) {
            echo "Error\n" . $error->getMessage();
            return []; 
        }
    }
}