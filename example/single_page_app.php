<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Lin\AppPhp\Server\App;
use Lin\AppPhp\Server\SinglePageApp;
use \Lin\AppPhp\Authorization\AuthorizationInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// 實作 AuthorizationInterface
class Authorization implements AuthorizationInterface
{
    public function Authorize($Token, $RequestScopes = []): bool
    {
        return true;
    }
}

// 創建 SinglePageApp 的實例
$Html = file_get_contents(__DIR__ . '/single_page_example.html');
$App = new SinglePageApp($Html);
$App->AddPostAction('check-login', function (ServerRequestInterface $ServerRequest): ResponseInterface {
    return App::NoContentResponse();
})->AddPostAction('greet', function (ServerRequestInterface $ServerRequest): ResponseInterface {
    return App::JsonResponse(['message' => 'Hello World!']);
})->AddPostAction('message', function (ServerRequestInterface $ServerRequest) use ($App): ResponseInterface {
    if (!$App->AuthorizeRequest()) return App::UnauthorizedResponse();
    $Message = $ServerRequest->getParsedBody()['message'];
    return App::JsonResponse(['message' => $Message]);
});

// 處理請求
$App->WithAuthorization(new Authorization());
$App->HandleRequest(App::CreateServerRequest());
$App->SendResponse();
exit();
