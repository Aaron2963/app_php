<?php

namespace Lin\AppPhp\Tests\Server;

use Lin\AppPhp\Authorization\AuthorizationInterface;
use Lin\AppPhp\Server\App;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7\Factory\Psr17Factory;

// TODO: unfinished test

ini_set('error_reporting', E_ALL);
ini_set('enable_post_data_reading', 0);

class SampleApp extends App
{
    public function HandleRequest(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->AuthorizeRequest(['read', 'write'])) {
            return App::UnauthorizedResponse();
        }
        return App::JsonResponse(['message' => 'success']);
    }
}

class SampleAuth implements AuthorizationInterface
{
    public function Authorize($token, $scopes = []): bool
    {
        return $token === 'abc123' && in_array('read', $scopes);
    }
}

/**
 * @covers \Lin\AppPhp\Server\App
 */
class AppTest extends TestCase
{
    private $body = '{"name":"John Doe"}';

    private function GenerateServerRequest($method = 'GET', $valid = true, $token_span = 2): ServerRequestInterface
    {
        $token = '';
        if ($token_span > 0) {
            $token .= 'Bearer';
        }
        if ($token_span > 1) {
            $token .= ' abc123';
        }
        if ($token_span > 2) {
            $token .= ' invalid';
        }
        if ($valid) {
            $_SERVER['HTTP_AUTHORIZATION'] = $token;
        }
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = '/api/v1/user';
        $headers = $valid ? ['Authorization' => $token] : [];
        $request = new ServerRequest($method, '/api/v1/user', $headers);

        if ($method === 'POST') {
            $request = $request->withAddedHeader('Content-Type', 'application/json; charset=utf-8');
            $stream = (new Psr17Factory())->createStream($this->body);
            $request = $request->withBody($stream);
        }

        return $request;
    }

    private function withMultipartFormData(ServerRequestInterface $request, array $data): ServerRequestInterface
    {
        $psr17Factory = new Psr17Factory();
    
        // Generate a unique boundary
        $boundary = '-------------' . uniqid();
    
        // Create the multipart body
        $body = '';
        foreach ($data as $name => $value) {
            if (is_array($value) && isset($value['tmp_name']['sub'])) {
                $filename = basename($value['name']['sub']);
                $contnent = file_get_contents($value['tmp_name']['sub']);
                $body .= "--$boundary\r\n";
                $body .= "Content-Disposition: form-data; name=\"{$name}[sub]\"; filename=\"$filename\"\r\n";
                $body .= "Content-Type: " . mime_content_type($value['tmp_name']['sub']) . "\r\n\r\n";
                $body .= $contnent . "\r\n";
                continue;
            }
            if (is_array($value) && isset($value['tmp_name'])) {
                $filename = basename($value['name']);
                $contnent = file_get_contents($value['tmp_name']);
                $body .= "--$boundary\r\n";
                $body .= "Content-Disposition: form-data; name=\"$name\"; filename=\"$filename\"\r\n";
                $body .= "Content-Type: " . mime_content_type($value['tmp_name']) . "\r\n\r\n";
                $body .= $contnent . "\r\n";
                continue;
            }
            $body .= "--$boundary\r\n";
            $body .= "Content-Disposition: form-data; name=\"$name\"\r\n\r\n";
            $body .= "$value\r\n";
        }
        $body .= "--$boundary--\r\n";
    
        // Create a new stream with the multipart body
        $stream = $psr17Factory->createStream($body);
    
        // Create a new request with updated headers and body
        return $request
            ->withHeader('Content-Type', 'multipart/form-data; boundary=' . $boundary)
            ->withHeader('Content-Length', (string) $stream->getSize())
            ->withBody($stream);
    }

    /**
     * @covers \Lin\AppPhp\Server\App::CreateServerRequest
     */
    public function testCreateServerRequest(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer abc123';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/v1/user';
        $serverRequest = App::CreateServerRequest();
        $this->assertInstanceOf(ServerRequestInterface::class, $serverRequest);
        $this->assertEquals('Bearer abc123', $serverRequest->getHeaderLine('Authorization'));
        $this->assertEquals('GET', $serverRequest->getMethod());
        $this->assertEquals('/api/v1/user', $serverRequest->getUri()->getPath());
    }

    /**
     * @covers \Lin\AppPhp\Server\App::JsonResponse
     * @covers \Lin\AppPhp\Server\App::NoContentResponse
     * @covers \Lin\AppPhp\Server\App::UnauthorizedResponse
     */
    public function testResponseMethods(): void
    {
        $jsonResponse = App::JsonResponse(['message' => 'success']);
        $this->assertInstanceOf(ResponseInterface::class, $jsonResponse);
        $this->assertEquals(200, $jsonResponse->getStatusCode());
        $this->assertEquals('application/json; charset=utf-8', $jsonResponse->getHeaderLine('Content-Type'));

        $noContentResponse = App::NoContentResponse();
        $this->assertInstanceOf(ResponseInterface::class, $noContentResponse);
        $this->assertEquals(204, $noContentResponse->getStatusCode());

        $unauthorizedResponse = App::UnauthorizedResponse();
        $this->assertInstanceOf(ResponseInterface::class, $unauthorizedResponse);
        $this->assertEquals(401, $unauthorizedResponse->getStatusCode());
        $this->assertEquals('{"message":"unauthorized request"}', $unauthorizedResponse->getBody()->getContents());
    }

    /**
     * @covers \Lin\AppPhp\Server\App::WithAuthorization
     * @covers \Lin\AppPhp\Server\App::AuthorizeRequest
     */
    public function testAuthorizeRequest(): void
    {
        $request = $this->GenerateServerRequest('POST');
        $authorization = new SampleAuth();
        $app = new SampleApp();
        //without authorization
        $this->assertTrue($app->AuthorizeRequest(['delete']));
        //with authorization
        $app = $app->WithAuthorization($authorization)->WithServerRequest($request);
        $this->assertTrue($app->AuthorizeRequest(['read', 'write']));
        $this->assertFalse($app->AuthorizeRequest(['delete']));
        //with invalid token
        $invalid_request = $this->GenerateServerRequest('GET', false);
        $app = $app->WithServerRequest($invalid_request);
        $this->assertFalse($app->AuthorizeRequest(['read', 'write']));
        //with 3-span token
        $invalid_request = $this->GenerateServerRequest('GET', true, 3);
        $app = $app->WithServerRequest($invalid_request);
        $this->assertFalse($app->AuthorizeRequest(['read', 'write']));
    }

    /**
     * @covers \Lin\AppPhp\Server\App::handle
     */
    public function testHandleRequest(): void
    {
        $request = $this->GenerateServerRequest('GET');
        $authorization = new SampleAuth();
        $app = new SampleApp();
        $app = $app->WithAuthorization($authorization)->WithServerRequest($request);
        $response = $app->handle($request);
        $this->assertEquals('{"message":"success"}', $response->getBody()->getContents());
    }

    /**
     * @covers \Lin\AppPhp\Server\App::GetServerRequest
     * @covers \Lin\AppPhp\Server\App::GetResponse
     * @covers \Lin\AppPhp\Server\App::GetRawBody
     */
    public function testGetters(): void
    {
        $request = $this->GenerateServerRequest('POST');
        $authorization = new SampleAuth();
        //get server request and response
        $app = new SampleApp();
        $app = $app->WithAuthorization($authorization)->WithServerRequest($request);
        $this->assertEquals($request->getUri(), $app->GetServerRequest()->getUri());
        $response = $app->handle($request);
        $this->assertEquals($response, $app->GetResponse());
        //get raw body
        $request = $this->GenerateServerRequest('POST');
        $app = new SampleApp();
        $app = $app->WithServerRequest($request);
        $this->assertEquals($this->body, $app->GetRawBody());
    }
    
    /**
     * @covers \Lin\AppPhp\Server\App::AddHeaders
     */
    public function testAddHeaders(): void
    {
        $request = $this->GenerateServerRequest('GET');
        $app = new SampleApp();
        $app = $app->WithServerRequest($request);
        try {
            $app->AddHeaders(['X-Test' => 'test']);
            $this->fail('Exception not thrown');
        } catch (\Throwable $th) {
            $this->assertEquals('Response is null when adding headers', $th->getMessage());
        }
        $app->handle($request);
        $app->AddHeaders(['X-Test' => 'test']);
        $this->assertEquals('test', $app->GetResponse()->getHeaderLine('X-Test'));
    }

    /**
     * @covers \Lin\AppPhp\Server\App::SendResponse
     * @runInSeparateProcess
     */
    public function testSend(): void
    {
        $request = $this->GenerateServerRequest('GET');
        $app = new SampleApp();
        $app = $app->WithServerRequest($request);
        try {
            $app->SendResponse();
            $this->fail('Exception not thrown');
        } catch (\Throwable $th) {
            $this->assertEquals('Response is null when sending response', $th->getMessage());
        }
        $app->handle($request);
        $app->SendResponse();
        $this->expectOutputString('{"message":"success"}');
    }

    /**
     * @covers \Lin\AppPhp\Server\App::SendResponse
     */
    public function testHeadersSent(): void
    {
        $request = $this->GenerateServerRequest('GET');
        $app = new SampleApp();
        $app = $app->WithServerRequest($request);
        $app->handle($request);
        try {
            $app->SendResponse();
            $this->fail('Exception not thrown');
        } catch (\Throwable $th) {
            $this->assertEquals('Headers were already sent. The response could not be emitted!', $th->getMessage());
        }
    }

    /**
     * @covers \Lin\AppPhp\Server\App::ParseMultipartFormDataInput
     */
    public function testParseMultipartFormDataInput(): void
    {
        $image = __DIR__ . '/asset/lorem.jpg';
        $data = [
            'name' => 'John Doe',
            'image' => [
                'name' => 'test-lorem.jpg',
                'tmp_name' => $image,
                'type' => 'image/jpeg',
                'error' => 0,
                'size' => filesize($image)
            ],
            'image-nested' => [
                'name' => ['sub' => 'test-nested-lorem.jpg'],
                'tmp_name' => ['sub' => $image],
                'type' => ['sub' => 'image/jpeg'],
                'error' => ['sub' => 0],
                'size' => ['sub' => filesize($image)]
            ]
        ];
        $request = $this->GenerateServerRequest('POST');
        $request = $this->withMultipartFormData($request, $data);
        $app = new SampleApp();
        $app->handle($request);
        $this->assertEquals('John Doe', $app->GetServerRequest()->getParsedBody()['name']);
    }

    /**
     * @covers \Lin\AppPhp\Server\App::ParseFormUrlencodedInput
     */
    public function testParseFormUrlencodedInput(): void
    {
        $request = $this->GenerateServerRequest('POST');
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');
        $stream = (new Psr17Factory())->createStream('name=John+Doe');
        $request = $request->withBody($stream);
        $app = new SampleApp();
        $app->handle($request);
        $this->assertEquals('John Doe', $app->GetServerRequest()->getParsedBody()['name']);
    }

    /**
     * @covers \Lin\AppPhp\Server\App::ParseContentByType
     */
    public function testHandleUnknownContentType(): void
    {
        $request = $this->GenerateServerRequest('POST');
        $request = $request->withHeader('Content-Type', 'application/xml');
        $app = new SampleApp();
        $app->handle($request);
        $this->assertEquals('{"message":"success"}', $app->GetResponse()->getBody()->getContents());
    }
}
