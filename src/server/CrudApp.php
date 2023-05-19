<?php

namespace Lin\AppPhp\Server;

class CrudApp extends App
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
        $this->ServerRequest = $ServerRequest;
        if ($ServerRequest->getMethod() !== 'POST') {
            $this->Response = $this->Psr17Factory->createResponse(405);
            return $this->Response;
        }
        // 檢查授權
        if (!$this->AuthorizeRequest()) {
            $this->Response = $this->Psr17Factory->createResponse(401);
            return $this->Response;
        }
        $Path = $this->ServerRequest->getUri()->getPath();
        $Path = explode('/', $Path);
        $Resource = array_pop($Path);
        $Resource = rtrim($Resource, '.php');
        $Method = explode('.', $Resource);
        $Method = array_pop($Method);
        $Method = strtoupper($Method);
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
     * @return \Psr\Http\Message\ResponseInterface
     * 
     */
    public function OnCreate()
    {
        return $this->Response = $this->Psr17Factory->createResponse(404);
    }

    /**
     * Handle Request when command is `SELECT`
     * 
     * @return \Psr\Http\Message\ResponseInterface
     * 
     */
    public function OnRead()
    {
        return $this->Response = $this->Psr17Factory->createResponse(404);
    }

    /**
     * Handle Request when command is `UPDATE`
     * 
     * @return \Psr\Http\Message\ResponseInterface
     * 
     */
    public function OnUpdate()
    {
        return $this->Response = $this->Psr17Factory->createResponse(404);
    }

    /**
     * Handle Request when command is `DELETE`
     * 
     * @return \Psr\Http\Message\ResponseInterface
     * 
     */
    public function OnDelete()
    {
        return $this->Response = $this->Psr17Factory->createResponse(404);
    }
}
