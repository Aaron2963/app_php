<?php

namespace Lin\AppPhp\Server;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class CrudApp extends App
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
        if ($ServerRequest->getMethod() !== 'POST') {
            $this->Response = $this->Psr17Factory->createResponse(405);
            return $this->Response;
        }
        foreach (getallheaders() as $Name => $Value) {
            $this->ServerRequest = $this->ServerRequest->withHeader($Name, $Value);
        }
        $Path = $this->ServerRequest->getUri()->getPath();
        $Path = explode('/', $Path);
        $Resource = array_pop($Path);
        $Resource = rtrim($Resource, '.php');
        $Method = explode('.', $Resource);
        $Method = array_pop($Method);
        $Method = strtoupper($Method);
        $this->ParsePHPInput();
        // 處理請求
        switch ($Method) {
            case 'SELECT':
                $this->ServerRequest = $this->ServerRequest->withParsedBody($_POST);
                $this->Response = $this->OnRead();
                break;
            case 'CREATE':
                $this->ServerRequest = $this->ServerRequest->withParsedBody($_POST);
                $this->Response = $this->OnCreate();
                break;
            case 'UPDATE':
                $this->ServerRequest = $this->ServerRequest->withParsedBody($_POST);
                $this->Response = $this->OnUpdate();
                break;
            case 'DELETE':
                $this->ServerRequest = $this->ServerRequest->withParsedBody($_POST);
                $this->Response = $this->OnDelete();
                break;
            default:
                $this->Response = $this->Psr17Factory->createResponse(404);
                break;
        }
        return $this->Response;
    }

    /**
     * Handle Request when command is `CREATE`
     *
     * @return ResponseInterface
     * 
     */
    public function OnCreate()
    {
        return $this->Response = $this->Psr17Factory->createResponse(404);
    }

    /**
     * Handle Request when command is `SELECT`
     * 
     * @return ResponseInterface
     * 
     */
    public function OnRead()
    {
        return $this->Response = $this->Psr17Factory->createResponse(404);
    }

    /**
     * Handle Request when command is `UPDATE`
     * 
     * @return ResponseInterface
     * 
     */
    public function OnUpdate()
    {
        return $this->Response = $this->Psr17Factory->createResponse(404);
    }

    /**
     * Handle Request when command is `DELETE`
     * 
     * @return ResponseInterface
     * 
     */
    public function OnDelete()
    {
        return $this->Response = $this->Psr17Factory->createResponse(404);
    }
}
