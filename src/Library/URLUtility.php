<?php

/*
 * This file is part of marcellosendos/php-utility
 *
 * (c) Marcel Oehler <mo@marcellosendos.ch>
 */

namespace Marcellosendos\PhpUtility\Library;

class URLUtility
{

// --- CONFIG -------------------------------------------------------------------------------------

    /**
     * Default scheme on import
     */
    protected static $DEFAULT_SCHEME = 'http';

    /**
     * Scheme of ssl connection
     */
    protected static $SECURE_SCHEME = 'https';

    /**
     * Suffix to scheme on export
     */
    protected static $SCHEME_SUFFIX = '://';

    /**
     * Regular expression for matching scheme
     */
    protected static $SCHEME_REGEX = '{://}';

    /**
     * Default port on export
     */
    protected static $DEFAULT_PORT = 80;

    /**
     * Port of ssl connection
     */
    protected static $SECURE_PORT = 443;

    /**
     * Prefix to port on export
     */
    protected static $PORT_PREFIX = ':';

    /**
     * Separator between host elements
     */
    protected static $HOST_SEP = '.';

    /**
     * Separator between path elements
     */
    protected static $PATH_SEP = '/';

    /**
     * Prefix to current path
     */
    protected static $PATH_CURRENT = '.';

    /**
     * Prefix to upper path
     */
    protected static $PATH_UPPER = '..';

    /**
     * Separator between query params on import
     */
    protected static $QUERY_SEP = '&';

    /**
     * Separator between query key/value pairs
     */
    protected static $QUERY_KV_SEP = '=';

    /**
     * Prefix before query string on export
     */
    protected static $QUERY_PRE = '?';

    /**
     * Separator between query params on export
     */
    protected static $QUERY_AMP_SEP = '&amp;';

// --- RUNTIME ------------------------------------------------------------------------------------

    /**
     * Global url for non-server mode or similar
     */
    protected static $UTILITY_URL = '';

    /**
     * Original (given) url
     */
    protected $URL_ORIGINAL = '';

    /**
     * Request scheme, e.g. http, ftp, etc.
     */
    protected $URL_SCHEME = '';

    /**
     * Host
     */
    protected $URL_HOST = '';

    /**
     * Post
     */
    protected $URL_PORT = 0;

    /**
     * Absolute path - starting and ending with slash (/)
     */
    protected $URL_PATH = '';

    /**
     * File name
     */
    protected $URL_FILE_NAME = '';

    /**
     * Array of query params - after the question mark (?)
     */
    protected $URL_QUERY_ARRAY = [];

// === CLASS ======================================================================================

    /**
     * Constructor
     *
     * @param string $url
     */
    public function __construct($url = '')
    {
        if (is_string($url) && strlen($url) > 0) {
            $this->setURL($url);
        } else {
            // if no url is given use one of current script
            $this->resetURL();
        }
    }

// === URL ========================================================================================

    /**
     * Resets internal URL to URL of current script
     *
     * @param void
     * @return void
     */
    public function resetURL()
    {
        // Global url for non-server mode or similar
        if (strlen(self::$UTILITY_URL) > 0) {
            $this->setURL(self::$UTILITY_URL);
        } else {
            // scheme can be http or https
            $scheme =
                (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ||
                (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
                    $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
                    ? self::$SECURE_SCHEME
                    : self::$DEFAULT_SCHEME;

            // host can be forwarded via proxy server
            $host = empty($_SERVER['HTTP_X_FORWARDED_HOST'])
                ? $_SERVER['HTTP_HOST']
                : $_SERVER['HTTP_X_FORWARDED_HOST'];

            // build domain of current url
            $domain = $scheme . self::$SCHEME_SUFFIX . $host;

            // get query of current url
            $query = empty($_SERVER['QUERY_STRING'])
                ? ''
                : (self::$QUERY_PRE . $_SERVER['QUERY_STRING']);

            // request uri is the savest way to current url
            if (isset($_SERVER['REQUEST_URI'])) {
                $url = $domain . $this->getServerRequestURI();
            } // php self is more accurate than script name ...
            elseif (isset($_SERVER['PHP_SELF'])) {
                $url = $domain . $_SERVER['PHP_SELF'] . $query;
            } // ... because script name can contain path to php interpreter
            elseif (isset($_SERVER['SCRIPT_NAME'])) {
                $url = $domain . $_SERVER['SCRIPT_NAME'] . $query;
            } // script url is often not set
            elseif (isset($_SERVER['SCRIPT_URL'])) {
                $url = $domain . $_SERVER['SCRIPT_URL'] . $query;
            } // domain and query string are always set
            else {
                $url = $domain . '' . $query;
            }

            $this->setURL($url);
        }
    }

    /**
     * Sets internal URL to given URL
     *
     * @param string $url
     * @return void
     */
    public function setURL($url)
    {
        $this->URL_ORIGINAL = $url;

        $this->setURLParts($url, true, true, true, true);
    }

    /**
     * Gets complete URL including query
     *
     * @param bool $html
     * @return string
     */
    public function getURL($html = true)
    {
        return $this->getURLParts(true, true, true, true, $html);
    }

    /**
     * Merges internal URL with given URL
     *
     * @param string $url
     * @return void
     */
    public function mergeURL($url)
    {
        $this->mergeURLParts($url, true, true, true, true);
    }

// === SCHEME =====================================================================================

    /**
     * Sets scheme of URL
     *
     * @param string $scheme
     * @return void
     */
    public function setScheme($scheme)
    {
        $this->URL_SCHEME = $scheme;
    }

    /**
     * Gets scheme of URL
     *
     * @param void
     * @return string
     */
    public function getScheme()
    {
        return $this->URL_SCHEME;
    }

    /**
     * Tells if url is secure url
     *
     * @param void
     * @return bool
     */
    public function isSecure()
    {
        return ($this->URL_SCHEME == self::$SECURE_SCHEME);
    }

// === HOST =======================================================================================

    /**
     * Sets host of URL
     *
     * @param string $host
     * @return void
     */
    public function setHost($host)
    {
        $this->URL_HOST = $host;
    }

    /**
     * Gets host of URL
     *
     * @param void
     * @return string
     */
    public function getHost()
    {
        return $this->URL_HOST;
    }

    /**
     * Gets hostname of URL
     *
     * @param void
     * @return string
     */
    public function getHostName()
    {
        $host_list = explode(self::$HOST_SEP, $this->URL_HOST);

        return (count($host_list) > 1
            ? implode('', array_slice(array_reverse($host_list), 1, 1))
            : $this->URL_HOST
        );
    }

// === PORT =======================================================================================

    /**
     * Sets port of URL
     *
     * @param int $port
     * @return void
     */
    public function setPort($port)
    {
        $this->URL_PORT = $port;
    }

    /**
     * Gets port of URL
     *
     * @param void
     * @return int
     */
    public function getPort()
    {
        return $this->URL_PORT;
    }

// === PATH =======================================================================================

    /**
     * Sets absolute path of URL
     *
     * @param string $path
     * @return void
     */
    public function setPath($path)
    {
        $this->URL_PATH = $this->absolutePath($this->fixPath($path));
    }

    /**
     * Gets absolute path to file of URL (without file name)
     *
     * @param void
     * @return string
     */
    public function getPath()
    {
        return $this->URL_PATH;
    }

// === FILE NAME ==================================================================================

    /**
     * Sets file name of URL (without path)
     *
     * @param string $file
     * @return void
     */
    public function setFileName($file)
    {
        $this->URL_FILE_NAME = $file;
    }

    /**
     * Gets file name of URL (without path)
     *
     * @param void
     * @return string
     */
    public function getFileName()
    {
        return $this->URL_FILE_NAME;
    }

// === SCHEME HOST ================================================================================

    /**
     * Sets scheme, host and port of URL
     *
     * @param string $url
     * @return void
     */
    public function setSchemeHost($url)
    {
        $this->setURLParts($url, true, false, false, false);
    }

    /**
     * Gets scheme, host and port of URL
     *
     * @param void
     * @return string
     */
    public function getSchemeHost()
    {
        return $this->getURLParts(true, false, false, false, false);
    }

    /**
     * Merges scheme, host and port with existing scheme, host and port
     *
     * @param string $url
     * @return void
     */
    public function mergeSchemeHost($url)
    {
        $this->mergeURLParts($url, true, false, false, false);
    }

// === SCHEME HOST PATH ===========================================================================

    /**
     * Sets scheme, host, port and path of URL
     *
     * @param string $url
     * @return void
     */
    public function setSchemeHostPath($url)
    {
        $this->setURLParts($url, true, true, false, false);
    }

    /**
     * Gets scheme, host, port and path of URL
     *
     * @param void
     * @return string
     */
    public function getSchemeHostPath()
    {
        return $this->getURLParts(true, true, false, false, false);
    }

    /**
     * Merges scheme, host, port and path with existing scheme, host, port and path
     *
     * @param string $url
     * @return void
     */
    public function mergeSchemeHostPath($url)
    {
        $this->mergeURLParts($url, true, true, false, false);
    }

// === SCHEME HOST FILE PATH ======================================================================

    /**
     * Sets scheme, host, port, path and file of URL
     *
     * @param string $url
     * @return void
     */
    public function setSchemeHostFilePath($url)
    {
        $this->setURLParts($url, true, true, true, false);
    }

    /**
     * Gets scheme, host, port, path and file name of URL
     *
     * @param void
     * @return string
     */
    public function getSchemeHostFilePath()
    {
        return $this->getURLParts(true, true, true, false, false);
    }

    /**
     * Merges scheme, host, port and file path with existing scheme, host, port and file path
     *
     * @param string $url
     * @return void
     */
    public function mergeSchemeHostFilePath($url)
    {
        $this->mergeURLParts($url, true, true, true, false);
    }

// === FILE PATH ==================================================================================

    /**
     * Sets absolute file path of URL
     *
     * @param string $file_path
     * @return void
     */
    public function setFilePath($file_path)
    {
        $this->setURLParts($file_path, false, true, true, false);
    }

    /**
     * Gets absolute file path of URL
     *
     * @param void
     * @return string
     */
    public function getFilePath()
    {
        return $this->getURLParts(false, true, true, false, false);
    }

    /**
     * Merges file path into existing path
     *
     * @param string $file_path
     * @return void
     */
    public function mergeFilePath($file_path)
    {
        $this->mergeURLParts($file_path, false, true, true, false);
    }

// === FILE PATH QUERY ============================================================================

    /**
     * Sets absolute file path of URL with query
     *
     * @param string $file_path_query
     * @return void
     */
    public function setFilePathQuery($file_path_query)
    {
        $this->setURLParts($file_path_query, false, true, true, true);
    }

    /**
     * Gets absolute file path of URL with query
     *
     * @param bool $html
     * @return string
     */
    public function getFilePathQuery($html = true)
    {
        return $this->getURLParts(false, true, true, true, $html);
    }

    /**
     * Merges file path with query into existing path and query
     *
     * @param string $file_path_query
     * @return void
     */
    public function mergeFilePathQuery($file_path_query)
    {
        $this->mergeURLParts($file_path_query, false, true, true, true);
    }

    /**
     * Sets current file path with query as server request uri
     *
     * @param void
     * @return void
     */
    protected function setServerRequestURI()
    {
        $_SERVER['REQUEST_URI'] = $this->getFilePathQuery(false);
    }

    /**
     * Gets server request uri
     *
     * @param void
     * @return string
     */
    protected function getServerRequestURI()
    {
        return $_SERVER['REQUEST_URI'];
    }

// === QUERY STRING ===============================================================================

    /**
     * Sets query from string
     *
     * @param string $query
     * @return void
     */
    public function setQuery($query)
    {
        $this->setQueryList($this->parseQuery($query));
    }

    /**
     * Gets complete query as string
     *
     * @param bool $html
     * @return string
     */
    public function getQuery($html = true)
    {
        $url_query = $this->buildQuery($this->getQueryList(), $html);

        return (strlen($url_query) > 0 ? self::$QUERY_PRE : '') . $url_query;
    }

    /**
     * Deletes complete query
     *
     * @param void
     * @return void
     */
    public function delQuery()
    {
        $this->setQueryList([]);
    }

    /**
     * Merges query string into existing query
     *
     * @param string $query
     * @return void
     */
    public function mergeQuery($query)
    {
        $this->mergeQueryList($this->parseQuery($query));
    }

    /**
     * Resets query using server property
     *
     * @param bool $overwrite
     * @return bool
     */
    public function resetQuery($overwrite = false)
    {
        $reset = false;

        // get current query list
        $query_list = $this->getQueryList();

        // re-parse query
        $new_query = $this->parseQuery($_SERVER['QUERY_STRING'], $overwrite);

        foreach ($new_query as $key => $value) {
            // reset existing query elements not matching corresponding element of new query
            if (isset($query_list[$key]) && $query_list[$key] != $value) {
                $query_list[$key] = $value;
                $reset = true;
            } // set all other elements of new query
            elseif (!isset($query_list[$key])) {
                $query_list[$key] = $value;
                $reset = true;
            }
        }

        $this->setQueryList($query_list);

        return $reset;
    }

// === QUERY LIST =================================================================================

    /**
     * Sets complete query from key/value list
     *
     * @param array $query_list
     * @return void
     */
    public function setQueryList($query_list)
    {
        $this->URL_QUERY_ARRAY = $query_list;
    }

    /**
     * Gets complete query as key/value list
     *
     * @param void
     * @return array
     */
    public function getQueryList()
    {
        return $this->URL_QUERY_ARRAY;
    }

    /**
     * Merges key/value list into existing query list
     *
     * @param array $query_list
     * @return void
     */
    public function mergeQueryList($query_list)
    {
        foreach ($query_list as $key => $value) {
            $this->URL_QUERY_ARRAY[$key] = $value;
        }
    }

    /**
     * Sorts query list by key
     *
     * @param int $sort_flags
     * @return void
     */
    public function sortQueryList($sort_flags = null)
    {
        ksort($this->URL_QUERY_ARRAY, $sort_flags);
    }

// === QUERY PARAM ================================================================================

    /**
     * Sets one param of query
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public function setQueryParam($key, $value)
    {
        $this->mergeQueryList([$key => "$value"]);
    }

    /**
     * Gets one param of query
     *
     * @param string $key
     * @param string $default
     * @return string
     */
    public function getQueryParam($key, $default = '')
    {
        $query_list = $this->getQueryList();

        return (isset($query_list[$key]) ? $query_list[$key] : $default);
    }

    /**
     * Deletes one param of query
     *
     * @param string $key
     * @return void
     */
    public function delQueryParam($key)
    {
        unset($this->URL_QUERY_ARRAY[$key]);

//        $query_list = $this->getQueryList();
//        unset($this->query_list[$key]);
//        $this->setQueryList($query_list);
    }

    /**
     * Tells if query param is set
     *
     * @param string $key
     * @return bool
     */
    public function isQueryParam($key)
    {
        $query_list = $this->getQueryList();

        return isset($query_list[$key]);
    }

// === URL HELPERS ================================================================================

    /**
     * Sets parts of url according to given url and flags
     *
     * @param string $url
     * @param bool $scheme_host
     * @param bool $path
     * @param bool $file
     * @param bool $query
     * @return void
     */
    protected function setURLParts($url, $scheme_host, $path, $file, $query)
    {
        $url_array = parse_url($url);

        // set scheme, host and port
        if ($scheme_host) {
            $this->URL_HOST = isset($url_array['host']) ? $url_array['host'] : '';

            $this->URL_SCHEME =
                isset($url_array['scheme']) ? $url_array['scheme'] : self::$DEFAULT_SCHEME;

            $this->URL_PORT = isset($url_array['port'])
                ? $url_array['port']
                : ($this->URL_SCHEME == self::$SECURE_SCHEME
                    ? self::$SECURE_PORT
                    : self::$DEFAULT_PORT
                );
        }

        // set path and/or file name
        if ($path || $file) {
            $file_path = isset($url_array['path']) ? $url_array['path'] : '';

            // set path and file name ...
            if ($path && $file) {
                [$this->URL_PATH, $this->URL_FILE_NAME] = $this->getPathFile($file_path);
            } // ... or just set path ...
            elseif ($path && !$file) {
                [$this->URL_PATH,] = $this->getPathFile($file_path);
            } // ... or just set file name
            elseif (!$path && $file) {
                [, $this->URL_FILE_NAME] = $this->getPathFile($file_path);
            }
        }

        // set query array from parsed query
        if ($query) {
            $this->URL_QUERY_ARRAY =
                isset($url_array['query']) ? $this->parseQuery($url_array['query']) : [];
        }
    }

    /**
     * Gets parts of url according to given flags
     *
     * @param bool $scheme_host
     * @param bool $path
     * @param bool $file
     * @param bool $query
     * @param bool $html
     * @return string
     */
    protected function getURLParts($scheme_host, $path, $file, $query, $html = true)
    {
        $url = '';

        // empty scheme and empty host are useless
        if ($scheme_host && strlen($this->URL_SCHEME) > 0 && strlen($this->URL_HOST) > 0) {
            $url .= $this->URL_SCHEME . self::$SCHEME_SUFFIX . $this->URL_HOST;

            if (
                ($this->URL_SCHEME == self::$DEFAULT_SCHEME &&
                    $this->URL_PORT != self::$DEFAULT_PORT) ||
                ($this->URL_SCHEME == self::$SECURE_SCHEME &&
                    $this->URL_PORT != self::$SECURE_PORT)
            ) {
                $url .= self::$PORT_PREFIX . $this->URL_PORT;
            }
        }

        // get path, file and query ...
        if ($path && $file && $query) {
            $url .= $this->getPath() . $this->getFileName() . $this->getQuery($html);
        } // ... or just path and file ...
        elseif ($path && $file && !$query) {
            $url .= $this->getPath() . $this->getFileName();
        } // ... or just path ...
        elseif ($path && !$file && !$query) {
            $url .= $this->getPath();
        }

        // ... every other case is useless

        return $url;
    }

    /**
     * Merges parts of url according to given url and flags
     *
     * @param string $url
     * @param bool $scheme_host
     * @param bool $path
     * @param bool $file
     * @param bool $query
     * @return void
     */
    protected function mergeURLParts($url, $scheme_host, $path, $file, $query)
    {
        $url_array = parse_url($url);

        // merge scheme, host and/or port into existing
        if ($scheme_host) {
            if (isset($url_array['host'])) {
                $this->URL_HOST = $url_array['host'];
            }

            if (isset($url_array['scheme'])) {
                $this->URL_SCHEME = $url_array['scheme'];
            }

            // special treatment for url port, because it is normaly determined by scheme
            if (isset($url_array['port'])) {
                $this->URL_PORT = $url_array['port'];
            } else {
                $this->URL_PORT = ($this->URL_SCHEME == self::$SECURE_SCHEME)
                    ? self::$SECURE_PORT
                    : self::$DEFAULT_PORT;
            }
        }

        // merge path and/or file
        if ($path || $file) {
            $file_path = $this->absolutePath($this->mergePaths(
                $this->URL_PATH . $this->URL_FILE_NAME,
                isset($url_array['path']) ? $url_array['path'] : '')
            );

            // set merged path and file name ...
            if ($path && $file) {
                [$this->URL_PATH, $this->URL_FILE_NAME] = $this->getPathFile($file_path);
            } // ... or just set merged path ...
            elseif ($path && !$file) {
                [$this->URL_PATH,] = $this->getPathFile($file_path);
            } // ... or just set merged file name
            elseif (!$path && $file) {
                [, $this->URL_FILE_NAME] = $this->getPathFile($file_path);
            }
        }

        // merge query array with parsed query
        if ($query) {
            $this->URL_QUERY_ARRAY = array_merge(
                $this->URL_QUERY_ARRAY,
                $this->parseQuery(isset($url_array['query']) ? $url_array['query'] : '')
            );
        }
    }

    /**
     * Gets path and file name from file path
     *
     * @param string $file_path
     * @return array
     */
    protected function getPathFile($file_path)
    {
        $file_path_list = explode(self::$PATH_SEP, $this->absolutePath($file_path));

        $file = array_pop($file_path_list);
        array_push($file_path_list, '');

        $path = implode(self::$PATH_SEP, $file_path_list);

        return [$path, $file];
    }

// === FILE PATH HELPERS ==========================================================================

    /**
     * Prepends slash to path if necessary
     *
     * @param string $path
     * @return string
     */
    protected function absolutePath($path)
    {
        return (substr($path, 0, 1) != self::$PATH_SEP ? self::$PATH_SEP . $path : $path);
    }

    /**
     * Appends slash to path if necessary
     *
     * @param string $path
     * @return string
     */
    protected function fixPath($path)
    {
        return
            (substr(strrev($path), 0, 1) != self::$PATH_SEP ? $path . self::$PATH_SEP : $path);
    }

    /**
     * Merges two file paths into one
     *
     * @param string $original_path
     * @param string $merge_path
     * @return string
     */
    protected function mergePaths($original_path, $merge_path)
    {
        // absolute merge path overwrites original path
        if (substr($merge_path, 0, 1) == self::$PATH_SEP) {
            return $merge_path;
        }

        // empty merge path has no effect on original path
        if (strlen($merge_path) == 0) {
            return $original_path;
        }

        $original_path_array = explode(self::$PATH_SEP, $original_path);
        $merge_path_array = explode(self::$PATH_SEP, $merge_path);

        // discard original file name
        array_pop($original_path_array);

        foreach ($merge_path_array as $path_part) {
            switch ($path_part) {
                case self::$PATH_CURRENT:
                {
                    break;
                }

                case self::$PATH_UPPER:
                {
                    array_pop($original_path_array);
                    break;
                }

                default:
                {
                    array_push($original_path_array, $path_part);
                }
            }
        }

        return implode(self::$PATH_SEP, $original_path_array);
    }

// === QUERY HELPERS ==============================================================================

    /**
     * Builds query from given url query array
     *
     * @param array $url_query_array
     * @param bool $html
     * @return string
     */
    protected function buildQuery($url_query_array, $html = true)
    {
        $query_array = [];

        // build key-value pairs (e.g. a=1) and store them in temp array
        foreach ($url_query_array as $key => $value) {
            $query_array[] = $key . (strlen($value) > 0 ? self::$QUERY_KV_SEP . $value : '');
        }

        // build query string by joining elements of temp array
        return implode($html ? self::$QUERY_AMP_SEP : self::$QUERY_SEP, $query_array);
    }

    /**
     * Parses query into array
     * This method is necessary because parse_str does not really work
     *
     * @param string $query
     * @param bool $overwrite
     * @return array
     */
    protected function parseQuery($query, $overwrite = false)
    {
        $result = [];

        // empty query evaluates in empty query array
        if (strlen($query) == 0) {
            return $result;
        }

        // replace all &amp; with & before parsing query string
        $query = str_replace(self::$QUERY_AMP_SEP, self::$QUERY_SEP, $query);

        // remove question mark before splitting into query parts
        $query_list = explode(
            self::$QUERY_SEP,
            substr($query, 0, 1) == self::$QUERY_PRE ? substr($query, 1) : $query
        );

        // read each key value pair from query list
        foreach ($query_list as $param) {
            $key_value = explode(self::$QUERY_KV_SEP, $param);

            if ($overwrite || !isset($result[$key_value[0]])) {
                $result[$key_value[0]] = @$key_value[1];
            }
        }

        return $result;
    }

// === HELPERS ====================================================================================

    /**
     * Tests if URL is complete or just host relative
     *
     * @param string $url
     * @return bool
     */
    public static function isComplete($url)
    {
        return preg_match(self::$SCHEME_REGEX, $url);
    }

    /**
     * Sets utility url
     *
     * @param string $url
     * @return string
     */
    public static function setUtilityURL($url)
    {
        // store previous utility url for later use
        $previous_url = self::$UTILITY_URL;

        // set utility url to desired value
        self::$UTILITY_URL = $url;

        // return previous utility url
        return $previous_url;
    }

    /**
     * Gets utility url
     *
     * @param void
     * @return string
     */
    public static function getUtilityURL()
    {
        return self::$UTILITY_URL;
    }

}
