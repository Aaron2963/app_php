<?php

namespace Lin\AppPhp\Server;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class RestfulApp extends App
{
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
        global $_PUT, $_DELETE, $_PATCH;
        $this->ServerRequest = $ServerRequest;
        $Method = $ServerRequest->getMethod();
        foreach (getallheaders() as $Name => $Value) {
            $this->ServerRequest = $this->ServerRequest->withHeader($Name, $Value);
        }
        // 處理請求
        switch ($Method) {
            case 'GET':
                $this->Response = $this->OnGet();
                break;
            case 'POST':
                $this->ParsePHPInput();
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
     * @return ResponseInterface
     * 
     */
    public function OnGet()
    {
        return $this->Psr17Factory->createResponse(405);
    }

    /**
     * Handle Request when Method is POST
     * 
     * @return ResponseInterface
     * 
     */
    public function OnPost()
    {
        return $this->Psr17Factory->createResponse(405);
    }

    /**
     * Handle Request when Method is PUT
     *
     * @return ResponseInterface
     * 
     */
    public function OnPut()
    {
        return $this->Psr17Factory->createResponse(405);
    }

    /**
     * Handle Request when Method is PATCH
     *
     * @return ResponseInterface
     * 
     */
    public function OnPatch()
    {
        return $this->Psr17Factory->createResponse(405);
    }

    /**
     * Handle Request when Method is DELETE
     *
     * @return ResponseInterface
     * 
     */
    public function OnDelete()
    {
        return $this->Psr17Factory->createResponse(405);
    }
}
