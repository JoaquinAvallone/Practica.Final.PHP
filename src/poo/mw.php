<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as ResponseMW;

require_once 'imiddleware.php';
require_once 'usuario.php';

class Mw implements IMiddleware {

    public static function VerificarCampos(Request $request, RequestHandler $handler) : ResponseMW
    {
        $contenidoAPI = '';

        
        $arrayDeParametros = $request->getParsedBody(); 
        
        if (isset($arrayDeParametros["user"]) || isset($arrayDeParametros["usuario"])) 
        {
            
            $jsonNombre = isset($arrayDeParametros["user"]) ? "user" : "usuario";
            $obj = json_decode($arrayDeParametros[$jsonNombre], true);

            $correoInvalido = !isset($obj['correo']) || trim($obj['correo']) === '';
            $claveInvalida = !isset($obj['clave']) || trim($obj['clave']) === '';

            if ($correoInvalido || $claveInvalida) 
            {
                $faltanCampos = "";

                if ($correoInvalido) {
                    $faltanCampos .= "Falta atributo correo!!!";
                }
                if ($claveInvalida) {
                    $faltanCampos .= "Falta atributo clave!!!";
                }
                
                $mensaje = ["mensaje" => $faltanCampos];
                $response = new ResponseMW();
                $response = $response->withHeader('Content-Type', 'application/json')
                                    ->withStatus(409); 

                $response->getBody()->write(json_encode($mensaje));

                return $response;

            }
            else 
            {
                $response = $handler->handle($request);
                
                
                $contenidoAPI = (string) $response->getBody();
            }

        }
        else 
        {
            
            $mensaje = ["mensaje" => "ERROR. Falta parametro user"];
            $response = new ResponseMW();
            $response = $response->withHeader('Content-Type', 'application/json')
                                ->withStatus(409); 

            $response->getBody()->write(json_encode($mensaje));

            return $response;
        }


        
        $response = new ResponseMW();
        $response = $response->withHeader('Content-Type', 'application/json')
                                ->withStatus(200); 

        $response->getBody()->write($contenidoAPI);

        return $response;
    }

    public function VerificarUsuario(Request $request, RequestHandler $handler) : ResponseMW
    {
        $contenidoAPI = '';

        
        $arrayDeParametros = $request->getParsedBody(); 
        
        
        $obj = json_decode(($arrayDeParametros["user"]));

        
        $encontrado = Usuario::ExisteUsuario($obj);

        if($encontrado)
        {
            $response = $handler->handle($request);
            
            
            $contenidoAPI = (string) $response->getBody();
        }
        else
        {
            
            $mensaje = ["mensaje" => "ERROR. Correo o clave incorrecta"];
            $response = new ResponseMW();
            $response = $response->withHeader('Content-Type', 'application/json')
                                ->withStatus(403); 

            $response->getBody()->write(json_encode($mensaje));

            return $response;
        }
        
        $response = new ResponseMW();
        $response = $response->withHeader('Content-Type', 'application/json')
                                ->withStatus(200); 
        $response->getBody()->write("$contenidoAPI");

        return $response;
    }

    public function VerificarToken(Request $request, RequestHandler $handler) : ResponseMW
    {
        $contenidoAPI = '';
        $tokenBearer = $request->getHeader("Authorization")[0];
        
        $token = substr($tokenBearer, 7); 
        $obj_rta = Autentificadora::verificarJWT($token);

        if($obj_rta->exito)
        {
            $response = $handler->handle($request);
            
            
            $contenidoAPI = (string) $response->getBody();
        }
        else
        {
            
            $mensaje = ["mensaje" => $obj_rta->mensaje];
            $response = new ResponseMW();
            $response = $response->withHeader('Content-Type', 'application/json')
                                ->withStatus(403);

            $response->getBody()->write(json_encode($mensaje));

            return $response;
        }

        
        $response = new ResponseMW();
        $response = $response->withHeader('Content-Type', 'application/json')
                                ->withStatus(200);
        $response->getBody()->write("$contenidoAPI");

        return $response;
    }

    public static function MostrarTablaUsuarios(Request $request, RequestHandler $handler) : ResponseMW
    {
        
        $usuarios = Usuario::traerTodosLosUsuarios();
        
        
        $tablaHTML = "<table border='1' cellpadding='5' cellspacing='0'>";
        $tablaHTML .= "<thead><tr><th>Id</th><th>Correo</th><th>Nombre</th><th>Apellido</th><th>Foto</th><th>Perfil</th></tr></thead>";
        $tablaHTML .= "<tbody>";

        
        foreach ($usuarios as $usuario) {
            
            $tablaHTML .= "<tr>";
            $tablaHTML .= "<td>{$usuario['id']}</td>";
            $tablaHTML .= "<td>{$usuario['correo']}</td>";
            $tablaHTML .= "<td>{$usuario['nombre']}</td>";
            $tablaHTML .= "<td>{$usuario['apellido']}</td>";
            
            
            $foto = !empty($usuario['foto']) ? "<img src='{$usuario['foto']}' alt='Foto' width='50' height='50'>" : "No disponible";
            $tablaHTML .= "<td>{$foto}</td>";

            
            $tablaHTML .= "<td>{$usuario['perfil']}</td>";
            $tablaHTML .= "</tr>";
        }

        
        $tablaHTML .= "</tbody></table>";

        
        $response = new ResponseMW();
        $response = $response->withHeader('Content-Type', 'text/html') 
                            ->withStatus(200);
        $response->getBody()->write($tablaHTML);

        return $response;

    }

    public static function MostrarTablaPropietario(Request $request, RequestHandler $handler) : ResponseMW
    {
        $contenidoAPI = '';
        $formatoSalida = '';
        $tokenBearer = $request->getHeader("Authorization")[0];
        
        $token = substr($tokenBearer, 7); 

        $obj_rta = Autentificadora::obtenerPayLoad($token);

        if($obj_rta->exito)
        {
            
            $perfil = $obj_rta->payloadData->perfil; 
            if($perfil === 'propietario')
            {
                $contenidoAPI = Mw::GenerarTablaPropietario();
                $formatoSalida = 'text/html';
            }
            else
            {
                $obj_rta = new stdClass();
                $obj_rta->exito = false;
                $obj_rta->mensaje = 'No es propierario';
                $obj_rta->status = 403;
                $contenidoAPI = json_encode($obj_rta);
                $formatoSalida = 'application/json';
            }
        }
        else
        {
            
            $mensaje = ["mensaje" => $obj_rta->mensaje];
            $response = new ResponseMW();
            $response = $response->withHeader('Content-Type', 'application/json')
                                ->withStatus(403);

            $response->getBody()->write(json_encode($mensaje));

            return $response;
        }

        
        $response = new ResponseMW();
        $response = $response->withHeader('Content-Type', $formatoSalida)
                                ->withStatus(200);
        $response->getBody()->write("$contenidoAPI");

        return $response;
    }

    public static function GenerarTablaPropietario() : string {
        
        $usuarios = Usuario::traerTodosLosUsuarios();
        
        $tablaHTML = "<table border='1' cellpadding='5' cellspacing='0'>";
        $tablaHTML .= "<thead><tr><th>Correo</th><th>Nombre</th><th>Apellido</th></thead>";
        $tablaHTML .= "<tbody>";

        
        foreach ($usuarios as $usuario) 
        {
            $tablaHTML .= "<tr>";
            $tablaHTML .= "<td>{$usuario['correo']}</td>";
            $tablaHTML .= "<td>{$usuario['nombre']}</td>";
            $tablaHTML .= "<td>{$usuario['apellido']}</td>";
            
            $tablaHTML .= "</tr>";
        }

        $tablaHTML .= "</tbody></table>";
        return $tablaHTML;
    }

    public function MostrarTablaJuguetes(Request $request, RequestHandler $handler) : ResponseMW
    {
        
        $juguetes = Juguete::traerTodosLosJuguetes();
        
        
        $tablaHTML = "<table border='1' cellpadding='5' cellspacing='0'>";
        $tablaHTML .= "<thead><tr><th>Id</th><th>marca</th><th>precio</th><th>path_foto</th></tr></thead>";
        $tablaHTML .= "<tbody>";

        
        foreach ($juguetes as $juguete) {
            
            if(($juguete['id'] % 2) !== 0 )
            {
                $tablaHTML .= "<tr>";
                $tablaHTML .= "<td>{$juguete['id']}</td>";
                $tablaHTML .= "<td>{$juguete['marca']}</td>";
                $tablaHTML .= "<td>{$juguete['precio']}</td>";
                
                $foto = !empty($juguete['path_foto']) ? "<img src='{$juguete['path_foto']}' alt='Foto' width='50' height='50'>" : "No disponible";
                $tablaHTML .= "<td>{$foto}</td>";
                $tablaHTML .= "</tr>";
            }
        }

        
        $tablaHTML .= "</tbody></table>";

        
        $response = new ResponseMW();
        $response = $response->withHeader('Content-Type', 'text/html') 
                            ->withStatus(200);
        $response->getBody()->write($tablaHTML);

        return $response;

    }

    public static function VerificarCorreo(Request $request, RequestHandler $handler) : ResponseMW
    {
        $obj_rta = new stdClass();
        $obj_rta->exito = false;
        $obj_rta->mensaje = "No se pudo agregar a la base de datos";
        $obj_rta->status = 418;
        
        
        $arrayDeParametros = $request->getParsedBody();
        
        
        $obj = json_decode(($arrayDeParametros["usuario"]));
        $existeCorreo = Usuario::ExisteCorreo($obj->correo);

        if(!$existeCorreo)
        {
            $response = $handler->handle($request);
            
            
            $contenidoAPI = (string) $response->getBody();
        }
        else
        {
            
            $mensaje = ["mensaje" => "ERROR. El correo ya existe en la base de datos"];
            $response = new ResponseMW();
            $response = $response->withHeader('Content-Type', 'application/json')
                                ->withStatus(403); 

            $response->getBody()->write(json_encode($mensaje));

            return $response;
        }
    
        $response = new ResponseMW();
        $response = $response->withHeader('Content-Type', 'application/json')
                                ->withStatus(200); 
        $response->getBody()->write("$contenidoAPI");

        return $response;
    }
    


}