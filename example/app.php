<?php

namespace Lin\AppPhp\Tests\Server;

require_once __DIR__ . '/../vendor/autoload.php';

error_reporting(E_ALL);

use Lin\AppPhp\Server\App;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


class SampleApp extends App
{
    public function HandleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->ServerRequest = $request;
        if (!$this->AuthorizeRequest(['read', 'write'])) {
            return App::UnauthorizedResponse();
        }
        return App::JsonResponse(['message' => 'success']);
    }
}

$request = App::CreateServerRequest();
$app = new SampleApp();
$response = $app->handle($request);
$app->SendResponse();
