# Lin/App-PHP

## Installation

```bash
$ composer require aaron-lin/app-php
```

## Usage

There are three types of supported PHP applications: **RESTful App**, **CRUD App** and **Single Page App**.


### RESTful App

To build RESTful App, extend the `Lin\AppPhp\Server\RestfulApp` class, and override the `OnGet|OnPost|OnPut|OnDelete|OnPatch` methods.

For detailed example, see [example/restful_app.php](example/restful_app.php).


### CRUD App

To build CRUD App which only accept `POST` method and receive command from resource path, extend the `Lin\AppPhp\Server\CrudApp` class, and override the `OnCreate|OnRead|OnUpdate|OnDelete` methods.

For detailed example, see [example/crud_app.html](example/crud_app.html) and [example/crud_app.select.php](example/crud_app.select.php).


### Single Page App

To build single page app, instanciate the `Lin\AppPhp\Server\SinglePageApp` class, and pass the web page html code as string to the constructor, and call `AddPostAction` method to add actions for receiving post requests.

For detailed example, see [example/single_page_example.html](example/single_page_example.html) and [example/single_page_app.php](example/single_page_app.php).


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
    public function Authorize($Token, $RequestScopes = []): bool
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
$PublicKeyPath = '/var/www/pubkeys/oauth_pub';
$App = new User();
$App->WithAuthorization(new Authorization($PublicKeyPath))->HandleRequest(App::CreateServerRequest());
$App->SendResponse();
exit;
```
