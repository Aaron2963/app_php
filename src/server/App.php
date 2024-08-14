<?php

namespace Lin\AppPhp\Server;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Lin\AppPhp\Authorization\AuthorizationInterface;
use Nyholm\Psr7Server\ServerRequestCreator;

abstract class App implements RequestHandlerInterface
{
    /**
     * Request
     *
     * @var ServerRequestInterface
     */
    protected $ServerRequest = null;

    /**
     * Response
     *
     * @var ResponseInterface
     */
    protected $Response = null;

    /**
     * Factory for PSR-17 Response
     *
     * @var \Nyholm\Psr7\Factory\Psr17Factory
     */
    protected $Psr17Factory = null;

    /**
     * Authorization mechanism
     *
     * @var AuthorizationInterface
     */
    protected $Authorization = null;

    /**
     * Raw body
     * 
     * @var string
     */
    protected $RawBody = null;

    /**
     * Error
     *
     * @var \Exception
     */
    public $Error = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->Psr17Factory = new Psr17Factory();
    }

    /**
     * Create PSR-7 server request with Authorization header
     *
     * @return ServerRequestInterface
     */
    static public function CreateServerRequest()
    {
        $Factory = new Psr17Factory();
        $Creator = new ServerRequestCreator(
            $Factory,
            $Factory,
            $Factory,
            $Factory
        );
        $_FILES = $_FILES ?? [];
        $Request = $Creator->fromGlobals();
        $ParsedBody = self::ParseContentByType(
            $Request->getBody()->getContents(),
            $Request->getHeader('Content-Type')[0] ?? '',
            $GLOBALS['_' . strtoupper($Request->getMethod())]
        );
        return $Request->withParsedBody($ParsedBody);
    }

    /**
     * Handle Server Request
     *
     * @param ServerRequestInterface $ServerRequest
     * 
     * @return ResponseInterface
     * 
     */
    abstract public function HandleRequest(ServerRequestInterface $ServerRequest): ResponseInterface;

    /**
     * Handle Server Request
     *
     * @param ServerRequestInterface $ServerRequest
     * 
     * @return ResponseInterface
     * 
     */
    public function handle(ServerRequestInterface $ServerRequest): ResponseInterface
    {
        $this->ServerRequest = $ServerRequest;
        $this->RawBody = $this->ServerRequest->getBody()->getContents();
        if (empty($ServerRequest->getParsedBody())) {
            $this->ParsePHPInput();
        }
        $this->Response = $this->HandleRequest($ServerRequest);
        return $this->Response;
    }

    /**
     * Set Authorization
     *
     * @param AuthorizationInterface $Authorization
     * 
     * @return self
     * 
     */
    public function WithAuthorization(AuthorizationInterface $Authorization): self
    {
        $this->Authorization = $Authorization;
        return $this;
    }

    /**
     * Authorize Server Request
     * 
     * @param   string[]    $RequestScopes     the scopes of requesting resource
     *
     * @return bool 
     * 
     */
    public function AuthorizeRequest(array $RequestScopes = []): bool
    {
        if ($this->Authorization == null) {
            return true;
        }
        $Token = $this->ServerRequest->getHeader('Authorization');
        if (count($Token) == 0) {
            return false;
        }
        $Token = explode(' ', $Token[0]);
        if (count($Token) != 2) {
            return false;
        }
        return $this->Authorization->Authorize(array_pop($Token), $RequestScopes);
    }

    /**
     * Set ServerRequest
     * 
     * @return self
     * 
     */
    public function WithServerRequest(ServerRequestInterface $ServerRequest): self
    {
        $this->ServerRequest = $ServerRequest;
        $this->RawBody = $ServerRequest->getBody()->getContents();
        return $this;
    }

    /**
     * Get ServerRequest
     * 
     * @return ServerRequestInterface
     * 
     */
    public function GetServerRequest(): ServerRequestInterface
    {
        return $this->ServerRequest;
    }

    /**
     * Get Response
     *
     * @return ResponseInterface
     * 
     */
    public function GetResponse(): ResponseInterface
    {
        return $this->Response;
    }

    /**
     * Add response headers
     *
     * @param  array $Headers
     * @return void
     */
    public function AddHeaders(array $Headers): void
    {
        if ($this->Response == null) {
            throw new \RuntimeException('Response is null when adding headers');
        }
        foreach ($Headers as $Name => $Value) {
            $this->Response = $this->Response->withHeader($Name, $Value);
        }
    }

    /**
     * Get body raw data as string
     *
     * @return string
     */
    public function GetRawBody(): string
    {
        return $this->RawBody;
    }

    /**
     * Get response with JSON body
     * 
     * @param   array   $Array      content of response body
     * @param   int     $StatusCode status code
     *
     * @return ResponseInterface
     * 
     */
    static public function JsonResponse(array $Array, int $StatusCode = 200): ResponseInterface
    {
        $Psr17Factory = new Psr17Factory();
        $ResponseBody = $Psr17Factory->createStream(json_encode($Array, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $Psr17Factory->createResponse($StatusCode)->withBody($ResponseBody)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    /**
     * Get `204 No Content` response
     *
     * @return ResponseInterface
     * 
     */
    static public function NoContentResponse(): ResponseInterface
    {
        $Psr17Factory = new Psr17Factory();
        return $Psr17Factory->createResponse(204);
    }

    /**
     * Get `401 Unauthorized` response
     *
     * @return ResponseInterface
     * 
     */
    static public function UnauthorizedResponse(): ResponseInterface
    {
        return self::JsonResponse(['message' => 'unauthorized request'], 401);
    }

    /**
     * Parse request body from `php://input`, and put it into `$_PUT|$_PATCH|$_DELETE` and `$_FILES`
     *
     * @return array
     * 
     */
    protected function ParsePHPInput(): void
    {
        $Method = $this->ServerRequest->getMethod();
        $ValidMethods = ['PUT', 'PATCH', 'DELETE', 'POST'];
        if (!in_array($Method, $ValidMethods)) {
            return;
        }
        $ContentType = $this->ServerRequest->getHeader('Content-Type')[0] ?? '';
        try {
            $Data = self::ParseContentByType($this->RawBody, $ContentType, $GLOBALS['_' . $Method]);
        } catch (\Exception $e) {
            $Data = [];
        }
        $this->ServerRequest = $this->ServerRequest->withParsedBody($Data);
    }

    static public function ParseContentByType(string $Content, string $Type, array &$Result = []): array
    {
        if (ini_get('enable_post_data_reading')) {
            trigger_error('php://input is not available in POST requests with enctype="multipart/form-data" if enable_post_data_reading option is enabled.', E_USER_WARNING);
        }
        $Data = [];
        list($Type,) = explode(';', $Type);
        if (!empty($Type)) {
            switch ($Type) {
                case 'multipart/form-data':
                    $Data = self::ParseMultipartFormDataInput($Content);
                    break;
                case 'application/json':
                    $Data = self::ParseJsonInput($Content);
                    break;
                case 'application/x-www-form-urlencoded':
                    $Data = self::ParseFormUrlencodedInput($Content);
                    break;
                default:
                    throw new \RuntimeException('Unsupported Content-Type: ' . $Type);
            }
        }
        $Result = $Data;
        return $Data;
    }

    /**
     * Parse JSON from `php://input`
     *
     * @return array
     */
    static protected function ParseJsonInput(string $Raw): array
    {
        return json_decode($Raw, true) ?? [];
    }

    /**
     * Parse multipart/form-data from `php://input`
     *
     * @return array
     */
    static protected function ParseMultipartFormDataInput(string $Raw): array
    {
        unset($_FILES);

        // Fetch content and determine boundary
        $boundary = substr($Raw, 0, strpos($Raw, "\r\n"));

        // Fetch each part
        $parts = array_slice(explode($boundary, $Raw), 1);
        $data = array();
        $postParams = [];
        $fileParams = [];

        foreach ($parts as $part) {
            // If this is the last part, break
            if ($part == "--\r\n") break;

            // Separate content from headers
            $part = ltrim($part, "\r\n");
            list($raw_headers, $body) = explode("\r\n\r\n", $part, 2);

            // Parse the headers list
            $raw_headers = explode("\r\n", $raw_headers);
            $headers = array();
            foreach ($raw_headers as $header) {
                list($name, $value) = explode(':', $header);
                $headers[strtolower($name)] = ltrim($value, ' ');
            }

            // Parse the Content-Disposition to get the field name, etc.
            if (!isset($headers['content-disposition'])) {
                continue;
            }
            preg_match(
                '/^(.+); *name="([^"]+)"(; *filename="([^"]+)")?/',
                $headers['content-disposition'],
                $matches
            );
            $name = $matches[2];

            //Parse File
            if (isset($matches[4])) {
                //if labeled the same as previous, skip
                if (isset($_FILES[$matches[2]])) {
                    continue;
                }

                if (!isset($_FILES)) {
                    $_FILES = array();
                }

                //get filename
                $filename = $matches[4];

                //get tmp name
                $tmp_name = tempnam(ini_get('upload_tmp_dir'), 'app');

                //populate $_FILES with information, size may be off in multibyte situation
                $file_info = array(
                    'error' => 0,
                    'name' => $filename,
                    'tmp_name' => $tmp_name,
                    'size' => strlen($body),
                    'type' => $headers['content-type']
                );
                foreach ($file_info as $k => $v) {
                    if (strpos($name, '[') !== false) {
                        $keys = explode('[', $name);
                        array_splice($keys, 1, 0, $k . ']');
                        $sname = implode('[', $keys);
                        $fileParams[] = "$sname=$v";
                    } else {
                        $_FILES[$name] = $file_info;
                        $sname = $name . '[' . $k . ']';
                        $fileParams[] = "$sname=$v";
                    }
                }

                //place in temporary directory
                file_put_contents($tmp_name, $body);
            } else {
                //Parse Field
                $value = substr($body, 0, strlen($body) - 2);
                $postParams[] = "$name=$value";
            }
        }
        $postParams = implode('&', $postParams);
        parse_str($postParams, $data);
        if (count($fileParams) > 0) {
            $fileParams = implode('&', $fileParams);
            parse_str($fileParams, $_FILES);
        }

        return $data;
    }

    /**
     * Parse x-www-form-urlencoded from `php://input`
     *
     * @return array
     */
    static protected function ParseFormUrlencodedInput(string $Raw): array
    {
        parse_str($Raw, $data);
        return $data;
    }

    /**
     * Send response stored in the object
     *
     * @return void
     * 
     */
    public function SendResponse(): void
    {
        $Response = $this->Response;
        if ($Response == null) {
            throw new \RuntimeException('Response is null when sending response');
        }
        App::Send($Response);
        return;
    }

    /**
     * Send Response
     *
     * @param  ResponseInterface $Response
     * 
     * @return void
     */
    static public function Send(ResponseInterface $Response): void
    {
        if (headers_sent()) {
            throw new \RuntimeException('Headers were already sent. The response could not be emitted!');
        }

        $statusLine = sprintf(
            'HTTP/%s %s %s',
            $Response->getProtocolVersion(),
            $Response->getStatusCode(),
            $Response->getReasonPhrase()
        );
        header($statusLine, TRUE);

        foreach ($Response->getHeaders() as $name => $values) {
            $responseHeader = sprintf(
                '%s: %s',
                $name,
                $Response->getHeaderLine($name)
            );
            header($responseHeader, FALSE);
        }

        echo $Response->getBody();
        return;
    }
}
