<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/server/App.php';
require_once __DIR__ . '/../src/server/RestfulApp.php';
require_once __DIR__ . '/../src/auth/AuthorizationInterface.php';

use Lin\AppPhp\Server\App;
use Lin\AppPhp\Server\RestfulApp;
use Lin\AppPhp\Authorization\AuthorizationInterface;

// 實作 AuthorizationInterface
class Authorization implements AuthorizationInterface
{
    public function Authorize($Token, $RequestScopes = [])
    {
        return true;
    }
}

// 創建 RestfulApp 的子類別
class User extends RestfulApp
{
    public function OnGet()
    {
        // 檢查權限: 呼叫 AuthorizationInterface::AuthorizeRequest
        if (!$this->AuthorizeRequest(['user.read'])) {
            return App::UnauthorizedResponse();
        }
        // 回應
        return App::JsonResponse(['name' => 'John Doe']);
    }

    public function OnPost()
    {
        // 檢查權限: 呼叫 AuthorizationInterface::AuthorizeRequest
        if (!$this->AuthorizeRequest(['user.create'])) {
            return App::UnauthorizedResponse();
        }
        // 取得請求資料
        $Data = $this->GetServerRequest()->getParsedBody();
        if (!isset($Data['name'])) {
            return App::JsonResponse(['message' => 'name is required'], 400);
        }
        // 儲存資料...
        // 回應
        return App::NoContentResponse();
    }
}

// 處理請求
$App = new User();
$App->WithAuthorization(new Authorization())->HandleRequest(App::CreateServerRequest());
$App->AddHeaders(['Access-Control-Allow-Origin' => '*']);
$App->SendResponse();
exit();
