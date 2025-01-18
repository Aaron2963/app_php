<?php

namespace Lin\AppPhp\Server;

use Lin\AppPhp\Authorization\AuthorizationInterface;
use Psr\Http\Message\ResponseInterface;
use Exception;

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
     * @var ?AuthorizationInterface $Authorization The meachanism to authorize the request, set by `Router::WithAuthorization` method
     */
    protected ?AuthorizationInterface $Authorization = null;

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
    public function Run(string $RequestURI, bool $Return = false): ?ResponseInterface
    {
        $Path = str_replace('//', '/', $RequestURI);
        $Creator = $this->ResolveRoute($this->Routes, $Path);
        $Response = App::JsonResponse(['message' => 'Not Found'], 404);
        try {
            if (!isset($Creator[0])) {
                throw new Exception('Not Found', 404);
            }
            $Request = App::CreateServerRequest();
            $Request = $Request->withAttribute('PathParams', $this->PathParams);
            $App = $Creator[0];
            if (!($App instanceof App)) {
                throw new Exception('Invalid request handler, must be instance of \Lin\AppPhp\Server\App', 500);
            }
            if ($this->Authorization !== null) {
                $App->WithAuthorization($this->Authorization);
            }
            $Response = $App->HandleRequest($Request);
        } catch (Exception $e) {
            $Code = $e->getCode();
            if (!is_int($Code) || $Code < 100 || $Code > 599) {
                $Code = 400;
            }
            $Response = App::JsonResponse(['message' => $e->getMessage()], $Code);
        }
        if ($Return) {
            return $Response;
        }
        App::Send($Response);
        return null;
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
     * @param  string   $Path   The path of the route, if the path contains `:` (eg. `/api/v1/user/:id`), it will be treated as a variable, and the value can be accessed in the App by `$this->ServerRequest->getAttribute('PathParams')`
     * @param  App      $App    The instance of \Lin\AppPhp\Server\App handle the request
     * 
     * @return void
     * 
     */
    public function AddRoute(string $Path, App $App): void
    {
        $Path = $this->Root . '/' . $Path;
        $Path = str_replace('//', '/', $Path);
        $Parts = explode('/', $Path);
        $Route = &$this->Routes;
        foreach ($Parts as $Part) {
            if (strpos($Part, ':') === 0) {
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
