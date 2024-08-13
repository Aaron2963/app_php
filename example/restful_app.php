<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Lin\AppPhp\Server\App;
use Lin\AppPhp\Server\RestfulApp;
use Lin\AppPhp\Authorization\AuthorizationInterface;
use Psr\Http\Message\ResponseInterface;

// 實作 AuthorizationInterface
class Authorization implements AuthorizationInterface
{
    public function Authorize($Token, $RequestScopes = []): bool
    {
        return true;
    }
}

// 創建 RestfulApp 的子類別
class User extends RestfulApp
{
    public function OnGet(): ResponseInterface
    {
        // 檢查權限: 呼叫 AuthorizationInterface::AuthorizeRequest
        if (!$this->AuthorizeRequest(['user.read'])) {
            return App::UnauthorizedResponse();
        }
        // 回應
        return App::JsonResponse(['name' => 'John Doe']);
    }

    public function OnPost(): ResponseInterface
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
$Request = App::CreateServerRequest(); // 從全域變數 $_SERVER 及 php://input 創建 ServerRequestInterface
$App = new User();
$App->WithAuthorization(new Authorization())->HandleRequest($Request);
$App->AddHeaders(['Access-Control-Allow-Origin' => '*']);
$App->SendResponse();
exit();
