<?php

/*
 * This file is part of marcellosendos/php-utility
 *
 * (c) Marcel Oehler <mo@marcellosendos.ch>
 */

namespace Marcellosendos\PhpUtility\Utility;

/**
 * TextUtility
 */
class TextUtility
{
    /**
     * @param string $txt
     * @return string
     */
    public static function normalize($txt)
    {
        if (!is_string($txt) || strlen($txt) == 0) {
            return '';
        }

        return preg_replace('/[^a-zA-Z0-9]+/', '_', $txt);
    }

    /**
     * @param string $txt
     * @return string
     */
    public static function firstcharLowercase($txt)
    {
        if (!is_string($txt) || strlen($txt) == 0) {
            return '';
        }

        return strtolower(self::umlaut2ascii(substr($txt, 0, 1)));
    }

    /**
     * @param string $txt
     * @return string
     */
    public static function umlaut2ascii($txt)
    {
        if (!is_string($txt) || strlen($txt) == 0) {
            return '';
        }

        return strtr(
            utf8_decode($txt),
            utf8_decode('ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ'),
            'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy'
        );
    }

    /**
     * @param string $txt
     * @return string
     */
    public static function compressed_base64_encode($txt)
    {
        if (!is_string($txt) || strlen($txt) == 0) {
            return '';
        }

        return strtr(base64_encode(addslashes(gzdeflate($txt, 9))), '+/=', '-_.');
    }

    /**
     * @param string $txt
     * @return string
     */
    public static function compressed_base64_decode($txt)
    {
        if (!is_string($txt) || strlen($txt) == 0) {
            return '';
        }

        return gzinflate(stripslashes(base64_decode(strtr($txt, '-_.', '+/='))));
    }

    /**
     * @param string $txt
     * @param string $prep
     * @param string $app
     * @return string
     */
    public static function trimPrependAppend($txt, $prep = '', $app = '')
    {
        if (!is_string($txt) || strlen($txt) == 0) {
            return '';
        }

        return ($prep . trim($txt) . $app);
    }

    /**
     * @param mixed $words
     * @return array
     */
    public static function wordcapsVariants($words)
    {
        if (is_string($words) && strlen($words) > 0) {
            $words = explode(' ', $words);
        }

        if (!is_array($words) || count($words) == 0) {
            return [];
        }

        $result = [];
        $word = array_pop($words);

        if (count($words) > 0) {
            $variants = self::wordcapsVariants($words);

            foreach ($variants as $variant) {
                $result[] = $variant . ' ' . ucfirst($word);
                $result[] = $variant . ' ' . lcfirst($word);
            }
        } else {
            $result[] = ucfirst($word);
            $result[] = lcfirst($word);
        }

        return $result;
    }

    /**
     * @param string $txt
     * @return array
     */
    public static function wordList($txt)
    {
        return ListUtility::emptyExplode(' ', $txt);
    }

    /**
     * @param string $txt
     * @return int
     */
    public static function wordCount($txt)
    {
        return count(self::wordList($txt));
    }

    /**
     * @param string $txt
     * @param int $count
     * @param string $app
     * @return string
     */
    public static function wordTruncate($txt, $count, $app = null)
    {
        if (!is_string($txt) || strlen($txt) == 0) {
            return '';
        }

        if (!is_int($count) || $count < 1) {
            return $txt;
        }

        $words = self::wordList($txt);

        if (count($words) < $count) {
            return $txt;
        }

        if (!is_scalar($app)) {
            $app = '';
        }

        return (implode(' ', array_slice($words, 0, $count)) . $app);
    }
}
