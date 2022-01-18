<?php

/*
 * This file is part of marcellosendos/php-utility
 *
 * (c) Marcel Oehler <mo@marcellosendos.ch>
 */

namespace Marcellosendos\PhpUtility\Utility;

class DownloadUtility
{
    /**
     * @param string $filepath
     * @param string $filename
     * @param string $contentType
     * @param bool $download
     * @return void
     */
    public static function outputFile($filepath, $filename = null, $contentType = null, $download = false)
    {
        if (!is_readable($filepath)) {
            self::pageNotFoundAndExit();
        }

        if (empty($filename)) {
            $filename = basename($filepath);
        }

        self::sendHeaders($filename, $contentType, filesize($filepath), $download);
        @readfile($filepath);

        exit;
    }

    /**
     * @param string $content
     * @param string $filename
     * @param string $contentType
     * @param bool $download
     * @return void
     */
    public static function outputContent($content, $filename, $contentType = null, $download = false)
    {
        self::sendHeaders($filename, $contentType, strlen($content), $download);
        echo $content;

        exit;
    }

    /**
     * @param string $filename
     * @param string $contentType
     * @param int $contentLength
     * @param bool $download
     * @return void
     */
    public static function sendHeaders($filename = null, $contentType = null, $contentLength = 0, $download = false)
    {
        $headers = self::buildHeaders($filename, $contentType, $contentLength, $download);

        foreach ($headers as $key => $value) {
            header($key . ': ' . $value);
        }
    }

    /**
     * @param string $filename
     * @param string $contentType
     * @param int $contentLength
     * @param bool $download
     * @return array
     */
    public static function buildHeaders($filename = null, $contentType = null, $contentLength = 0, $download = false)
    {
        $headers = [
            'Pragma' => 'public',
            'Expires' => '0',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-Description' => 'File Transfer',
            'Content-Transfer-Encoding' => 'binary',
            'Content-Disposition' => ($download ? 'attachment' : 'inline') . (empty($filename) ? '' : '; filename="' . $filename . '"')
        ];

        if (empty($contentType) && !empty($filename)) {
            $contentType = MimeTypeUtility::getType(FileUtility::getExtension($filename));
        }

        if (is_string($contentType) && strlen($contentType) > 0) {
            $headers['Content-Type'] = $contentType;
        }

        if (is_int($contentLength) && $contentLength > 0) {
            $headers['Content-Length'] = $contentLength;
        }

        return $headers;
    }

    /**
     * @return void
     */
    public static function pageNotFoundAndExit()
    {
        $html = self::buildPageNotFound();

        header('HTTP/1.1 404 Not Found');
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Length: ' . strlen($html));

        echo $html;
        exit;
    }

    /**
     * @param string $title
     * @param string $text
     * @return string
     */
    public static function buildPageNotFound($title = null, $text = null)
    {
        if (!is_string($title) || strlen($title) == 0) {
            $title = 'Not Found';
        }

        if (!is_string($text) || strlen($text) == 0) {
            $text = 'The requested resource was not found on this server.';
        }

        return '<!DOCTYPE HTML><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>404 Not Found</title></head><body><h1>' . $title . '</h1><p>' . $text . '</p></body></html>';
    }
}
