<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Factory\AppFactory;


require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/poo/usuario.php';
require __DIR__ . '/../src/poo/juguete.php';
require __DIR__ . '/../src/poo/mw.php';


use Firebase\JWT\JWT;
use \Slim\Routing\RouteCollectorProxy;


$app = AppFactory::create();



$app->get('/', \Usuario::class . ':traerTodos');

$app->post('/', \Juguete::class . ':AgregarUno')->add(\Mw::class . ":VerificarToken");

$app->get('/juguetes', \Juguete::class . ':traerTodos');

$app->post('/login', \Usuario::class . ':Crear')->add(\Mw::class . ":VerificarUsuario")->add(\Mw::class . "::VerificarCampos"); 

$app->get('/login', \Usuario::class . ':verificarPorAuth');

$app->group('/toys', function (RouteCollectorProxy $grupo){

    $grupo->delete('/{id_juguete}', \Juguete::class . ':borrarUno');

    $grupo->post('/modificar', \Juguete::class . ':ModificarUno');

})->add(\Mw::class . ":VerificarToken");


$app->group('/tablas', function (RouteCollectorProxy $grupo){

    $grupo->get('/usuarios', function (Request $request, Response $response, array $args): Response {

        return $response;
    
    })->add(\Mw::class . "::MostrarTablaUsuarios");

    $grupo->post('/usuarios', function (Request $request, Response $response, array $args): Response {

        return $response;
    
    })->add(\Mw::class . "::MostrarTablaPropietario")->add(\Mw::class . ":VerificarToken");

    $grupo->get('/juguetes', function (Request $request, Response $response, array $args): Response {

        return $response;
    
    })->add(\Mw::class . ":MostrarTablaJuguetes");

});

$app->post('/usuarios', \Usuario::class . ':AgregarUno')->add(\Mw::class . ":Verificartoken")
                                                        ->add(\Mw::class . "::VerificarCorreo")
                                                        ->add(\Mw::class . "::VerificarCampos");



$app->run();