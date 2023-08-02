<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/server/App.php';
require_once __DIR__ . '/../src/server/SinglePageApp.php';

use Lin\AppPhp\Server\App;
use Lin\AppPhp\Server\SinglePageApp;
use \Lin\AppPhp\Authorization\AuthorizationInterface;

// 實作 AuthorizationInterface
class Authorization implements AuthorizationInterface
{
    public function Authorize($Token, $RequestScopes = [])
    {
        return true;
    }
}

// 創建 SinglePageApp 的實例
$App = new SinglePageApp(file_get_contents('single_page_example.html'));
$App->WithAuthorization(new Authorization());
$App->AddPostAction('check-login', function ($ServerRequest) {
    return App::NoContentResponse();
})->AddPostAction('greet', function ($ServerRequest) {
    return App::JsonResponse(['message' => 'Hello World!']);
})->AddPostAction('message', function ($ServerRequest) use ($App) {
    if (!$App->AuthorizeRequest()) return App::UnauthorizedResponse();
    $Message = $ServerRequest->getParsedBody()['message'];
    return App::JsonResponse(['message' => $Message]);
});

// 處理請求
$App->HandleRequest(App::CreateServerRequest());
$App->SendResponse();
exit();
