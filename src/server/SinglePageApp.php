<?php

namespace Lin\AppPhp\Server;

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
     * @var \Psr\Http\Message\ServerRequestInterface
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
     * @param \Psr\Http\Message\ServerRequestInterface $ServerRequest
     * 
     * @return \Psr\Http\Message\ResponseInterface
     * 
     */
    public function HandleRequest($ServerRequest)
    {
        $this->ServerRequest = $ServerRequest;
        $Method = $ServerRequest->getMethod();
        // 處理請求
        switch ($Method) {
            case 'GET':
                $this->Response = $this->OnGet();
                break;
            case 'POST':
                $this->ServerRequest = $this->ServerRequest->withParsedBody($_POST);
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
     * @return \Psr\Http\Message\ResponseInterface
     * 
     */
    public function OnGet()
    {
        $ResponseBody = $this->Psr17Factory->createStream($this->WebPage);
        return $this->Psr17Factory->createResponse(200)->withBody($ResponseBody);
    }

    /**
     * Handle Request when Method is POST
     * 
     * @return \Psr\Http\Message\ResponseInterface
     * 
     */
    public function OnPost()
    {
        $Action = $this->ServerRequest->getParsedBody()['action'];
        if (isset($this->PostActions[$Action])) {
            return $this->PostActions[$Action]($this->ServerRequest);
        }
        $ResponseBody = $this->Psr17Factory->createStream("Bad Request: `action=$Action` not found");
        return $this->Psr17Factory->createResponse(400)->withBody($ResponseBody);
    }

    /**
     * Add Listening Post Action
     *
     * @param string    $Action     Action name
     * @param \Closure  $Callback   Callback function with `ServerRequest` as parameter
     * 
     * @return self
     * 
     */
    public function AddPostAction($Action, $Callback)
    {
        //check if $Callback is function and has one parameter
        $CallbackReflection = new \ReflectionFunction($Callback);
        if ($CallbackReflection->getNumberOfParameters() !== 1) {
            throw new \Exception("Callback function must have exactly one parameter");
        }
        $this->PostActions[$Action] = $Callback;
        return $this;
    }
}
