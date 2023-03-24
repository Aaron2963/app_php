# Lin/App-PHP

## Installation

```bash
$ composer require aaron-lin/app-php
```

## Usage

There are three types of supported PHP applications: RESTful App, CRUD App and Single Page App.


### RESTful App

To build RESTful App, extend the `Lin\AppPhp\Server\RestfulApp` class, and override the `OnGet|OnPost|OnPut|OnDelete|OnPatch` methods.

```php
require __DIR__ . '/vendor/autoload.php';

use Lin\AppPhp\Server\App;
use Lin\AppPhp\Server\RestfulApp;

// create a child class of RestfulApp, and override method `OnGet|OnPost|OnPut|OnDelete|OnPatch`
class MyRestfulApp extends RestfulApp
{
    public function OnGet($request, $response)
    {
        $ResponseBody = $this->Psr17Factory->createStream(json_encode(['message' => 'Hello World']));
        return $this->Psr17Factory->createResponse(200)->withBody($ResponseBody);
    }
}

// handle request and send response
$App = new MyRestfulApp();
$App->HandleRequest(App::CreateServerRequest());
$App->SendResponse();
exit();
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
        $ResponseBody = $this->Psr17Factory->createStream(json_encode(['name' => 'John Doe']));
        return $this->Psr17Factory->createResponse(200)->withBody($ResponseBody);
    }
}

// 處理請求
$App = new User();
$App->HandleRequest(App::CreateServerRequest());
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
$App->SendResponse();
exit();
```
