<?php

namespace Lin\AppPhp\Server;

use Lin\AppPhp\Authorization\AuthorizationInterface;

class Router
{
    /**
     * @var array $Routes Nested array of routes, end node would be an array of \Lin\AppPhp\Server\RestfulApp
     */
    protected array $Routes = [];

    /**
     * @var array $PathParams The path params, which is the value of the variable in the path, eg. /api/v1/user/:id, the value of :id will be stored in this array
     */
    protected array $PathParams = [];

    /**
     * @var AuthorizationInterface $Authorization The meachanism to authorize the request, set by `Router::WithAuthorization` method
     */
    protected AuthorizationInterface $Authorization;

    /**
     * @var string $Root The root path of the router, which will be appended to the path of every route
     */
    protected string $Root = '';

    /**
     * Create a new Router instance
     * 
     * @param string $Root The root path of the router, which will be appended to the path of every route
     * 
     */
    public function __construct(string $Root = '')
    {
        $this->Root = $Root;
    }

    /**
     * Run router, handle request
     * 
     * @param string $RequestURI The requesting URI, which is the path part of the URL, eg. /api/v1/user
     * 
     * @return void
     * 
     */
    public function Run(string $RequestURI): void
    {
        $Path = str_replace('//', '/', $RequestURI);
        $Creator = $this->ResolveRoute($this->Routes, $Path);
        if (!isset($Creator[0])) {
            http_response_code(404);
            echo 'Not Found';
            return;
        }
        $Request = App::CreateServerRequest();
        $Request = $Request->withAttribute('PathParams', $this->PathParams);
        $App = $Creator[0];
        if (!($App instanceof RestfulApp)) {
            http_response_code(500);
            return;
        }
        $App->WithAuthorization($this->Authorization);
        $App->HandleRequest($Request);
        $App->SendResponse();
    }


    /**
     * Append Authorization object to Router
     *
     * @param  AuthorizationInterface $Authorization
     * 
     * @return self
     * 
     */
    public function WithAuthorization(AuthorizationInterface $Authorization): self
    {
        $this->Authorization = $Authorization;
        return $this;
    }

    /**
     * ResolveRoute
     *
     * @param  array    $Routes Nested array of routes, every end node should be an array of \Lin\AppPhp\Server\RestfulApp
     * @param  string   $Path
     * 
     * @return array|null
     * 
     */
    protected function ResolveRoute(array $Routes, string $Path): ?array
    {
        $Path = explode('?', $Path)[0];
        $Parts = explode('/', $Path);
        $Route = &$Routes;
        foreach ($Parts as $Part) {
            if (!isset($Route[$Part])) {
                if (isset($Route['*'])) {
                    $Route = &$Route['*'];
                    $this->PathParams[] = $Part;
                    continue;
                } else {
                    return null;
                }
            }
            $Route = &$Route[$Part];
        }
        return $Route;
    }
    
    /**
     * Add route to Router, with path and \Lin\AppPhp\Server\RestfulApp instance
     *
     * @param  string       $Path   The path of the route, if the path contains `:` (eg. `/api/v1/user/:id`), it will be treated as a variable, and the value can be accessed in the App by `$this->ServerRequest->getAttribute('PathParams')`
     * @param  RestfulApp   $App    The instance of \Lin\AppPhp\Server\RestfulApp handle the request
     * 
     * @return void
     * 
     */
    public function AddRoute(string $Path, RestfulApp $App): void
    {
        $Path = $this->Root . '/' . $Path;
        $Path = str_replace('//', '/', $Path);
        $Parts = explode('/', $Path);
        $Route = &$this->Routes;
        foreach ($Parts as $Part) {
            if (str_starts_with($Part, ':')) {
                $Part = '*';
            }
            if (!isset($Route[$Part])) {
                $Route[$Part] = [];
            }
            $Route = &$Route[$Part];
        }
        if (!is_array($Route)) {
            $Route = [];
        }
        $Route[] = $App;
    }
}
