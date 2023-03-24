<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/server/App.php';
require_once __DIR__ . '/../src/server/RestfulApp.php';

use Lin\AppPhp\Server\App;
use Lin\AppPhp\Server\RestfulApp;
use \Lin\AppPhp\Authorization\AuthorizationInterface;

// 創建 RestfulApp 的子類別
class User extends RestfulApp
{
    public function OnGet()
    {
        $ResponseBody = $this->Psr17Factory->createStream(json_encode(['name' => 'John Doe']));
        return $this->Psr17Factory->createResponse(200)->withBody($ResponseBody);
    }
}

// 實作 AuthorizationInterface
class Authorization implements AuthorizationInterface
{
    public function Authorize($Token)
    {
        return true;
    }
}

// 處理請求
$App = new User();
$App->WithAuthorization(new Authorization())->HandleRequest(App::CreateServerRequest());
$App->SendResponse();
exit();
