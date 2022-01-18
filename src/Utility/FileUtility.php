<?php

/*
 * This file is part of marcellosendos/php-utility
 *
 * (c) Marcel Oehler <mo@marcellosendos.ch>
 */

namespace Marcellosendos\PhpUtility\Utility;

class FileUtility
{
    /**
     * @param string $path
     * @return string
     */
    public static function appendSlash($path)
    {
        if (is_string($path) && strlen($path) > 0) {
            return (rtrim($path, '/') . '/');
        }

        return $path;
    }

    /**
     * @param string $path
     * @return string
     */
    public static function prependSlash($path)
    {
        if (is_string($path) && strlen($path) > 0) {
            return ('/' . ltrim($path, '/'));
        }

        return $path;
    }

    /**
     * @param string $path
     * @param bool $all
     * @return string
     */
    public static function postRemoveSlash($path, $all = true)
    {
        if (is_string($path) && strlen($path) > 0) {
            if ($all) {
                return rtrim($path, '/');
            } else {
                $last = strlen($path) - 1;

                if ($path[$last] == '/') {
                    return substr($path, 0, $last);
                }
            }
        }

        return $path;
    }

    /**
     * @param string $path
     * @param bool $all
     * @return string
     */
    public static function preRemoveSlash($path, $all = true)
    {
        if (is_string($path) && strlen($path) > 0) {
            if ($all) {
                return ltrim($path, '/');
            } else {
                if ($path[0] == '/') {
                    return substr($path, 1);
                }
            }
        }

        return $path;
    }

    /**
     * @param string $extension
     * @return string
     */
    public static function prependDot($extension)
    {
        if (is_string($extension) && strlen($extension) > 0) {
            return (substr($extension, 0, 1) == '.' ? $extension : ('.' . $extension));
        }

        return '';
    }

    /**
     * @param string $filename
     * @return string
     */
    public static function getName($filename)
    {
        $basename = self::isUrl($filename) ? self::urlBasename($filename) : basename($filename);

        //return @pathinfo($basename, PATHINFO_FILENAME);

        if (preg_match('/^(.+)\.[^\.]+$/', $basename, $matches)) {
            return $matches[1];
        }

        return $filename;
    }

    /**
     * @param string $filename
     * @param bool $dot
     * @return string
     */
    public static function getExtension($filename, $dot = false)
    {
        $basename = self::isUrl($filename) ? self::urlBasename($filename) : basename($filename);

        //$extension = strtolower(@pathinfo($basename, PATHINFO_EXTENSION));
        //return ($dot ? self::prependDot($extension) : $extension);

        if (preg_match('/^.+\.([^\.]+)$/', $basename, $matches)) {
            $extension = strtolower($matches[1]);

            return ($dot ? self::prependDot($extension) : $extension);
        }

        return '';
    }

    /**
     * @param string $filename
     * @param string $extension
     * @return string
     */
    public static function adjustExtension($filename, $extension)
    {
        if (is_string($extension) && strlen($extension) > 0) {
            return (self::getName($filename) . self::prependDot($extension));
        }

        return $filename;
    }

    /**
     * @param string $name
     * @return string
     */
    public static function normalizeName($name)
    {
        return preg_replace('/[^a-zA-Z0-9\.]+/', '_', urldecode($name));
    }

    /**
     * @param mixed $parameter
     * @param string $name
     * @return string
     */
    public static function uniqueName($parameter = null, $name = null)
    {
        return (sha1(serialize([mt_rand(), $parameter, $name])) . self::getExtension($name, true));
    }

    /**
     * @param int $size
     * @return string
     */
    public static function formatSize($size)
    {
        $units = [' B', ' KB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB'];
        $steps = 1024;

        for ($i = 0; $i < count($units) && $size > $steps; $i++) {
            $size /= $steps;
        }

        return round($size, 1) . $units[$i];
    }

    /**
     * @param string $filepath
     * @return bool
     */
    public static function isUrl($filepath)
    {
        return preg_match('/^http[s]?:\/\//i', $filepath);
        //return preg_match('/^(https?:\/\/)([\da-z\.-]+\.[a-z\.]{2,6})([\/\w \.-]+)*\/?$/i', $filepath);
    }

    /**
     * @param string $url
     * @return string
     */
    public static function urlBasename($url)
    {
        $parsedUrlPath = @parse_url($url, PHP_URL_PATH);

        return (empty($parsedUrlPath) ? $url : basename($parsedUrlPath));
    }

    /**
     * @param string $filepath
     * @return string
     */
    public static function copyFileToTmp($filepath)
    {
        if (is_file($filepath)) {
            $tmppath = '/tmp/' . basename($filepath);

            if (@copy($filepath, $tmppath) !== false && is_file($tmppath)) {
                return $tmppath;
            }
        }

        return $filepath;
    }

    /**
     * @param string $urlFile
     * @return string
     */
    public static function getContents($urlFile)
    {
        if (is_string($urlFile) && strlen($urlFile) > 0) {
            return @file_get_contents($urlFile);
        }

        return '';
    }

    /**
     * @param mixed $list
     * @param string $content
     * @param string $delimiter
     * @param string $glue
     * @return string
     */
    public static function getContentsWithList($list, $content = null, $delimiter = ',', $glue = PHP_EOL)
    {
        $result = (is_string($content) && strlen($content) > 0 ? [$content] : []);
        $urlFileList = ListUtility::mixedExplode($delimiter, $list);

        foreach ($urlFileList as $urlFile) {
            $result[] = self::getContents($urlFile);
        }

        return implode($glue, $result);
    }
}
