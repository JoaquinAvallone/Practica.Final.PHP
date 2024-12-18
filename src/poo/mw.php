<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as ResponseMW;

require_once "autentificadora.php";

class Mw 
{

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

}