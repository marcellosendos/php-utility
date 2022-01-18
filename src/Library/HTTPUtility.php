<?php

/*
 * This file is part of marcellosendos/php-utility
 *
 * (c) Marcel Oehler <mo@marcellosendos.ch>
 */

namespace Marcellosendos\PhpUtility\Library;

class HTTPUtility
{

// --- CONSTANTS ----------------------------------------------------------------------------------

    const TYPE_GET = 'GET';
    const TYPE_POST = 'POST';
    const TYPE_PUT = 'PUT';
    const TYPE_DELETE = 'DELETE';

// --- CONFIG -------------------------------------------------------------------------------------

    /**
     * SSL URL prefix
     */
    protected static $SSL_PRE = 'https://';

    /**
     * Query prefix
     */
    protected static $QUERY_PRE = '?';

    /**
     * Header name of content length
     */
    protected static $HEAD_LENGTH = 'Content-Length';

    /**
     * Header name of http code
     */
    protected static $HEAD_CODE = 'Http-Code';

// --- OPTIONS ------------------------------------------------------------------------------------

    /**
     * Absolute path to cookie jar file
     */
    protected $COOKIE_PATH = '';

    /**
     * User agent
     */
    protected $USER_AGENT = '';

    /**
     * Request http headers
     */
    protected $REQUEST_HEADERS = [];

    /**
     * Request authentication
     */
    protected $REQUEST_AUTH = '';

// --- RUNTIME ------------------------------------------------------------------------------------

    /**
     * Reponse header set in callback method for CURLOPT_HEADERFUNCTION
     */
    protected $RESPONSE_HEADER = [];

    /**
     * Response text
     */
    protected $TEXT = '';

    /**
     * Response info list
     */
    protected $INFO = [];

    /**
     * Response http code
     */
    protected $CODE = 0;

    /**
     * Response header list
     */
    protected $HEADER = [];

    /**
     * Response body text
     */
    protected $BODY = '';

// === CLASS ======================================================================================

    /**
     * @param void
     */
    public function __construct()
    {
    }

// === OPTIONS ====================================================================================

    /**
     * @param string $path
     * @return void
     */
    public function setCookiePath($path = '/tmp/http_utility_cookies')
    {
        $this->COOKIE_PATH = $path;
    }

    /**
     * @param string $agent
     * @return void
     */
    public function setUserAgent($agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:90.0) Gecko/20100101 Firefox/90.0')
    {
        $this->USER_AGENT = $agent;
    }

    /**
     * @param array $headers
     * @return void
     */
    public function setRequestHeaders($headers)
    {
        $this->REQUEST_HEADERS = $headers;
    }

    /**
     * @param string $auth
     * @return void
     */
    public function setRequestAuth($auth)
    {
        $this->REQUEST_AUTH = $auth;
    }

// === REQUEST ====================================================================================

    /**
     * @param string $url
     * @param string $type
     * @param mixed $fields
     * @param string $file
     * @return array
     */
    public function request($url, $type = self::TYPE_GET, $fields = null, $file = '')
    {
        if (is_string($file) && strlen($file) > 0) {
            set_time_limit(0);
            $fp = fopen($file, 'w+');

            $curl = curl_init();
            $this->curlSetRequest($curl, $url, $type, $fields);

            curl_setopt($curl, CURLOPT_TIMEOUT, 60);
            curl_setopt($curl, CURLOPT_FILE, $fp);

            $response = $this->curlGetResponse($curl);
            $response['body'] = $url . PHP_EOL . realpath($file);

            curl_close($curl);
            fclose($fp);

            return $response;
        } else {
            $curl = curl_init();
            $this->curlSetRequest($curl, $url, $type, $fields);

            $response = $this->curlGetResponse($curl);

            curl_close($curl);

            return $response;
        }
    }

    /**
     * @param resource $curl
     * @param string $url
     * @param string $type
     * @param mixed $fields
     * @return void
     */
    protected function curlSetRequest($curl, $url, $type, $fields)
    {
        $query = self::httpBuildQuery($fields);

        if (strlen($query) > 0) {
            if ($type == self::TYPE_GET) {
                $url .= self::$QUERY_PRE . $query;
            } else {
                //curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
                $headers[self::$HEAD_LENGTH] = strlen($query);
            }
        }

        if (is_array($this->REQUEST_HEADERS) && count($this->REQUEST_HEADERS) > 0) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $this->REQUEST_HEADERS);
        }

        if (is_string($this->REQUEST_AUTH) && strlen($this->REQUEST_AUTH) > 0) {
            curl_setopt($curl, CURLOPT_USERPWD, $this->REQUEST_AUTH);
        }

        if (strpos($url, self::$SSL_PRE) !== false) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }

        if (is_string($this->COOKIE_PATH) && strlen($this->COOKIE_PATH) > 0) {
            curl_setopt($curl, CURLOPT_COOKIEJAR, $this->COOKIE_PATH);
            curl_setopt($curl, CURLOPT_COOKIEFILE, $this->COOKIE_PATH);
        }

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($type));

        curl_setopt($curl, CURLOPT_URL, $url);
        //curl_setopt($curl, CURLOPT_PORT, $port);
        //curl_setopt($curl, CURLOPT_REFERER, $url);

        if (is_string($this->USER_AGENT) && strlen($this->USER_AGENT) > 0) {
            curl_setopt($curl, CURLOPT_USERAGENT, $this->USER_AGENT);
        }

        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_POSTREDIR, true);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);

        $this->RESPONSE_HEADER = [];
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, [$this, 'curlResponseHeader']);
    }

    /**
     * @param resource $curl
     * @param string $header
     * @return int
     */
    protected function curlResponseHeader($curl, $header)
    {
        if (strlen($header) > 2) {
            if (preg_match('/^([a-z0-9\-]+)\:(.*)$/i', $header, $matches)) {
                $key = trim($matches[1]);
                $value = trim($matches[2]);
            } else {
                $key = self::$HEAD_CODE;
                $value = trim($header);
            }

            if (isset($this->RESPONSE_HEADER[$key])) {
                if (!is_array($this->RESPONSE_HEADER[$key])) {
                    $this->RESPONSE_HEADER[$key] = [$this->RESPONSE_HEADER[$key]];
                }

                $this->RESPONSE_HEADER[$key][] = $value;
            } else {
                $this->RESPONSE_HEADER[$key] = $value;
            }
        } else {
            if (isset($this->RESPONSE_HEADER['Location'])) {
                $this->RESPONSE_HEADER = [];
            }
        }

        return strlen($header);
    }

    /**
     * @param resource $curl
     * @return array
     */
    protected function curlGetResponse($curl)
    {
        $response = [];

        $response['text'] = curl_exec($curl);

        $response['info'] = curl_getinfo($curl);
        $response['code'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        $response['header'] = $this->RESPONSE_HEADER;
        $response['body'] = $response['text'];

        return $response;
    }

// === REQUEST SHORTCUTS ==========================================================================

    /**
     * @param string $url
     * @param mixed $fields
     * @return bool
     */
    public function GETRequest($url, $fields = null)
    {
        return $this->TypeRequest($url, self::TYPE_GET, $fields);
    }

    /**
     * @param string $url
     * @param mixed $fields
     * @return bool
     */
    public function POSTRequest($url, $fields = null)
    {
        return $this->TypeRequest($url, self::TYPE_POST, $fields);
    }

    /**
     * @param string $url
     * @param mixed $fields
     * @return bool
     */
    public function PUTRequest($url, $fields = null)
    {
        return $this->TypeRequest($url, self::TYPE_PUT, $fields);
    }

    /**
     * @param string $url
     * @param mixed $fields
     * @return bool
     */
    public function DELETERequest($url, $fields = null)
    {
        return $this->TypeRequest($url, self::TYPE_DELETE, $fields);
    }

    /**
     * @param string $url
     * @param string $type
     * @param mixed $fields
     * @return bool
     */
    public function TypeRequest($url, $type, $fields = null)
    {
        $this->resetResponse();
        $this->setResponse($this->request($url, $type, $fields));

        return ($this->CODE < 400) ? true : false;
    }

    /**
     * @param string $url
     * @param string $file
     * @return bool
     */
    public function DownloadFile($url, $file)
    {
        $this->resetResponse();
        $this->setResponse($this->request($url, self::TYPE_GET, null, $file));

        return ($this->CODE < 400) ? true : false;
    }

// === RESPONSE ===================================================================================

    /**
     * @return string
     */
    public function getResponseText()
    {
        return $this->TEXT;
    }

    /**
     * @return array
     */
    public function getResponseInfo()
    {
        return $this->INFO;
    }

    /**
     * @return string
     */
    public function getResponseCode()
    {
        return $this->CODE;
    }

    /**
     * @return array
     */
    public function getResponseHeader()
    {
        return $this->HEADER;
    }

    /**
     * @return string
     */
    public function getResponseBody()
    {
        return $this->BODY;
    }

    /**
     * @param array $response
     * @return void
     */
    protected function setResponse($response)
    {
        $this->TEXT = $response['text'];
        $this->INFO = $response['info'];
        $this->CODE = $response['code'];

        $this->HEADER = $response['header'];
        $this->BODY = $response['body'];
    }

    /**
     * @return void
     */
    protected function resetResponse()
    {
        $this->TEXT = '';
        $this->INFO = [];
        $this->CODE = 0;

        $this->HEADER = [];
        $this->BODY = '';
    }

// === HELPER =====================================================================================

    /**
     * @param mixed $fields
     * @return string
     */
    public static function httpBuildQuery($fields)
    {
        if (!is_null($fields)) {
            if (is_array($fields) || is_object($fields)) {
                return http_build_query($fields);
            } else {
                return $fields;
            }
        }

        return '';
    }

    /**
     * @param mixed $fields
     * @return string
     */
    public static function httpBuildQueryApi($fields)
    {
        $query = self::httpBuildQuery($fields);

        $query = preg_replace('/\[[0-9]+\]/', '[]', $query);
        $query = preg_replace('/%5B[0-9]+%5D/', '%5B%5D', $query);

        return $query;
    }

}
