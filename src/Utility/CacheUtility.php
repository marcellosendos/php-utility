<?php

/*
 * This file is part of marcellosendos/php-utility
 *
 * (c) Marcel Oehler <mo@marcellosendos.ch>
 */

namespace Marcellosendos\PhpUtility\Utility;

class CacheUtility
{
    /**
     * @param mixed $parameter
     * @return string
     */
    public static function uniqueCacheIdentifier($parameter)
    {
        return sha1(serialize($parameter));
    }

    /**
     * @param string $cacheTag
     * @return string
     */
    public static function normalizeCacheTag($cacheTag)
    {
        return preg_replace('/[^a-z0-9]/', '_', strtolower($cacheTag));
    }

    /**
     * @param array $cacheTags
     * @return array
     */
    public static function normalizeCacheTags($cacheTags)
    {
        $result = [];

        foreach ($cacheTags as $cacheTag) {
            if (is_string($cacheTag) && strlen($cacheTag) > 0) {
                $result[] = self::normalizeCacheTag($cacheTag);
            }
        }

        return array_unique($result);
    }

    /**
     * @param int $cacheTime
     * @return bool
     */
    public static function validateCacheTime(&$cacheTime)
    {
        // cache given time (> 0) or not (< 0)
        // indefinite caching (= 0) is deactivated
        if (is_numeric($cacheTime)) {
            $cacheTime = intval($cacheTime);

            if ($cacheTime == 0) {
                $cacheTime = -1;
            }

            return ($cacheTime > 0);
        }

        // cache default time
        if (empty($cacheTime)) {
            $cacheTime = null;

            return true;
        }

        // do not cache
        $cacheTime = -1;

        return false;
    }

    /**
     * @param mixed $cacheContent
     * @param int $minLen
     * @return bool
     */
    public static function validateCacheContent(&$cacheContent, $minLen = 0)
    {
        if (is_null($cacheContent)) {
            return false;
        }

//        if (is_bool($cacheContent)) {
//            return $cacheContent;
//        }

        if (is_string($cacheContent)) {
            return (strlen(trim($cacheContent)) > $minLen);
        }

//        if (is_numeric($cacheContent)) {
//            return ($cacheContent > 0);
//        }

//        if (is_array($cacheContent)) {
//            return (count($cacheContent) > 0);
//        }

        return (!empty($cacheContent));
    }
}
