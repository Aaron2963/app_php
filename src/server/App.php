<?php

namespace Aaron\SPA\Server;

use \Nyholm\Psr7\Factory\Psr17Factory;

abstract class App
{
    /**
     * Request
     *
     * @var \Psr\Http\Message\ServerRequestInterface
     */
    protected $ServerRequest = null;

    /**
     * Response
     *
     * @var \Psr\Http\Message\ResponseInterface
     */
    protected $Response = null;

    /**
     * Factory for PSR-17 Response
     *
     * @var \Nyholm\Psr7\Factory\Psr17Factory
     */
    protected $Psr17Factory = null;

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
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public static function CreateServerRequest()
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
     * @param \Psr\Http\Message\ServerRequestInterface $ServerRequest
     * 
     * @return \Psr\Http\Message\ResponseInterface
     * 
     */
    abstract public function HandleRequest($ServerRequest);

    /**
     * Get ServerRequest
     * 
     * @return \Psr\Http\Message\ServerRequestInterface
     * 
     */
    public function GetServerRequest()
    {
        return $this->ServerRequest;
    }

    /**
     * Get Response
     *
     * @return \Psr\Http\Message\ResponseInterface
     * 
     */
    public function GetResponse()
    {
        return $this->Response;
    }

    /**
     * Get parameter from path, eg. get `123` from `/api/user/123`
     *
     * @return string|false
     * 
     */
    protected function GetPathParameter()
    {
        if ($this->ServerRequest == null) {
            return false;
        }
        $Resource = $this->ServerRequest->getAttribute('resource_name');
        $Param = $this->ServerRequest->getUri()->getPath();
        $Reg = '/' . $Resource . '\/(.*)/';
        preg_match($Reg, $Param, $Matches);
        if (count($Matches) > 1) {
            return $Matches[1];
        }
        return false;
    }

    /**
     * Get `200 OK` response with JSON body
     * 
     * @param   array   $Array  content of response body
     *
     * @return \Psr\Http\Message\ResponseInterface
     * 
     */
    public static function JsonResponse($Array)
    {
        $Psr17Factory = new Psr17Factory();
        $ResponseBody = $Psr17Factory->createStream(json_encode($Array));
        return $Psr17Factory->createResponse(200)->withBody($ResponseBody);
    }

    /**
     * Get `204 No Content` response
     *
     * @return \Psr\Http\Message\ResponseInterface
     * 
     */
    public static function NoContentResponse()
    {
        $Psr17Factory = new Psr17Factory();
        return $Psr17Factory->createResponse(204);
    }

    /**
     * Get `401 Unauthorized` response
     *
     * @return \Psr\Http\Message\ResponseInterface
     * 
     */
    public static function UnautorizedResponse()
    {
        $Psr17Factory = new Psr17Factory();
        $Response = $Psr17Factory->createResponse(401);
        $Response->getBody()->write(json_encode(["message" => "unauthorized request"]));
        return $Response;
    }

    /**
     * Parse formdata from `php://input`, and put it into `$_PUT|$_PATCH|$_DELETE` and `$_FILES`
     *
     * @return void
     * 
     */
    protected function ParsePHPInput()
    {
        $Method = $this->ServerRequest->getMethod();
        $ValidMethods = ['PUT', 'PATCH', 'DELETE'];
        if (!in_array($Method, $ValidMethods)) {
            return;
        }

        /* data comes in on the stdin stream */
        $inputdata = fopen("php://input", "r");

        /* Read the data 1 KB at a time and write to the file */
        $raw_data = '';
        while ($chunk = fread($inputdata, 1024)) {
            $raw_data .= $chunk;
        }

        /* Close the streams */
        fclose($inputdata);

        // Fetch content and determine boundary
        $boundary = substr($raw_data, 0, strpos($raw_data, "\r\n"));

        if (empty($boundary)) {
            parse_str($raw_data, $data);
            $GLOBALS['_' . $Method] = $data;
            return;
        }

        // Fetch each part
        $parts = array_slice(explode($boundary, $raw_data), 1);
        $data = array();

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
            if (isset($headers['content-disposition'])) {
                $filename = null;
                $tmp_name = null;
                preg_match(
                    '/^(.+); *name="([^"]+)"(; *filename="([^"]+)")?/',
                    $headers['content-disposition'],
                    $matches
                );
                list(, $type, $name) = $matches;

                //Parse File
                if (isset($matches[4])) {
                    //if labeled the same as previous, skip
                    if (isset($_FILES[$matches[2]])) {
                        continue;
                    }

                    //get filename
                    $filename = $matches[4];

                    //get tmp name
                    $filename_parts = pathinfo($filename);
                    $tmp_name = tempnam(ini_get('upload_tmp_dir'), $filename_parts['filename']);

                    //populate $_FILES with information, size may be off in multibyte situation
                    $_FILES[$matches[2]] = array(
                        'error' => 0,
                        'name' => $filename,
                        'tmp_name' => $tmp_name,
                        'size' => strlen($body),
                        'type' => $value
                    );

                    //place in temporary directory
                    file_put_contents($tmp_name, $body);
                } else {
                    //Parse Field
                    if (substr($name, -2) == '[]') {
                        $name = substr($name, 0, -2);
                        if (!isset($data[$name])) {
                            $data[$name] = array();
                        }
                        $data[$name][] = substr($body, 0, strlen($body) - 2);
                    } else {
                        $data[$name] = substr($body, 0, strlen($body) - 2);
                    }
                }
            }
        }

        $GLOBALS['_' . $Method] = $data;
    }

    /**
     * Send Response
     *
     * @return void
     * 
     */
    public function SendResponse()
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
