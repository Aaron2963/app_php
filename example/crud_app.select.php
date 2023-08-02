<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/server/App.php';
require_once __DIR__ . '/../src/server/CRUDApp.php';

use Lin\AppPhp\Server\App;
use Lin\AppPhp\Server\CrudApp;

class User extends CrudApp
{
    public function OnRead()
    {
        return App::JsonResponse(['name' => 'John Doe']);
    }
}

// 處理請求
$App = new User();
$App->HandleRequest(App::CreateServerRequest());
$App->SendResponse();
exit();