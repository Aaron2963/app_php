# Lin/App-PHP

## Installation

```bash
$ composer require aaron-lin/app-php
```

## Usage

There are three types of supported PHP applications: **RESTful App**, **CRUD App** and **Single Page App**.


### RESTful App

To build RESTful App, extend the `Lin\AppPhp\Server\RestfulApp` class, and override the `OnGet|OnPost|OnPut|OnDelete|OnPatch` methods.

```php
require __DIR__ . '/vendor/autoload.php';

use Lin\AppPhp\Server\App;
use Lin\AppPhp\Server\RestfulApp;
use Lin\AppPhp\Authorization\AuthorizationInterface;

// 實作 AuthorizationInterface
class Authorization implements AuthorizationInterface
{
    public function Authorize($Token, $RequestScopes = [])
    {
        $AvailableScopes = ['user.read', 'user.create'];
        $AccessScopes = array_intersect($RequestScopes, $AvailableScopes);
        if (count($RequestScopes) > 0 && count($AccessScopes) === 0) {
            return false;
        }
        return true;
    }
}

// create a class extending RestfulApp, and override method `OnGet|OnPost|OnPut|OnDelete|OnPatch`
// unoverrided method will return `405 Method Not Allowed` response
class User extends RestfulApp
{
    public function OnGet()
    {
        // 檢查權限: 呼叫 AuthorizationInterface::AuthorizeRequest
        if (!$this->AuthorizeRequest(['user.read'])) {
            return App::UnauthorizedResponse();
        }
        // 回應
        return App::JsonResponse([
            [
                'id' => '1',
                'name' => 'John Doe'
            ],
            [
                'id' => '2',
                'name' => 'Jane Doe'
            ],
        ]);
    }

    public function OnPost()
    {
        // 檢查權限: 呼叫 App::AuthorizeRequest
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
exit;
```


### CRUD App

To build CRUD App which only accept `POST` method and receive command from resource path, extend the `Lin\AppPhp\Server\CrudApp` class, and override the `OnCreate|OnRead|OnUpdate|OnDelete` methods.

```php
require_once __DIR__ . '/../vendor/autoload.php';

use Lin\AppPhp\Server\App;
use Lin\AppPhp\Server\CrudApp;

class User extends CrudApp
{
    public function OnRead()
    {
        return App::JsonResponse([
            [
                'id' => '1',
                'name' => 'John Doe'
            ],
            [
                'id' => '2',
                'name' => 'Jane Doe'
            ],
        ]);
    }
}

// 處理請求
$App = new User();
$App->HandleRequest(App::CreateServerRequest());
$App->AddHeaders(['Access-Control-Allow-Origin' => '*']);
$App->SendResponse();
exit();
```


### Single Page App

To build single page app, instanciate the `Lin\AppPhp\Server\SinglePageApp` class, and pass the web page html code as string to the constructor, and call `AddPostAction` method to add actions for receiving post requests.

```php
require __DIR__ . '/vendor/autoload.php';

use Lin\AppPhp\Server\App;
use Lin\AppPhp\Server\SinglePageApp;

$App = new SinglePageApp(file_get_contents('index.html'));
$App->AddPostAction('message', function ($ServerRequest) {
    $Message = $ServerRequest->getParsedBody()['message'];
    return App::JsonResponse(['message' => $Message]);
});

$App->HandleRequest(App::CreateServerRequest());
$App->AddHeaders(['Access-Control-Allow-Origin' => '*']);
$App->SendResponse();
exit();
```


### Authorization

To implement authorization: 
1. create a class implementing `Lin\AppPhp\Authorization\AuthorizationInterface` interface, and implement the `Authorize($Token, $RequestScopes = [])` method
2. pass the instance to `WithAuthorization` method of `Lin\AppPhp\Server\App` class
3. call `AuthorizeRequest` method of `Lin\AppPhp\Server\App` class to check authorization

```php
require __DIR__ . '/vendor/autoload.php';


use Lin\AppPhp\Server\App;
use Lin\AppPhp\Server\RestfulApp;
use Lin\AppPhp\Authorization\AuthorizationInterface;

// 實作 AuthorizationInterface
class Authorization implements AuthorizationInterface
{
    public function Authorize($Token, $RequestScopes = [])
    {
        $AvailableScopes = ['user.read', 'user.create'];
        $AccessScopes = array_intersect($RequestScopes, $AvailableScopes);
        if (count($RequestScopes) > 0 && count($AccessScopes) === 0) {
            return false;
        }
        return true;
    }
}

class User extends RestfulApp
{
    public function OnGet()
    {
        // 檢查權限: 呼叫 App::AuthorizeRequest
        if (!$this->AuthorizeRequest(['user.read'])) {
            return App::UnauthorizedResponse();
        }
        // 回應
        return App::NoContentResponse();
    }
}

// 處理請求
$App = new User();
$App->WithAuthorization(new Authorization())->HandleRequest(App::CreateServerRequest());
$App->SendResponse();
exit;
```

For OAuth2 authorization with JWT, use `Lin\AppPhp\Authorization\OAuthAuthorization` class:
1. create a class extending `Lin\AppPhp\Authorization\OAuthAuthorization` class, and implement the `IsTokenRevoked($JTI)` method
2. pass the instance to `WithAuthorization` method of `Lin\AppPhp\Server\App` class
3. call `AuthorizeRequest` method of `Lin\AppPhp\Server\App` class to check authorization


```php
require __DIR__ . '/vendor/autoload.php';


use Lin\AppPhp\Server\App;
use Lin\AppPhp\Server\RestfulApp;
use Lin\AppPhp\Authorization\AuthorizationInterface;

// 實作 AuthorizationInterface
class Authorization extends OAuthAuthorization
{
    public function IsTokenRevoked($JTI)
    {
        // 檢查 token 是否被撤銷
        return false;
    }
}

class User extends RestfulApp
{
    public function OnGet()
    {
        // 檢查權限: 呼叫 App::AuthorizeRequest
        if (!$this->AuthorizeRequest(['user.read'])) {
            return App::UnauthorizedResponse();
        }
        // 回應
        return App::NoContentResponse();
    }
}

// 處理請求
$App = new User();
$App->WithAuthorization(new Authorization())->HandleRequest(App::CreateServerRequest());
$App->SendResponse();
exit;
```
