<?php

namespace Lin\AppPhp\Server;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Lin\AppPhp\Authorization\AuthorizationInterface;

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
        $Headers = [];
        foreach (\getallheaders() as $key => $value) {
            $Headers[\strtoupper($key)] = $value;
            $_SERVER['HTTP_' . \str_replace('-', '_', \strtoupper($key))] = $value;
        }
        $Psr17Factory = new Psr17Factory();
        return $Psr17Factory->createServerRequest($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_SERVER)
            ->withAddedHeader('Authorization', $Headers['AUTHORIZATION'] ?? '');
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
        return $this->HandleRequest($ServerRequest);
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
        $Psr17Factory = new Psr17Factory();
        $Response = $Psr17Factory->createResponse(401);
        $Response->getBody()->write(json_encode(["message" => "unauthorized request"]));
        return $Response;
    }

    /**
     * Parse request body from `php://input`, and put it into `$_PUT|$_PATCH|$_DELETE` and `$_FILES`
     *
     * @return void
     * 
     */
    protected function ParsePHPInput(): void
    {
        $Method = $this->ServerRequest->getMethod();
        $ValidMethods = ['PUT', 'PATCH', 'DELETE', 'POST'];
        if (!in_array($Method, $ValidMethods)) {
            return;
        }
        $ContentType = $this->ServerRequest->getHeader('Content-Type');
        $ContentType = $ContentType[0] ?? '';
        $ContentType = explode(';', $ContentType)[0];
        $ContentType = strtolower(trim($ContentType));
        if ($ContentType === '') {
            return;
        }
        if (str_starts_with($ContentType, 'multipart/form-data')) {
            if ($Method === 'POST' && ini_get('enable_post_data_reading')) {
                // php://input is not available in POST requests with enctype="multipart/form-data" if enable_post_data_reading option is enabled.
                // https://www.php.net/manual/en/wrappers.php.php#wrappers.php.input
                return;
            }
            $Data = $this->ParseMultipartFormDataInput();
        } else if ($ContentType === 'application/json') {
            $Data = $this->ParseJsonInput();
        } else if ($ContentType === 'application/x-www-form-urlencoded') {
            $Data = $this->ParseFormUrlencodedInput();
        } else {
            throw new \RuntimeException('Unsupported Content-Type: ' . $ContentType);
        }
        $GLOBALS['_' . $Method] = $Data;
    }

    /**
     * Parse JSON from `php://input`
     *
     * @return array
     */
    protected function ParseJsonInput(): array
    {
        /* data comes in on the stdin stream */
        $inputdata = fopen("php://input", "r");

        /* Read the data 1 KB at a time and write to the file */
        $raw_data = '';
        while ($chunk = fread($inputdata, 1024)) {
            $raw_data .= $chunk;
        }

        /* Close the streams */
        fclose($inputdata);
        $this->RawBody = $raw_data;

        return json_decode($raw_data, true);
    }

    /**
     * Parse multipart/form-data from `php://input`
     *
     * @return array
     */
    protected function ParseMultipartFormDataInput(): array
    {
        unset($_FILES);

        /* data comes in on the stdin stream */
        $inputdata = fopen("php://input", "r");

        /* Read the data 1 KB at a time and write to the file */
        $raw_data = '';
        while ($chunk = fread($inputdata, 1024)) {
            $raw_data .= $chunk;
        }

        /* Close the streams */
        fclose($inputdata);
        $this->RawBody = $raw_data;

        // Fetch content and determine boundary
        $boundary = substr($raw_data, 0, strpos($raw_data, "\r\n"));

        // Fetch each part
        $parts = array_slice(explode($boundary, $raw_data), 1);
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
    protected function ParseFormUrlencodedInput(): array
    {
        /* data comes in on the stdin stream */
        $inputdata = fopen("php://input", "r");

        /* Read the data 1 KB at a time and write to the file */
        $raw_data = '';
        while ($chunk = fread($inputdata, 1024)) {
            $raw_data .= $chunk;
        }

        /* Close the streams */
        fclose($inputdata);
        $this->RawBody = $raw_data;

        parse_str($raw_data, $data);
        return $data;
    }

    /**
     * Send Response
     *
     * @return void
     * 
     */
    public function SendResponse(): void
    {
        $Response = $this->Response;
        if (headers_sent()) {
            throw new \RuntimeException('Headers were already sent. The response could not be emitted!');
        }

        // Step 1: Send the "status line".
        $statusLine = sprintf(
            'HTTP/%s %s %s',
            $Response->getProtocolVersion(),
            $Response->getStatusCode(),
            $Response->getReasonPhrase()
        );
        header($statusLine, TRUE); /* The header replaces a previous similar header. */

        // Step 2: Send the response headers from the headers list.
        foreach ($Response->getHeaders() as $name => $values) {
            $responseHeader = sprintf(
                '%s: %s',
                $name,
                $Response->getHeaderLine($name)
            );
            header($responseHeader, FALSE); /* The header doesn't replace a previous similar header. */
        }

        // Step 3: Output the message body.
        echo $Response->getBody();
        return;
    }
}
