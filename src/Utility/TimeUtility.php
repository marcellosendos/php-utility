<?php

/*
 * This file is part of marcellosendos/php-utility
 *
 * (c) Marcel Oehler <mo@marcellosendos.ch>
 */

namespace Marcellosendos\PhpUtility\Utility;

use DateTime;
use Exception;

class TimeUtility
{
    const YEAR_REGEX = '/^([0-9]{4})$/';
    const YEARMONTH_REGEX = '/^([0-9]{4})[_\-\.\/]?([0-9]{2})$/';

    /**
     * @param string $date
     * @param int $offset
     * @return DateTime
     */
    public static function startDate($date = null, $offset = null)
    {
        $dateTime = self::dateObject($date);
        $dateTime->modify('midnight');

        if (is_numeric($offset)) {
            $dateTime->modify(intval($offset) . ' day');
        }

        return $dateTime;
    }

    /**
     * @param string $date
     * @param int $offset
     * @return DateTime
     */
    public static function endDate($date = null, $offset = null)
    {
        $dateTime = self::dateObject($date);

        if (is_string($date) && strlen($date) > 0) {
//            if (preg_match(self::$YEAR_REGEX, $date, $matches)) {
//                // last day of year
//                $dateTime->setDate($matches[1], 12, 31);
//                //$dateTime->setDate($matches[1], 1, 1)->modify('+1 year')->modify('-1 day');
//            } elseif (preg_match(self::$YEARMONTH_REGEX, $date, $matches)) {
//                // last day of month
//                $dateTime->setDate($matches[1], $matches[2], 1)->modify('+1 month')->modify('-1 day');
//            }

            if (preg_match(self::YEAR_REGEX, $date)) {
                // last day of year
                $dateTime->modify('+1 year')->modify('-1 day');
            } elseif (preg_match(self::YEARMONTH_REGEX, $date)) {
                // last day of month
                $dateTime->modify('+1 month')->modify('-1 day');
            }
        }

        // one second before midnight
        $dateTime->modify('midnight')->modify('+1 day')->modify('-1 second');

        if (is_numeric($offset)) {
            $dateTime->modify(intval($offset) . ' day');
        }

        return $dateTime;
    }

    /**
     * @param string $date
     * @param int $offset
     * @return DateTime
     */
    public static function dateOffset($date = null, $offset = null)
    {
        $dateTime = self::dateObject($date);
        $dateTime->modify('midnight');

        if (is_numeric($offset)) {
            $dateTime->modify(intval($offset) . ' day');
        }

        return $dateTime;
    }

    /**
     * @param string $date
     * @return DateTime
     */
    public static function dateObject($date = null)
    {
        $dateTime = new DateTime();

        if (!is_null($date)) {
            if (preg_match(self::YEAR_REGEX, $date, $matches)) {
                // first day of year
                $dateTime->setDate($matches[1], 1, 1);
            } elseif (preg_match(self::YEARMONTH_REGEX, $date, $matches)) {
                // first day of month
                $dateTime->setDate($matches[1], $matches[2], 1);
            } else {
                try {
                    // given date
                    $dateTime->modify($date);
                } catch (Exception $e) {
                    // $date was not a valid date or modification
                    $dateTime = new DateTime();
                }
            }
        }

        return $dateTime;
    }

    /**
     * @param DateTime $datetime1
     * @param DateTime $datetime2
     * @return int
     */
    public static function diff($datetime1, $datetime2)
    {
        return ($datetime1->getTimestamp() - $datetime2->getTimestamp());

//        $timestamp1 = $datetime1->getTimestamp();
//        $timestamp2 = $datetime2->getTimestamp();
//
//        if ($timestamp1 > $timestamp2) {
//            return 1;
//        } elseif ($timestamp1 < $timestamp2) {
//            return -1;
//        } else {
//            return 0;
//        }
    }
}
