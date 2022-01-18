<?php

/*
 * This file is part of marcellosendos/php-utility
 *
 * (c) Marcel Oehler <mo@marcellosendos.ch>
 */

namespace Marcellosendos\PhpUtility\Utility;

class SerializeUtility
{
    /**
     * @param string $str
     * @param string $type
     * @return mixed
     */
    public static function unserialize($str, &$type)
    {
        $result = @unserialize($str);

        if (!is_null($result) && $result !== false) {
            $type = 'serialize';

            return $result;
        }

        if (function_exists('igbinary_unserialize')) {
            $result = @igbinary_unserialize($str);

            if (!is_null($result) && $result !== false) {
                $type = 'igbinary';

                return $result;
            }
        }

        return false;
    }

    /**
     * @param mixed $value
     * @param string $type
     * @return string
     */
    public static function serialize($value, $type = '')
    {
        switch ($type) {
            case 'igbinary':
            {
                if (function_exists('igbinary_serialize')) {
                    return igbinary_serialize($value);
                }

                break;
            }

            case 'serialize':
            {
                return serialize($value);
            }
        }

        return serialize($value);
    }

    /**
     * @param string $str
     * @param string $type
     * @return mixed
     */
    public static function decode($str, &$type)
    {
        $result = @json_decode($str, true);

        if (!is_null($result) && $result !== false) {
            $type = 'json';

            return $result;
        }

        return self::unserialize($str, $type);
    }

    /**
     * @param mixed $value
     * @param string $type
     * @return string
     */
    public static function encode($value, $type = '')
    {
        switch ($type) {
            case 'igbinary':
            {
                if (function_exists('igbinary_serialize')) {
                    return igbinary_serialize($value);
                }

                break;
            }

            case 'serialize':
            {
                return serialize($value);
            }

            case 'json':
            {
                return json_encode($value);
            }
        }

        return json_encode($value);
    }
}
