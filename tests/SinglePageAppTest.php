<?php

use PHPUnit\Framework\TestCase;
use Lin\AppPhp\Server\SinglePageApp;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Server\RequestHandlerInterface;

class SinglePageAppTest extends TestCase
{
    private SinglePageApp $app;
    private Psr17Factory $factory;
    private string $html = '<!doctype html><title>hello</title>';

    protected function setUp(): void
    {
        $factory = new Psr17Factory();
        $app = new SinglePageApp($this->html);
        $this->app = $app;
        $this->factory = $factory;
    }

    private function createServerRequest(string $action = ''): ServerRequestInterface
    {
        $_SERVER['REQUEST_METHOD'] = empty($action) ? 'GET' : 'POST';
        $_SERVER['REQUEST_URI'] = "/api/v1/user.php";
        $headers = ['Authorization' => 'Bearer abc123'];
        if (!empty($action)) {
            $body = $this->factory->createStream('{"name":"John Doe","action":"' . $action . '"}');
            $headers['Content-Type'] = 'application/json; charset=utf-8';
            $headers['Content-Length'] = $body->getSize();
            return new ServerRequest($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $headers, $body);
        }
        return new ServerRequest($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $headers);
    }

    /**
     * @covers \Lin\AppPhp\Server\SinglePageApp::__construct
     * @covers \Lin\AppPhp\Server\SinglePageApp::HandleRequest
     * @covers \Lin\AppPhp\Server\SinglePageApp::OnGet
     * @covers \Lin\AppPhp\Server\App::__construct
     * @covers \Lin\AppPhp\Server\App::GetServerRequest
     */
    public function testHandleRequestGetMethod()
    {
        $req = $this->createServerRequest();
        $res = $this->app->HandleRequest($req);
        $this->assertInstanceOf(ServerRequestInterface::class, $req);
        $this->assertEquals('Bearer abc123', $this->app->GetServerRequest()->getHeaderLine('Authorization'));
        $this->assertInstanceOf(ResponseInterface::class, $res);
        $this->assertEquals(200, $res->getStatusCode());
        $this->assertEquals($this->html, $res->getBody()->getContents());
    }

    /**
     * @covers \Lin\AppPhp\Server\SinglePageApp::__construct
     * @covers \Lin\AppPhp\Server\SinglePageApp::HandleRequest
     * @covers \Lin\AppPhp\Server\SinglePageApp::OnPost
     * @covers \Lin\AppPhp\Server\SinglePageApp::AddPostAction
     * @covers \Lin\AppPhp\Server\App::__construct
     * @covers \Lin\AppPhp\Server\App::GetServerRequest
     * @covers \Lin\AppPhp\Server\App::ParsePHPInput
     * @covers \Lin\AppPhp\Server\App::ParseJsonInput
     * @covers \Lin\AppPhp\Server\App::ParseContentByType
     */
    public function testAddPostAction()
    {
        $req = $this->createServerRequest('hello');
        $app = new SinglePageApp($this->html);
        $app->AddPostAction('hello', function (ServerRequestInterface $request): ResponseInterface {
            return (new Psr17Factory())->createResponse(200)->withBody((new Psr17Factory())->createStream('{"hello":"world"}'));
        });
        $res = $app->HandleRequest($req);
        $this->assertInstanceOf(ServerRequestInterface::class, $req);
        $this->assertEquals('Bearer abc123', $app->GetServerRequest()->getHeaderLine('Authorization'));
        $this->assertInstanceOf(ResponseInterface::class, $res);
        $this->assertEquals(200, $res->getStatusCode());
        $this->assertEquals('{"hello":"world"}', $res->getBody()->getContents());
    }

    /**
     * @covers \Lin\AppPhp\Server\SinglePageApp::__construct
     * @covers \Lin\AppPhp\Server\SinglePageApp::HandleRequest
     * @covers \Lin\AppPhp\Server\SinglePageApp::OnPost
     * @covers \Lin\AppPhp\Server\SinglePageApp::AddPostActionHandler
     * @covers \Lin\AppPhp\Server\App::__construct
     * @covers \Lin\AppPhp\Server\App::GetServerRequest
     * @covers \Lin\AppPhp\Server\App::ParsePHPInput
     * @covers \Lin\AppPhp\Server\App::ParseJsonInput
     * @covers \Lin\AppPhp\Server\App::ParseContentByType
     */
    public function testAddPostActionHandler()
    {
        $req = $this->createServerRequest('echo');
        $app = new SinglePageApp($this->html);
        $handler = new class() implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $factory = new Psr17Factory();
                $content = $request->getParsedBody()['name'];
                $body = $factory->createStream('{"name":"' . $content . '","action":"echo"}');
                $res = $factory->createResponse(200)
                    ->withBody($body)
                    ->withHeader('Content-Type', 'application/json; charset=utf-8');
                return $res;
            }
        };
        $app->AddPostActionHandler('echo', $handler);
        $res = $app->HandleRequest($req);
        $this->assertInstanceOf(ServerRequestInterface::class, $req);
        $this->assertEquals('Bearer abc123', $app->GetServerRequest()->getHeaderLine('Authorization'));
        $this->assertInstanceOf(ResponseInterface::class, $res);
        $this->assertEquals(200, $res->getStatusCode());
        $this->assertEquals('{"name":"John Doe","action":"echo"}', $res->getBody()->getContents());
    }

    /**
     * @covers \Lin\AppPhp\Server\SinglePageApp::__construct
     * @covers \Lin\AppPhp\Server\SinglePageApp::HandleRequest
     * @covers \Lin\AppPhp\Server\App::__construct
     */
    public function testHandleRequestWrongMethod(): void
    {
        $req = new ServerRequest('PUT', '/api/v1/user.php');
        $res = $this->app->HandleRequest($req);
        $this->assertInstanceOf(ResponseInterface::class, $res);
        $this->assertEquals(405, $res->getStatusCode());
    }

    /**
     * @covers \Lin\AppPhp\Server\SinglePageApp::__construct
     * @covers \Lin\AppPhp\Server\SinglePageApp::HandleRequest
     * @covers \Lin\AppPhp\Server\SinglePageApp::OnPost
     * @covers \Lin\AppPhp\Server\App::__construct
     * @covers \Lin\AppPhp\Server\App::ParsePHPInput
     * @covers \Lin\AppPhp\Server\App::ParseJsonInput
     * @covers \Lin\AppPhp\Server\App::ParseContentByType
     */
    public function testHandleRequestMissingAction(): void
    {
        $req = $this->createServerRequest('missing');
        $res = $this->app->HandleRequest($req);
        $this->assertInstanceOf(ResponseInterface::class, $res);
        $this->assertEquals(400, $res->getStatusCode());
    }

    /**
     * @covers \Lin\AppPhp\Server\SinglePageApp::__construct
     * @covers \Lin\AppPhp\Server\SinglePageApp::AddPostAction
     * @covers \Lin\AppPhp\Server\App::__construct
     */
    public function testAddPostActionInvalid(): void
    {
        $app = new SinglePageApp($this->html);
        try {
            $app->AddPostAction('hello', function (ServerRequestInterface $request, bool $valid): ResponseInterface {
                return (new Psr17Factory())->createResponse(200)->withBody((new Psr17Factory())->createStream('{"hello":"world"}'));
            });
            $this->fail('An expected exception has not been raised.');
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
        }
    }
}
