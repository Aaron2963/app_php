<?php

namespace Lin\AppPhp\Server;

class RestfulApp extends App
{
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
        global $_PUT, $_DELETE, $_PATCH;
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
            case 'PUT':
                $this->ParsePHPInput();
                $this->ServerRequest = $this->ServerRequest->withParsedBody($_PUT);
                $this->Response = $this->OnPut();
                break;
            case 'DELETE':
                $this->ParsePHPInput();
                $this->ServerRequest = $this->ServerRequest->withParsedBody($_DELETE);
                $this->Response = $this->OnDelete();
                break;
            case 'PATCH':
                $this->ParsePHPInput();
                $this->ServerRequest = $this->ServerRequest->withParsedBody($_PATCH);
                $this->Response = $this->OnPatch();
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
        return $this->Psr17Factory->createResponse(405);
    }

    /**
     * Handle Request when Method is POST
     * 
     * @return \Psr\Http\Message\ResponseInterface
     * 
     */
    public function OnPost()
    {
        return $this->Psr17Factory->createResponse(405);
    }

    /**
     * Handle Request when Method is PUT
     *
     * @return \Psr\Http\Message\ResponseInterface
     * 
     */
    public function OnPut()
    {
        return $this->Psr17Factory->createResponse(405);
    }

    /**
     * Handle Request when Method is PATCH
     *
     * @return \Psr\Http\Message\ResponseInterface
     * 
     */
    public function OnPatch()
    {
        return $this->Psr17Factory->createResponse(405);
    }

    /**
     * Handle Request when Method is DELETE
     *
     * @return \Psr\Http\Message\ResponseInterface
     * 
     */
    public function OnDelete()
    {
        return $this->Psr17Factory->createResponse(405);
    }
}
