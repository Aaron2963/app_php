<?php

use Lin\AppPhp\Authorization\AuthorizationInterface;
use Lin\AppPhp\Server\RestfulApp;
use Lin\AppPhp\Server\Router;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class RouterTest extends TestCase
{
    private RestfulApp $app;
    private AuthorizationInterface $auth;

    protected function setUp(): void
    {
        $this->app = new class() extends RestfulApp {
            private function Response(int $status): ResponseInterface
            {
                return $this->Response = $this->Psr17Factory->createResponse($status);
            }
            public function OnPost(): ResponseInterface
            {
                return $this->Response(201);
            }
            public function OnGet(): ResponseInterface
            {
                return $this->Response(200);
            }
            public function OnPut(): ResponseInterface
            {
                return $this->Response(204);
            }
            public function OnDelete(): ResponseInterface
            {
                return $this->Response(205);
            }
        };
        $this->auth = new class() implements AuthorizationInterface {
            public function Authorize($Token, $Scopes = []): bool
            {
                return true;
            }
        };
    }

    /**
     * @covers \Lin\AppPhp\Server\Router::AddRoute
     * @covers \Lin\AppPhp\Server\Router::Run
     * @covers \Lin\AppPhp\Server\Router::ResolveRoute
     * @covers \Lin\AppPhp\Server\App::CreateServerRequest
     * @covers \Lin\AppPhp\Server\App::JsonResponse
     * @covers \Lin\AppPhp\Server\App::ParseContentByType
     * @covers \Lin\AppPhp\Server\App::__construct
     * @covers \Lin\AppPhp\Server\RestfulApp::HandleRequest
     * @covers \Lin\AppPhp\Server\Router::__construct
     * @covers \Lin\AppPhp\Server\Router::WithAuthorization
     * @covers \Lin\AppPhp\Server\App::WithAuthorization
     */
    public function testAddRouteAndRun()
    {
        $router = new Router('/api/v1');
        $router->AddRoute('/user', $this->app);
        $router->AddRoute('/user/:id', $this->app);
        $res = $router->Run('/api/v1/user', true);
        $this->assertInstanceOf(ResponseInterface::class, $res);
        $this->assertEquals(200, $res->getStatusCode());
        $res = $router->Run('/api/v2/user', true);
        $this->assertInstanceOf(ResponseInterface::class, $res);
        $this->assertEquals(404, $res->getStatusCode());
        $res = $router->Run('/api/v1/user/1', true);
        $this->assertInstanceOf(ResponseInterface::class, $res);
        $this->assertEquals(200, $res->getStatusCode());
        $router->WithAuthorization($this->auth);
        $res = $router->Run('/api/v1/user', true);
        $this->assertInstanceOf(ResponseInterface::class, $res);
        $this->assertEquals(200, $res->getStatusCode());
    }
}
