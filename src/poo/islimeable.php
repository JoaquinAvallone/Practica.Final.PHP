<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


interface ISlimeable{
    function TraerTodos(Request $request, Response $response, array $args) : Response;
    function AgregarUno(Request $request, Response $response, array $args) : Response;
}