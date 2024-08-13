<?php

namespace Lin\AppPhp\Server;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionFunction;
use Closure;
use Exception;

class SinglePageApp extends App
{
    /**
     * Web page content
     *
     * @var string
     */
    protected $WebPage = '';

    /**
     * Post Actions, key is action name, value is callback function with one parameter `$ServerRequest`
     *
     * @var array
     */
    protected $PostActions = [];

    /**
     * Request
     *
     * @var ServerRequestInterface
     */
    protected $ServerRequest = null;

    /**
     * Constructor
     *
     * @param   string  $WebPage    Web page content
     * 
     */
    public function __construct($WebPage)
    {
        parent::__construct();
        $this->WebPage = $WebPage;
    }

    /**
     * Handle Server Request
     *
     * @param ServerRequestInterface $ServerRequest
     * 
     * @return ResponseInterface
     * 
     */
    public function HandleRequest(ServerRequestInterface $ServerRequest): ResponseInterface
    {
        $this->ServerRequest = $ServerRequest;
        $this->RawBody = $ServerRequest->getBody()->getContents();
        $Method = $ServerRequest->getMethod();
        // 處理請求
        switch ($Method) {
            case 'GET':
                $this->Response = $this->OnGet();
                break;
            case 'POST':
                if (empty($this->ServerRequest->getParsedBody())) {
                    $this->ParsePHPInput();
                }
                $this->Response = $this->OnPost();
                break;
            default:
                $this->Response = $this->Psr17Factory->createResponse(405);
                break;
        }
        return $this->Response;
    }

    /**
     * Handle Request when Method is GET
     * 
     * @return ResponseInterface
     * 
     */
    public function OnGet()
    {
        $ResponseBody = $this->Psr17Factory->createStream($this->WebPage);
        $this->Response = $this->Psr17Factory->createResponse(200)->withBody($ResponseBody);
        $this->Response = $this->Response->withHeader('Content-Type', 'text/html; charset=utf-8');
        return $this->Response;
    }

    /**
     * Handle Request when Method is POST
     * 
     * @return ResponseInterface
     * 
     */
    public function OnPost()
    {
        $Action = $this->ServerRequest->getParsedBody()['action'];
        $Callback = $this->PostActions[$Action] ?? null;
        if (!isset($Callback)) {
            $ResponseBody = $this->Psr17Factory->createStream("Bad Request: `action=$Action` not found");
            return $this->Response = $this->Psr17Factory->createResponse(400)->withBody($ResponseBody);
        }
        if ($Callback instanceof RequestHandlerInterface) {
            return $this->Response = $Callback->handle($this->ServerRequest);
        }
        return $this->Response = $Callback($this->ServerRequest);
    }

    /**
     * Add Listening Post Action
     *
     * @param string    $Action     Action name
     * @param Closure   $Callback   Callback function with `ServerRequest` as parameter, return ResponseInterface
     * 
     * @return self
     * 
     */
    public function AddPostAction(string $Action, Closure $Callback): self
    {
        //check if $Callback is function and has one parameter
        $CallbackReflection = new ReflectionFunction($Callback);
        if (
            $CallbackReflection->getNumberOfParameters() !== 1 ||
            $CallbackReflection->getParameters()[0]->getType()->getName() !== ServerRequestInterface::class ||
            $CallbackReflection->getReturnType() == null ||
            $CallbackReflection->getReturnType()->getName() !== ResponseInterface::class
        ) {
            throw new Exception("Callback function must have exactly one parameter");
        }
        $this->PostActions[$Action] = $Callback;
        return $this;
    }

    /**
     * Add Listening Post Action with RequestHandlerInterface
     * 
     * @param string                    $Action     Action name
     * @param RequestHandlerInterface   $Handler    RequestHandlerInterface
     * 
     * @return self
     * 
     */
    public function AddPostActionHandler(string $Action, RequestHandlerInterface $Handler): self
    {
        $this->PostActions[$Action] = $Handler;
        return $this;
    }
}
