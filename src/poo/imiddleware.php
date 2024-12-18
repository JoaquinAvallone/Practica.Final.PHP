<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as ResponseMW;

interface IMiddleware{

    static function VerificarCampos(Request $request, RequestHandler $handler) : ResponseMW;
    function VerificarUsuario(Request $request, RequestHandler $handler) : ResponseMW;
    function Verificartoken(Request $request, RequestHandler $handler) : ResponseMW;
    static function MostrarTablaUsuarios(Request $request, RequestHandler $handler) : ResponseMW;
    static function MostrarTablaPropietario(Request $request, RequestHandler $handler) : ResponseMW;
    function MostrarTablaJuguetes(Request $request, RequestHandler $handler) : ResponseMW;
    static function VerificarCorreo(Request $request, RequestHandler $handler) : ResponseMW;
    

}
?>