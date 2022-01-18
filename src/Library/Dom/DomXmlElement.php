<?php

/*
 * This file is part of marcellosendos/php-utility
 *
 * (c) Marcel Oehler <mo@marcellosendos.ch>
 */

namespace Marcellosendos\PhpUtility\Library\Dom;

class DomXmlElement
{

// --- RUNTIME ------------------------------------------------------------------------------------

    /**
     * @var  string
     */
    protected $NAME = '';

    /**
     * @var  string
     */
    protected $DATA = '';

    /**
     * @var  bool
     */
    protected $CDATA = false;

    /**
     * @var  array
     */
    protected $ATTRIBUTES = [];

    /**
     * @var  DomXmlElement[]
     */
    protected $CHILDREN = [];

// === CLASS ======================================================================================

    /**
     * @param string $name
     * @param string $data
     * @param array $attributes
     * @param DomXmlElement[] $children
     */
    public function __construct($name = '', $data = '', $attributes = [], $children = [])
    {
        $this->setElement($name, $data, $attributes, $children);
    }

// === INPUT / OUTPUT =============================================================================

    /**
     * @param string $name
     * @param string $data
     * @param array $attributes
     * @param DomXmlElement[] $children
     * @return void
     */
    public function setElement($name = '', $data = '', $attributes = [], $children = [])
    {
        if (is_string($name) && strlen($name) > 0) {
            $this->NAME = $name;
        }

        if (is_scalar($data) && strlen($data) > 0) {
            $this->DATA = $data;
        }

        if (is_array($attributes) && count($attributes) > 0) {
            $this->ATTRIBUTES = $attributes;
        }

        if (is_array($children) && count($children) > 0) {
            $this->CHILDREN = $children;
        }
    }

    /**
     * @param array $element
     * @return void
     */
    public function setElementList($element)
    {
        $name = isset($element[DomXmlArray::TAG_NAME]) ? $element[DomXmlArray::TAG_NAME] : '';
        $data = isset($element[DomXmlArray::TAG_DATA]) ? $element[DomXmlArray::TAG_DATA] : '';
        $attributes = isset($element[DomXmlArray::TAG_ATTRIBUTES]) ? $element[DomXmlArray::TAG_ATTRIBUTES] : [];
        $children = isset($element[DomXmlArray::TAG_CHILDREN]) ? $element[DomXmlArray::TAG_CHILDREN] : [];

        $this->setElement($name, $data, $attributes, $children);
    }

    /**
     * @param void
     * @return array
     */
    public function getElementList()
    {
        return self::buildElementList($this->NAME, $this->DATA, $this->ATTRIBUTES, $this->CHILDREN);
    }

    /**
     * @param string $name
     * @param string $data
     * @param array $attributes
     * @param array $children
     * @return array
     */
    public static function buildElementList($name = '', $data = '', $attributes = [], $children = [])
    {
        $result = [];

        if (is_string($name) && strlen($name) > 0) {
            $result[DomXmlArray::TAG_NAME] = $name;

            if (is_scalar($data) && strlen($data) > 0) {
                $result[DomXmlArray::TAG_DATA] = $data;
            }

            if (is_array($attributes) && count($attributes) > 0) {
                $result[DomXmlArray::TAG_ATTRIBUTES] = $attributes;
            }

            if (is_array($children) && count($children) > 0) {
                $result[DomXmlArray::TAG_CHILDREN] = $children;
            }
        }

        return $result;
    }

// === SET / GET / CHECK ==========================================================================

    /**
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->NAME = $name;
    }

    /**
     * @param void
     * @return string
     */
    public function getName()
    {
        return $this->NAME;
    }

    /**
     * @param void
     * @return bool
     */
    public function hasName()
    {
        return (is_string($this->NAME) && strlen($this->NAME) > 0);
    }

    /**
     * @param string $data
     * @return void
     */
    public function setData($data)
    {
        $this->DATA = $data;
    }

    /**
     * @param void
     * @return string
     */
    public function getData()
    {
        return $this->DATA;
    }

    /**
     * @param void
     * @return bool
     */
    public function hasData()
    {
        return (is_scalar($this->DATA) && strlen($this->DATA) > 0);
    }

    /**
     * @param bool $cdata
     * @return void
     */
    public function setCData($cdata)
    {
        $this->CDATA = $cdata;
    }

    /**
     * @return bool
     */
    public function isCData()
    {
        return $this->CDATA;
    }

    /**
     * @param string $key
     * @param string $value
     * @return void
     */
    public function setAttribute($key, $value)
    {
        if (is_null($value)) {
            unset($this->ATTRIBUTES[$key]);
        } else {
            $this->ATTRIBUTES[$key] = $value;
        }
    }

    /**
     * @param string $key
     * @return string
     */
    public function getAttribute($key)
    {
        return (isset($this->ATTRIBUTES[$key]) ? $this->ATTRIBUTES[$key] : '');
    }

    /**
     * @param string $key
     * @return bool
     */
    public function hasAttribute($key)
    {
        return isset($this->ATTRIBUTES[$key]);
    }

    /**
     * @param array $attributes
     * @return void
     */
    public function setAttributes($attributes)
    {
        $this->ATTRIBUTES = $attributes;
    }

    /**
     * @param void
     * @return array
     */
    public function getAttributes()
    {
        return $this->ATTRIBUTES;
    }

    /**
     * @param void
     * @return bool
     */
    public function hasAttributes()
    {
        return (is_array($this->ATTRIBUTES) && count($this->ATTRIBUTES) > 0);
    }

    /**
     * @param DomXmlElement $child
     * @return void
     */
    public function addChild($child)
    {
        $this->CHILDREN[] = $child;
    }

    /**
     * @param DomXmlElement[] $children
     * @return void
     */
    public function setChildren($children)
    {
        $this->CHILDREN = $children;
    }

    /**
     * @param void
     * @return DomXmlElement[]
     */
    public function getChildren()
    {
        return $this->CHILDREN;
    }

    /**
     * @param void
     * @return bool
     */
    public function hasChildren()
    {
        return (is_array($this->CHILDREN) && count($this->CHILDREN) > 0);
    }

// === PLAIN TEXT =================================================================================

    /**
     * @param void
     * @return string
     */
    public function getText()
    {
        return $this->elementText($this);
    }

    /**
     * @param DomXmlElement $element
     * @return string
     */
    protected function elementText($element)
    {
        $result = '';

        if ($element->hasData()) {
            $result .= $element->getData();
        }

        if ($element->hasChildren()) {
            foreach ($element->getChildren() as $child) {
                $result .= $this->elementText($child);
            }
        }

        return $result;
    }

}
