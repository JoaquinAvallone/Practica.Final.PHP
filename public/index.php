<?php

use Slim\Factory\AppFactory;
use Avallone\Joaquin\Auto;
use Avallone\Joaquin\Usuario;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/poo/usuario.php';
require __DIR__ . '/../src/poo/Auto.php';

$app = AppFactory::create();

$app->post('/usuarios', Usuario::class . ':AgregarUno');
$app->get('/', Usuario::class . ':traerTodos');
$app->post('/', Auto::class . ':AgregarUno');

$app->get('/autos', Auto::class . ':TraerTodos');
$app->post('/login', Usuario::class . ':Crear');
$app->get('/login', Usuario::class . ':verificarPorAuth');

$app->run();

/*
Ir al archivo httpd.conf (desde el panel de control XAMPP) y
cambiar de AllowOverride none a AllowOverride all
<Directory />
    AllowOverride all
    Require all denied
</Directory>

Ir al archivo httpd-vhosts.conf ubicado en
xampp\apache\conf\extra y configurar el virtual host:
<VirtualHost *:80>
    ServerAdmin administrator@mail.com
    DocumentRoot "C:\xampp\htdocs\ClasesProgra\Avallone.Joaquin\public\index.php"
    ServerName slim4_parciales
    ErrorLog "logs/slim"
    CustomLog "logs/slim" common
</VirtualHost>

Ir al archivo hosts ubicado en C:\Windows\System32\drivers\etc y registrar el virtual host:
127.0.0.1 slim4_parciales
*/