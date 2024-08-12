<?php

use PHPUnit\Framework\TestCase;
use Lin\AppPhp\Server\RestfulApp;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;

class RestfulAppTest extends TestCase
{
    private RestfulApp $app;
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->app = new RestfulApp();
        $this->factory = new Psr17Factory();
    }

    private function createServerRequest(string $method): ServerRequestInterface
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = '/api/v1/user?name=John';
        $headers = ['Authorization' => 'Bearer abc123'];
        if ($method !== 'GET') {
            $body = $this->factory->createStream('{"name":"John Doe"}');
            $headers['Content-Type'] = 'application/json; charset=utf-8';
            $headers['Content-Length'] = $body->getSize();
            return new ServerRequest($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $headers, $body);
        } else {
            return new ServerRequest($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $headers);
        }
    }

    private function testHandleRequestWithBody(string $method): void
    {
        $req = $this->createServerRequest($method);
        $res = $this->app->HandleRequest($req);
        $this->assertInstanceOf(ServerRequestInterface::class, $req);
        $this->assertEquals('John Doe', $this->app->GetServerRequest()->getParsedBody()['name']);
        $this->assertEquals('Bearer abc123', $this->app->GetServerRequest()->getHeaderLine('Authorization'));
        $this->assertEquals('application/json; charset=utf-8', $this->app->GetServerRequest()->getHeaderLine('Content-Type'));
        $this->assertInstanceOf(ResponseInterface::class, $res);
        $this->assertEquals(405, $res->getStatusCode());
    }
    
    /**
     * @covers \Lin\AppPhp\Server\RestfulApp::HandleRequest
     * @covers \Lin\AppPhp\Server\RestfulApp::OnGet
     * @covers \Lin\AppPhp\Server\App::__construct
     * @covers \Lin\AppPhp\Server\App::GetServerRequest
     */
    public function testHadleRequestGetMethod()
    {
        $req = $this->createServerRequest('GET');
        $res = $this->app->HandleRequest($req);
        $this->assertInstanceOf(ServerRequestInterface::class, $req);
        $this->assertEquals('John', $this->app->GetServerRequest()->getQueryParams()['name']);
        $this->assertEquals('Bearer abc123', $this->app->GetServerRequest()->getHeaderLine('Authorization'));
        $this->assertInstanceOf(ResponseInterface::class, $res);
        $this->assertEquals(405, $res->getStatusCode());
    }

    /**
     * @covers \Lin\AppPhp\Server\RestfulApp::HandleRequest
     * @covers \Lin\AppPhp\Server\RestfulApp::OnPost
     * @covers \Lin\AppPhp\Server\App::__construct
     * @covers \Lin\AppPhp\Server\App::GetServerRequest
     * @covers \Lin\AppPhp\Server\App::ParseContentByType
     * @covers \Lin\AppPhp\Server\App::ParseJsonInput
     * @covers \Lin\AppPhp\Server\App::ParsePHPInput
     */
    public function testHadleRequestPostMethod()
    {
        $this->testHandleRequestWithBody('POST');
    }

    /**
     * @covers \Lin\AppPhp\Server\RestfulApp::HandleRequest
     * @covers \Lin\AppPhp\Server\RestfulApp::OnPut
     * @covers \Lin\AppPhp\Server\App::__construct
     * @covers \Lin\AppPhp\Server\App::GetServerRequest
     * @covers \Lin\AppPhp\Server\App::ParseContentByType
     * @covers \Lin\AppPhp\Server\App::ParseJsonInput
     * @covers \Lin\AppPhp\Server\App::ParsePHPInput
     */
    public function testHadleRequestPutMethod()
    {
        $this->testHandleRequestWithBody('PUT');
    }

    /**
     * @covers \Lin\AppPhp\Server\RestfulApp::HandleRequest
     * @covers \Lin\AppPhp\Server\RestfulApp::OnDelete
     * @covers \Lin\AppPhp\Server\App::__construct
     * @covers \Lin\AppPhp\Server\App::GetServerRequest
     * @covers \Lin\AppPhp\Server\App::ParseContentByType
     * @covers \Lin\AppPhp\Server\App::ParseJsonInput
     * @covers \Lin\AppPhp\Server\App::ParsePHPInput
     */
    public function testHadleRequestDeleteMethod()
    {
        $this->testHandleRequestWithBody('DELETE');
    }

    /**
     * @covers \Lin\AppPhp\Server\RestfulApp::HandleRequest
     * @covers \Lin\AppPhp\Server\RestfulApp::OnPatch
     * @covers \Lin\AppPhp\Server\App::__construct
     * @covers \Lin\AppPhp\Server\App::GetServerRequest
     * @covers \Lin\AppPhp\Server\App::ParseContentByType
     * @covers \Lin\AppPhp\Server\App::ParseJsonInput
     * @covers \Lin\AppPhp\Server\App::ParsePHPInput
     */
    public function testHadleRequestPatchMethod()
    {
        $this->testHandleRequestWithBody('PATCH');
    }

    /**
     * @covers \Lin\AppPhp\Server\RestfulApp::HandleRequest
     * @covers \Lin\AppPhp\Server\RestfulApp::OnPatch
     * @covers \Lin\AppPhp\Server\App::__construct
     * @covers \Lin\AppPhp\Server\App::GetServerRequest
     * @covers \Lin\AppPhp\Server\App::ParseContentByType
     * @covers \Lin\AppPhp\Server\App::ParseJsonInput
     * @covers \Lin\AppPhp\Server\App::ParsePHPInput
     */
    public function testHadleRequestOtherMethod()
    {
        $req = $this->createServerRequest('OTHER');
        $res = $this->app->HandleRequest($req);
        $this->assertInstanceOf(ServerRequestInterface::class, $req);
        $this->assertInstanceOf(ResponseInterface::class, $res);
        $this->assertEquals(405, $res->getStatusCode());
    }
}