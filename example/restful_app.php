<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/server/App.php';
require_once __DIR__ . '/../src/server/RestfulApp.php';

use App\Server\App;
use App\Server\RestfulApp;

// 創建 RestfulApp 的子類別
class User extends RestfulApp
{
    public function OnGet()
    {
        $ResponseBody = $this->Psr17Factory->createStream(json_encode(['name' => 'John Doe']));
        return $this->Psr17Factory->createResponse(200)->withBody($ResponseBody);
    }
}

// 處理請求
$App = new User();
$App->HandleRequest(App::CreateServerRequest());
$App->SendResponse();
exit();
