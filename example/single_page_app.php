<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/server/App.php';
require_once __DIR__ . '/../src/server/SinglePageApp.php';

use Aaron\SPA\Server\App;
use Aaron\SPA\Server\SinglePageApp;

// 創建 SinglePageApp 的實例
$App = new SinglePageApp(file_get_contents('example.html'));
$App->AddPostAction('check-login', function ($ServerRequest) {
    return App::NoContentResponse();
});
$App->AddPostAction('greet', function ($ServerRequest) {
    return App::JsonResponse(['message' => 'Hello World!']);
});
$App->AddPostAction('message', function ($ServerRequest) {
    $Message = $ServerRequest->getParsedBody()['message'];
    return App::JsonResponse(['message' => $Message]);
});

// 處理請求
$App->HandleRequest(App::CreateServerRequest());
$App->SendResponse();
exit();