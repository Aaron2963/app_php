<?php

use Lin\AppPhp\Server\CrudApp;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;

class CrudAppTest extends TestCase
{
    private CrudApp $app;
    private CrudApp $app_ori;
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->app = new class() extends CrudApp {
            private function Response(int $status): ResponseInterface
            {
                return $this->Response = $this->Psr17Factory->createResponse($status);
            }
            public function OnCreate(): ResponseInterface
            {
                return $this->Response(201);
            }
            public function OnRead(): ResponseInterface
            {
                return $this->Response(200);
            }
            public function OnUpdate(): ResponseInterface
            {
                return $this->Response(204);
            }
            public function OnDelete(): ResponseInterface
            {
                return $this->Response(205);
            }
        };
        $this->factory = new Psr17Factory();
        $this->app_ori = new CrudApp();
    }

    private function createServerRequest(string $method): ServerRequestInterface
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = "/api/v1/user.$method.php";
        $headers = ['Authorization' => 'Bearer abc123'];
        $body = $this->factory->createStream('{"name":"John Doe"}');
        $headers['Content-Type'] = 'application/json; charset=utf-8';
        $headers['Content-Length'] = $body->getSize();
        return new ServerRequest($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $headers, $body);
    }

    private function testHandleRequest(string $method, int $expected): void
    {
        $req = $this->createServerRequest($method);
        $res = $this->app->HandleRequest($req);
        $res_ori = $this->app_ori->HandleRequest($req);
        $this->assertInstanceOf(ServerRequestInterface::class, $this->app->GetServerRequest());
        $this->assertEquals('John Doe', $this->app->GetServerRequest()->getParsedBody()['name']);
        $this->assertEquals('Bearer abc123', $this->app->GetServerRequest()->getHeaderLine('Authorization'));
        $this->assertEquals('application/json; charset=utf-8', $this->app->GetServerRequest()->getHeaderLine('Content-Type'));
        $this->assertInstanceOf(ResponseInterface::class, $res);
        $this->assertEquals($expected, $res->getStatusCode());
        $this->assertEquals(404, $res_ori->getStatusCode());
    }

    /**
     * @covers \Lin\AppPhp\Server\CrudApp::OnRead
     * @covers \Lin\AppPhp\Server\CrudApp::HandleRequest
     * @covers \Lin\AppPhp\Server\App::__construct
     * @covers \Lin\AppPhp\Server\App::GetServerRequest
     * @covers \Lin\AppPhp\Server\App::ParsePHPInput
     * @covers \Lin\AppPhp\Server\App::ParseJsonInput
     * @covers \Lin\AppPhp\Server\App::ParseContentByType
     */
    public function testHandleRequestRead(): void
    {
        $this->testHandleRequest('select', 200);
    }

    /**
     * @covers \Lin\AppPhp\Server\CrudApp::OnCreate
     * @covers \Lin\AppPhp\Server\CrudApp::HandleRequest
     * @covers \Lin\AppPhp\Server\App::__construct
     * @covers \Lin\AppPhp\Server\App::GetServerRequest
     * @covers \Lin\AppPhp\Server\App::ParsePHPInput
     * @covers \Lin\AppPhp\Server\App::ParseJsonInput
     * @covers \Lin\AppPhp\Server\App::ParseContentByType
     */
    public function testHandleRequestCreate(): void
    {
        $this->testHandleRequest('create', 201);
    }

    /**
     * @covers \Lin\AppPhp\Server\CrudApp::OnUpdate
     * @covers \Lin\AppPhp\Server\CrudApp::HandleRequest
     * @covers \Lin\AppPhp\Server\App::__construct
     * @covers \Lin\AppPhp\Server\App::GetServerRequest
     * @covers \Lin\AppPhp\Server\App::ParsePHPInput
     * @covers \Lin\AppPhp\Server\App::ParseJsonInput
     * @covers \Lin\AppPhp\Server\App::ParseContentByType
     */
    public function testHandleRequestUpdate(): void
    {
        $this->testHandleRequest('update', 204);
    }

    /**
     * @covers \Lin\AppPhp\Server\CrudApp::OnDelete
     * @covers \Lin\AppPhp\Server\CrudApp::HandleRequest
     * @covers \Lin\AppPhp\Server\App::__construct
     * @covers \Lin\AppPhp\Server\App::GetServerRequest
     * @covers \Lin\AppPhp\Server\App::ParsePHPInput
     * @covers \Lin\AppPhp\Server\App::ParseJsonInput
     * @covers \Lin\AppPhp\Server\App::ParseContentByType
     */
    public function testHandleRequestDelete(): void
    {
        $this->testHandleRequest('delete', 205);
    }

    /**
     * @covers \Lin\AppPhp\Server\CrudApp::HandleRequest
     * @covers \Lin\AppPhp\Server\App::__construct
     * @covers \Lin\AppPhp\Server\App::GetServerRequest
     * @covers \Lin\AppPhp\Server\App::ParsePHPInput
     * @covers \Lin\AppPhp\Server\App::ParseJsonInput
     * @covers \Lin\AppPhp\Server\App::ParseContentByType
     */
    public function testHandleRequestMissingMethod(): void
    {
        $this->testHandleRequest('missing', 404);
    }

    /**
     * @covers \Lin\AppPhp\Server\CrudApp::HandleRequest
     * @covers \Lin\AppPhp\Server\App::__construct
     * @covers \Lin\AppPhp\Server\App::GetServerRequest
     */
    public function testHandleRequestWrongMethod(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "/api/v1/user.php";;
        $req = new ServerRequest($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
        $res = $this->app->HandleRequest($req);
        $this->assertInstanceOf(ServerRequestInterface::class, $this->app->GetServerRequest());
        $this->assertInstanceOf(ResponseInterface::class, $res);
        $this->assertEquals(405, $res->getStatusCode());
    }
}
