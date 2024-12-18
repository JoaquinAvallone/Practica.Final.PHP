<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Avallone\Joaquin\AccesoDatos;

require_once "autentificadora.php";
require_once "accesoDatos.php";

class Usuario 
{
    public string $correo;
    public int $clave;
    public string $nombre;
    public string $apellido;
    public int $id_perfil;
    public string $foto;

    public function __construct()
    {
        $this->correo = "";
        $this->clave = 0;
        $this->nombre = "";
        $this->apellido = "";
        $this->id_perfil = 0;
        $this->foto = "";
    }


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
            $pdo = AccesoDatos::dameUnObjetoAcceso()->retornarConsulta("SELECT * FROM usuarios");
            $pdo->execute();
            $resultados = $pdo->fetchAll(PDO::FETCH_ASSOC);    
            
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
                
                $usuarioObj = new stdClass();
                $usuarioObj->id = $resultado['id'];
                $usuarioObj->correo = $resultado['correo'];
                $usuarioObj->nombre = $resultado['nombre'];
                $usuarioObj->apellido = $resultado['apellido'];
                $usuarioObj->foto = $resultado['foto'];
                $usuarioObj->id_perfil = $resultado['id_perfil'];
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
    
            $pdo = AccesoDatos::dameUnObjetoAcceso()->retornarConsulta(" SELECT COUNT(*) AS cantidad
                FROM usuarios
                WHERE correo = :correo AND clave = :clave");   
            
            $pdo->bindValue(':correo', $correo, PDO::PARAM_STR);
            $pdo->bindValue(':clave', $clave, PDO::PARAM_STR);
            $pdo->execute();    
            
            $fila = $pdo->fetch(PDO::FETCH_ASSOC);    
            
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
        else
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
        $miUsuario->id_perfil = $obj->id_perfil;
        
        $nombreFoto = $miUsuario->id_perfil . "_" . $miUsuario->apellido .".". $extension[0];
        $miUsuario->foto = $nombreFoto;

        $agregado = $miUsuario->Agregar();

        if($agregado)
        {
            //$token = Autentificadora::crearJWT($miUsuario, 120);
            //$obj_rta->jwt = $token;
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
            $pdo = AccesoDatos::dameUnObjetoAcceso()->retornarConsulta("INSERT INTO usuarios (correo, clave, nombre, apellido, foto, id_perfil)"
                                            . "VALUES(:correo, :clave, :nombre, :apellido, :foto, :id_perfil)");          
            
            $pdo->bindValue(':correo', $this->correo, PDO::PARAM_STR);
            $pdo->bindValue(':clave', $this->clave, PDO::PARAM_INT);
            $pdo->bindValue(':nombre', $this->nombre, PDO::PARAM_STR);
            $pdo->bindValue(':apellido', $this->apellido, PDO::PARAM_STR);
            $pdo->bindValue(':id_perfil', $this->id_perfil, PDO::PARAM_INT);
            $pdo->bindValue(':foto', $this->foto, PDO::PARAM_STR);
            $pdo->execute();

            return true;

        } catch(PDOException $error) {
            echo "Error\n" . $error->getMessage();
            return false;
        }
    }

    public static function ExisteCorreo(string $correo): bool 
    {
        try {
        
            
            $pdo = AccesoDatos::dameUnObjetoAcceso()->retornarConsulta("SELECT COUNT(*) AS cantidad
                FROM usuarios
                WHERE correo = :correo"); 
    
            $pdo->bindValue(':correo', $correo, PDO::PARAM_STR);
            $pdo->execute();   
            
            $fila = $pdo->fetch(PDO::FETCH_ASSOC);    
            
            return $fila['cantidad'] > 0;

        } catch (PDOException $error) {
            echo "Error\n" . $error->getMessage();
            return false; 
        }
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
        if (!isset($datos['id_usuario'])) 
        {
            $obj_rta->mensaje = "El parámetro id_usuario es requerido.";
        } 
        else 
        {
            $id = $datos['id_usuario'];
            $eliminado = Usuario::Eliminar($id);

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
            $pdo = AccesoDatos::dameUnObjetoAcceso()->retornarConsulta("DELETE FROM usuarios WHERE id = :id");             
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
        $authResponse = $this->verificarPorAuth($request, $response, $args);
        $authBody = json_decode((string)$authResponse->getBody());

        if (!$authBody->exito) 
        {
            return $authResponse;
        }

        $obj_rta = new stdClass();
        $obj_rta->exito = false;
        $obj_rta->mensaje = "No se pudo modificar el usuario en la base de datos";
        $obj_rta->status = 418;

        try 
        {
            
            $arrayDeParametros = $request->getParsedBody();
            
            $obj = json_decode(($arrayDeParametros["usuario"]));
            
            $archivos = $request->getUploadedFiles();
            $nombreAnterior = $archivos['foto']->getClientFilename();

            $extension = explode(".", $nombreAnterior);
            $extension = array_reverse($extension);


            if (!isset($obj->id, $obj->correo, $obj->clave, $obj->nombre, $obj->apellido, $obj->id_perfil, $archivos['foto'])) 
            {
                throw new Exception("Datos insuficientes o mal formateados");
            }

            $id = (int)$obj->id;
            $correo = $obj->correo;
            $clave = $obj->clave;
            $nombre = $obj->nombre;
            $apellido = $obj->apellido;
            $id_perfil = $obj->id_perfil; 
            $nombreModificado = $id . "_" . $apellido ."_modificacion" ."." . $extension[0];
            $foto = $nombreModificado;        

            $miUsuario = new Usuario();
            $miUsuario->correo = $correo;
            $miUsuario->clave = $clave;
            $miUsuario->nombre = $nombre;
            $miUsuario->apellido = $apellido;
            $miUsuario->id_perfil = $id_perfil;
            $miUsuario->foto = $foto;
            $destino = __DIR__ . "/../fotos/";


            $modificado = $miUsuario->Modificar($id);

            if ($modificado) 
            {
                $obj_rta->exito = true;
                $obj_rta->mensaje = "Perfil modificado en la base de datos";
                $obj_rta->status = 200;
                $archivos['foto']->moveTo($destino . $nombreModificado);
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


    public function Modificar($id) : bool
    {
        try
        {
            $pdo = AccesoDatos::dameUnObjetoAcceso()->retornarConsulta("UPDATE usuarios SET correo = :correo, clave = :clave, nombre = :nombre,
            apellido = :apellido, id_perfil = :id_perfil, foto = :foto WHERE id = :id");

            $pdo->bindValue(':correo', $this->correo, PDO::PARAM_STR);
            $pdo->bindValue(':clave', $this->clave, PDO::PARAM_INT);
            $pdo->bindValue(':nombre', $this->nombre, PDO::PARAM_STR);
            $pdo->bindValue(':apellido', $this->apellido, PDO::PARAM_STR);
            $pdo->bindValue(':id_perfil', $this->id_perfil, PDO::PARAM_INT);
            $pdo->bindValue(':foto', $this->foto, PDO::PARAM_STR);
            $pdo->bindValue(':id', $id, PDO::PARAM_STR);
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