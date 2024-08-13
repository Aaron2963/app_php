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
        $this->ServerRequest = $ServerRequest;
        $this->RawBody = $ServerRequest->getBody()->getContents();
        $Method = $ServerRequest->getMethod();
        // 處理請求
        switch ($Method) {
            case 'GET':
                $this->Response = $this->OnGet();
                break;
            case 'POST':
                if (empty($ServerRequest->getParsedBody())) {
                    $this->ParsePHPInput();
                }
                $this->Response = $this->OnPost();
                break;
            case 'PUT':
                if (empty($ServerRequest->getParsedBody())) {
                    $this->ParsePHPInput();
                }
                $this->Response = $this->OnPut();
                break;
            case 'DELETE':
                if (empty($ServerRequest->getParsedBody())) {
                    $this->ParsePHPInput();
                }
                $this->Response = $this->OnDelete();
                break;
            case 'PATCH':
                if (empty($ServerRequest->getParsedBody())) {
                    $this->ParsePHPInput();
                }
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
