<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Factory\AppFactory;


require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/poo/usuario.php';
require __DIR__ . '/../src/poo/mw.php';
require __DIR__ . '/../src/poo/Perfiles.php';


use Firebase\JWT\JWT;
use \Slim\Routing\RouteCollectorProxy;


$app = AppFactory::create();

$app->post('/usuario', \Usuario::class . ':AgregarUno');
$app->get('/', \Usuario::class . ':traerTodos');
$app->post('/', \Perfil::class . ':AgregarUno');

$app->get('/perfil', \Perfil::class . ':TraerTodos');
$app->post('/login', \Usuario::class . ':Crear');
$app->get('/login', \Usuario::class . ':verificarPorAuth');

$app->group('/perfiles', function (RouteCollectorProxy $grupo){

    $grupo->delete('', \Perfil::class . ':borrarUno');
    $grupo->put('', \Perfil::class . ':ModificarUno');

})->add(\Mw::class . ":VerificarToken");

$app->group('/usuarios', function (RouteCollectorProxy $grupo){

    $grupo->delete('', \Usuario::class . ':borrarUno');
    $grupo->post('', \Usuario::class . ':ModificarUno');

})->add(\Mw::class . ":VerificarToken");

$app->run();