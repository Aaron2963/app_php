<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Lin\AppPhp\Server\Router;
use Lin\AppPhp\Server\RestfulApp;
use Lin\AppPhp\Server\App;
use Psr\Http\Message\ResponseInterface;

class User extends RestfulApp
{
    public function OnGet(): ResponseInterface
    {
        return App::JsonResponse(['name' => 'John Doe']);
    }

    public function OnPost(): ResponseInterface
    {
        $Data = $this->GetServerRequest()->getParsedBody();
        if (!isset($Data['name'])) {
            return App::JsonResponse(['message' => 'name is required'], 400);
        }
        return App::JsonResponse([
            'name' => $Data['name'],
            'message' => 'User created'
        ]);
    }
}

$router = new Router('/example/api/v1');
$router->AddRoute('/user', new User());
$router->Run($_SERVER['REQUEST_URI']);
