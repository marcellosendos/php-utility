<?php

/*
 * This file is part of marcellosendos/php-utility
 *
 * (c) Marcel Oehler <mo@marcellosendos.ch>
 */

namespace Marcellosendos\PhpUtility\Utility;

class ListUtility
{
    /**
     * @param string $delimiter
     * @param string $str
     * @return array
     */
    public static function emptyExplode($delimiter, $str)
    {
        if (!is_string($delimiter) || strlen($delimiter) == 0 || !is_string($str) || strlen($str) == 0) {
            return [];
        }

        return explode($delimiter, $str);
    }

    /**
     * @param string $delimiter
     * @param string $str
     * @param int $pos
     * @return string
     */
    public static function singleExplode($delimiter, $str, $pos = 0)
    {
        $pos = (is_numeric($pos) ? intval($pos) : 0);
        $list = self::emptyExplode($delimiter, $str);

        return (isset($list[$pos]) ? $list[$pos] : '');
    }

    /**
     * @param string $delimiter
     * @param mixed $element
     * @return array
     */
    public static function mixedExplode($delimiter, $element)
    {
        if (is_array($element)) {
            return $element;
        }

        return self::emptyExplode($delimiter, $element);
    }

    /**
     * @param string $glue
     * @param array $list
     * @param array $keys
     * @return string
     */
    public static function multiImplode($glue, $list, $keys)
    {
        if (!is_array($list) || count($list) == 0 || !is_array($keys) || count($keys) == 0) {
            return '';
        }

        if (!is_string($glue)) {
            $glue = '';
        }

        $result = [];

        foreach ($keys as $key) {
            if (isset($list[$key]) && is_string($list[$key]) && strlen($list[$key]) > 0) {
                $result[] = $list[$key];
            }
        }

        return (count($result) == 0 ? '' : implode($glue, $result));
    }

    /**
     * @param array $array1
     * @param array $array2
     * @return void
     */
    public static function arrayAppend(&$array1, $array2)
    {
        if (is_array($array1) && is_array($array2) && count($array2) > 0) {
            foreach ($array2 as $element) {
                $array1[] = $element;
            }
        }
    }

    /**
     * @param array $array1
     * @param array $array2
     * @return void
     */
    public static function arrayAppendAssoc(&$array1, $array2)
    {
        if (is_array($array1) && is_array($array2) && count($array2) > 0) {
            foreach ($array2 as $key => $value) {
                $array1[$key] = $value;
            }
        }
    }

    /**
     * @param array $array
     * @return array
     */
    public static function arrayUniqueTrim($array)
    {
        if (!is_array($array) || count($array) == 0) {
            return [];
        }

        $result = [];

        foreach ($array as $element) {
            if (is_string($element)) {
                $value = trim($element);

                if (strlen($value) > 0) {
                    $key = TextUtility::normalize(strtolower($value));

                    if (!isset($result[$key])) {
                        $result[$key] = $value;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param array $list
     * @param string $key
     * @param string $value
     * @return array
     */
    public static function convertAssociativeList($list, $key = 'key', $value = 'value')
    {
        if (!is_array($list) || count($list) == 0 || !is_string($key) || strlen($key) == 0 || !is_string($value) || strlen($value) == 0) {
            return [];
        }

        $result = [];

        foreach ($list as $element_key => $element_value) {
            $result[] = [
                $key => $element_key,
                $value => $element_value
            ];
        }

        return $result;
    }

    /**
     * @param array $list
     * @param string $key
     * @param string $value
     * @return array
     */
    public static function createAssociativeList($list, $key = 'key', $value = 'value')
    {
        if (!is_array($list) || count($list) == 0 || !is_string($key) || strlen($key) == 0 || !is_string($value) || strlen($value) == 0) {
            return [];
        }

        $result = [];

        foreach ($list as $element) {
            if (isset($element[$key]) && is_string($element[$key]) && isset($element[$value])) {
                $result[$element[$key]] = $element[$value];
            }
        }

        return $result;
    }

    /**
     * @param array $list
     * @return bool
     */
    public static function isAssociative($list)
    {
        if (is_array($list) && count($list) > 0) {
            return (array_keys($list) !== range(0, count($list) - 1));
        }

        return false;
    }

    /**
     * @param array $array1
     * @param array $array2
     * @return bool
     */
    public static function valueMatch($array1, $array2)
    {
        foreach ($array1 as $element1) {
            foreach ($array2 as $element2) {
                if ($element1 === $element2) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array $list
     * @param string $field
     * @param array $groupnames
     * @return array
     */
    public static function groupList($list, $field, $groupnames = null)
    {
        if (!is_array($list) || count($list) == 0) {
            return [];
        }

        if (!is_string($field) || strlen($field) == 0) {
            return $list;
        }

        $groups = [];

        if (is_array($groupnames) && count($groupnames) > 0) {
            foreach ($groupnames as $groupname) {
                $groupkey = sha1(strtolower($groupname));
                $groups[$groupkey] = [];
            }
        }

        foreach ($list as $element) {
            if (!isset($element[$field])) {
                return $list;
            }

            $key = sha1(strtolower($element[$field]));

            if (!isset($groups[$key])) {
                $groups[$key] = [];
            }

            $groups[$key][] = $element;
        }

        $result = [];

        foreach ($groups as $group) {
            for ($i = 0, $len = count($group); $i < $len; $i++) {
                $element = $group[$i];

                $element['_group'] = [
                    'position' => $i,
                    'length' => $len,
                    'isFirst' => ($i == 0),
                    'isLast' => ($i == $len - 1)
                ];

                $result[] = $element;
            }
        }

        return $result;
    }

    /**
     * @param array $list
     * @param mixed $fields
     * @param array $fieldGroupnames
     * @param array $groupinfoFields
     * @return array
     */
    public static function groupListDeep($list, $fields, $fieldGroupnames = null, $groupinfoFields = null)
    {
        if (!is_array($list) || count($list) == 0) {
            return [];
        }

        $fields = self::mixedExplode(',', $fields);

        if (count($fields) == 0) {
            return [];
        }

        $field = array_shift($fields);
        $groups = [];

        if (is_array($fieldGroupnames) && isset($fieldGroupnames[$field])) {
            $groupnames = self::mixedExplode(',', $fieldGroupnames[$field]);

            if (is_array($groupnames) && count($groupnames) > 0) {
                foreach ($groupnames as $groupname) {
                    $groupkey = sha1(strtolower($groupname));
                    $groups[$groupkey] = [];
                }
            }
        }

        $groupinfo = (is_array($groupinfoFields) && isset($groupinfoFields[$field]))
            ? self::mixedExplode(',', $groupinfoFields[$field])
            : [];

        foreach ($list as $element) {
            if (!isset($element[$field])) {
                return [];
            }

            $key = sha1(strtolower($element[$field]));

            if (!isset($groups[$key]['list'])) {
                $groups[$key] = [
                    'field' => $field,
                    'value' => $element[$field],
                    'info' => [$field => $element[$field]],
                    'list' => []
                ];

                if (count($groupinfo) > 0) {
                    foreach ($groupinfo as $infofield) {
                        if (isset($element[$infofield])) {
                            $groups[$key]['info'][$infofield] = $element[$infofield];
                        }
                    }
                }
            }

            if (!isset($element['_groups'])) {
                $element['_groups'] = [];
            }

            $element['_groups'][] = [
                'field' => $groups[$key]['field'],
                'value' => $groups[$key]['value'],
                'info' => $groups[$key]['info']
            ];

            $groups[$key]['list'][] = $element;
        }

        foreach ($groups as $key => $value) {
            if (empty($value['list'])) {
                unset($groups[$key]);
            }
        }

        $glen = count($groups);
        $gpos = 0;

        foreach ($groups as $key => $value) {
            self::arrayAppendAssoc($groups[$key], [
                'position' => $gpos,
                'length' => $glen,
                'isFirst' => ($gpos == 0),
                'isLast' => ($gpos == $glen - 1)
            ]);

            for ($i = 0, $len = count($value['list']); $i < $len; $i++) {
                $groupnum = count($groups[$key]['list'][$i]['_groups']) - 1;

                self::arrayAppendAssoc($groups[$key]['list'][$i]['_groups'][$groupnum], [
                    'position' => $i,
                    'length' => $len,
                    'isFirst' => ($i == 0),
                    'isLast' => ($i == $len - 1)
                ]);
            }

            $gpos++;
        }

        if (count($fields) > 0) {
            foreach ($groups as $key => $value) {
                $groups[$key]['grouped'] = self::groupListDeep($value['list'], $fields, $fieldGroupnames, $groupinfoFields);
            }
        }

        return [
            'groupedbyfield' => $field,
            'groups' => $groups
        ];
    }

    /**
     * @param array $list
     * @param mixed $fields
     * @param array $fieldGroupnames
     * @param array $groupinfoFields
     * @return array
     */
    public static function groupListFlat($list, $fields, $fieldGroupnames = null, $groupinfoFields = null)
    {
        $listDeep = self::groupListDeep($list, $fields, $fieldGroupnames, $groupinfoFields);

        return self::flattenGroupList($listDeep);
    }

    /**
     * @param array $list
     * @return array
     */
    public static function flattenGroupList($list)
    {
        if (!is_array($list) || count($list) == 0) {
            return [];
        }

        $result = [];

        if (!empty($list['groups'])) {
            foreach ($list['groups'] as $element) {
                if (!empty($element['grouped'])) {
                    self::arrayAppend($result, self::flattenGroupList($element['grouped']));
                } elseif (!empty($element['list'])) {
                    self::arrayAppend($result, $element['list']);
                }
            }
        } else {
            self::arrayAppend($result, $list);
        }

        return $result;
    }

    /**
     * @param array $list
     * @param string $sourceField
     * @param string $targetField
     * @param callback $mapFunction
     * @return array
     */
    public static function mapField($list, $sourceField, $targetField, $mapFunction)
    {
        if (!is_array($list) || count($list) == 0) {
            return [];
        }

        if (!is_string($sourceField) || strlen($sourceField) == 0 || !is_string($targetField) || strlen($targetField) == 0) {
            return $list;
        }

        if (!is_callable($mapFunction)) {
            return $list;
        }

        $result = [];

        foreach ($list as $element) {
            if (!isset($element[$sourceField]) || isset($element[$targetField])) {
                return $list;
            }

            $element[$targetField] = call_user_func($mapFunction, $element[$sourceField]);

            $result[] = $element;
        }

        return $result;
    }

    /**
     * @param array $list
     * @param array $map
     * @return array
     */
    public static function mapList($list, $map)
    {
        if (!is_array($list) || count($list) == 0) {
            return [];
        }

        if (!is_array($map) || count($map) == 0) {
            return $list;
        }

        $result = [];

        foreach ($map as $source => $target) {
            $result[$target] = (isset($list[$source]) ? $list[$source] : null);
        }

        return $result;
    }

    /**
     * @param array $list
     * @param array $map
     * @param bool $keepUnmapped
     * @return array
     */
    public static function mapListElements($list, $map, $keepUnmapped = false)
    {
        if (!is_array($list) || count($list) == 0) {
            return [];
        }

        if (!is_array($map) || count($map) == 0) {
            return $list;
        }

        $result = [];

        foreach ($map as $source => $target) {
            if (is_string($source) && strlen($source) > 0 && isset($list[$source])) {
                if (is_string($target) && strlen($target)) {
                    $result[$target] = $list[$source];
                } elseif ($keepUnmapped) {
                    $result[$source] = $list[$source];
                }
            }
        }

        return $result;
    }

    /**
     * @param array $list
     * @param array $path
     * @param mixed $value
     * @param bool $overwrite
     */
    public static function setListPath(&$list, $path, $value, $overwrite = true)
    {
        if (!is_array($list) || !is_array($path)) {
            return;
        }

        if (count($path) == 0 && $overwrite) {
            $list = $value;
        }

        $firstPathElement = array_shift($path);

        if (count($path) == 0) {
            if (!isset($list[$firstPathElement]) || $overwrite) {
                $list[$firstPathElement] = $value;
            }
        } else {
            if (!isset($list[$firstPathElement])) {
                $list[$firstPathElement] = [];
            }

            self::setListPath($list[$firstPathElement], $path, $value, $overwrite);
        }
    }

    /**
     * @param array $list
     * @param array $path
     * @return mixed
     */
    public static function getListPath($list, $path)
    {
        if (!is_array($list) || count($list) == 0 || !is_array($path) || count($path) == 0) {
            return null;
        }

        $firstPathElement = array_shift($path);

        if (isset($list[$firstPathElement])) {
            if (count($path) == 0) {
                return $list[$firstPathElement];
            }

            return self::getListPath($list[$firstPathElement], $path);
        }

        return null;
    }

    /**
     * @param array $list
     * @param array $path
     * @param mixed $value
     */
    public static function appendListPath(&$list, $path, $value)
    {
        if (!is_array($list) || !is_array($path)) {
            return;
        }

        if (count($path) == 0) {
            $list[] = $value;
        }

        $firstPathElement = array_shift($path);

        if (!isset($list[$firstPathElement])) {
            $list[$firstPathElement] = [];
        }

        self::setListPath($list[$firstPathElement], $path, $value);
    }
}
