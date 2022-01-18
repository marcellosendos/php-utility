<?php

/*
 * This file is part of marcellosendos/php-utility
 *
 * (c) Marcel Oehler <mo@marcellosendos.ch>
 */

namespace Marcellosendos\PhpUtility\Library\Dom;

use DOMDocument;
use DOMElement;

class DomXmlArray
{

// --- CONSTANTS ----------------------------------------------------------------------------------

    const TAG_NAME = 'name';
    const TAG_ATTRIBUTES = 'attributes';
    const TAG_DATA = 'data';
    const TAG_CHILDREN = 'children';
    const TAG_TEXT = '#text';

// --- SETTINGS -----------------------------------------------------------------------------------

    /**
     * @var string
     */
    protected $VERSION = '1.0';

    /**
     * @var string
     */
    protected $ENCODING = 'utf-8';

// --- RUNTIME ------------------------------------------------------------------------------------

    /**
     * @var DOMDocument|null
     */
    protected $DOC = null;

    /**
     * @var array
     */
    protected $PROCESSING = [];

    /**
     * @var array
     */
    protected $ARRAY = [];

    /**
     * @var string
     */
    protected $XML = '';

// === CLASS ======================================================================================

    /**
     * @param string $version
     * @param string $encoding
     */
    public function __construct($version = '', $encoding = '')
    {
        $this->setVersion($version);
        $this->setEncoding($encoding);
    }

// === SETTINGS ===================================================================================

    /**
     * @param string $version
     * @return void
     */
    public function setVersion($version)
    {
        if (is_string($version) && strlen($version) > 0) {
            $this->VERSION = $version;
        }
    }

    /**
     * @param string $encoding
     * @return void
     */
    public function setEncoding($encoding)
    {
        if (is_string($encoding) && strlen($encoding) > 0) {
            $this->ENCODING = $encoding;
        }
    }

// === STYLESHEETS ================================================================================

    /**
     * @param string $href
     * @param string $type
     * @return void
     */
    public function addStylesheet($href, $type)
    {
        $this->PROCESSING[] = [
            'target' => 'xml-stylesheet',
            'data' => 'href="' . $href . '" type="' . $type . '"'
        ];
    }

// === XML ========================================================================================

    /**
     * @param string $xml
     * @return void
     */
    public function setXML($xml)
    {
        if (is_string($xml) && strlen($xml) > 0) {
            $this->XML = $xml;
        }
    }

    /**
     * @param void
     * @return string
     */
    public function getXML()
    {
        return $this->XML;
    }

// === ARRAY ======================================================================================

    /**
     * @param array|object $array
     * @return void
     */
    public function setArray($array)
    {
        if ((is_array($array) && count($array) > 0) || $array instanceof DomXmlElement) {
            $this->ARRAY = $array;
        }
    }

    /**
     * @param void
     * @return array
     */
    public function getArray()
    {
        return $this->ARRAY;
    }

// === XML ELEMENT LIST ===========================================================================

    /**
     * @param void
     * @return DomXmlElement[]
     */
    public function getXMLElementList()
    {
        return $this->generateXMLElementList($this->ARRAY);
    }

    /**
     * @param array $list
     * @return DomXmlElement[]
     */
    protected function generateXMLElementList($list)
    {
        $result = [];

        foreach ($list as $element) {
            if (isset($element[self::TAG_CHILDREN])) {
                $element[self::TAG_CHILDREN] = $this->generateXMLElementList($element[self::TAG_CHILDREN]);
            }

            $XMLElement = new DomXmlElement();
            $XMLElement->setElementList($element);

            $result[] = $XMLElement;
        }

        return $result;
    }

// === XML TO ARRAY ===============================================================================

    /**
     * @param void
     * @return bool
     */
    public function generateArray()
    {
        $this->PROCESSING = [];
        $this->ARRAY = [];

        $this->DOC = new DOMDocument();

        if ($this->DOC->loadXML($this->XML) === false) {
            return false;
        }

        $this->VERSION = $this->DOC->version;
        $this->ENCODING = $this->DOC->encoding;

        foreach ($this->DOC->childNodes as $child) {
            switch ($child->nodeType) {
                case XML_ELEMENT_NODE:
                {
                    $this->ARRAY[] = $this->parseXMLElementNode($child);
                    break;
                }

                case XML_PI_NODE:
                {
                    $this->PROCESSING[] = ['target' => $child->target, 'data' => $child->data];
                    break;
                }
            }
        }

        return true;
    }

    /**
     * @param DOMElement $node
     * @return array
     */
    protected function parseXMLElementNode($node)
    {
        $name = $node->nodeName;
        $data = '';
        $attributes = [];
        $children = [];

        if ($node->hasAttributes()) {
            foreach ($node->attributes as $attribute) {
                $attributes[$attribute->name] = $attribute->value;
            }
        }

        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                switch ($child->nodeType) {
                    case XML_ELEMENT_NODE:
                    {
                        $children[] = $this->parseXMLElementNode($child);
                        break;
                    }

                    case XML_TEXT_NODE:
                    case XML_CDATA_SECTION_NODE:
                    {
                        $value = $child->nodeValue;

                        if (strlen(trim($value)) > 0) {
                            $children[] = DomXmlElement::buildElementList(self::TAG_TEXT, $value);
                        }

                        break;
                    }
                }
            }
        }

        if (count($children) == 1) {
            $onlychild = $children[0];

            if ($onlychild[self::TAG_NAME] == self::TAG_TEXT) {
                $data = $onlychild[self::TAG_DATA];
                $children = [];
            }
        }

        return DomXmlElement::buildElementList($name, $data, $attributes, $children);
    }

// === ARRAY TO XML ===============================================================================

    /**
     * @param void
     * @return bool
     */
    public function generateXML()
    {
        $this->XML = '';

        $this->DOC = new DOMDocument($this->VERSION, $this->ENCODING);
        $this->DOC->formatOutput = true;

        foreach ($this->PROCESSING as $instruction) {
            $this->DOC->appendChild($this->DOC->createProcessingInstruction($instruction['target'], $instruction['data']));
        }

        foreach ($this->ARRAY as $element) {
            $elementNode = $this->createXMLElementNode($element);

            if ($elementNode instanceof DOMElement) {
                $this->DOC->appendChild($elementNode);
            }
        }

        $this->XML = $this->DOC->saveXML();

        return true;
    }

    /**
     * @param DomXmlElement|array $element
     * @return DOMElement
     */
    protected function createXMLElementNode($element)
    {
        if ($element instanceof DomXmlElement) {
            /* @var DomXmlElement $XMLElement */
            $XMLElement = $element;
        } elseif (is_array($element) && count($element) > 0) {
            $XMLElement = new DomXmlElement();
            $XMLElement->setElementList($element);
        } else {
            return null;
        }

        $node = null;

        if ($XMLElement->hasName()) {
            $node = $this->DOC->createElement($XMLElement->getName());

            $this->appendDataNode($node, $XMLElement);

            if ($XMLElement->hasAttributes()) {
                foreach ($XMLElement->getAttributes() as $name => $value) {
                    $node->setAttribute($name, $value);
                }
            }

            if ($XMLElement->hasChildren()) {
                foreach ($XMLElement->getChildren() as $child) {
                    if ($child instanceof DomXmlElement) {
                        /* @var DomXmlElement $XMLChild */
                        $XMLChild = $child;
                    } elseif (is_array($child) && count($child) > 0) {
                        $XMLChild = new DomXmlElement();
                        $XMLChild->setElementList($child);
                    } else {
                        continue;
                    }

                    if ($XMLChild->getName() == self::TAG_TEXT) {
                        $this->appendDataNode($node, $XMLElement);
                    } else {
                        $childNode = $this->createXMLElementNode($child);

                        if ($childNode instanceof DOMElement) {
                            $node->appendChild($childNode);
                        }
                    }
                }
            }
        }

        return $node;
    }

    /**
     * @param DOMElement $node
     * @param DomXmlElement $XMLElement
     */
    protected function appendDataNode(&$node, $XMLElement)
    {
        if ($XMLElement->hasData()) {
//            $data = $XMLElement->getData();
//            $dataNode = $XMLElement->isCData() ? $this->DOC->createCDATASection($data) : $this->DOC->createTextNode($data);
//            $node->appendChild($dataNode);

            if ($XMLElement->isCData()) {
                $node->appendChild($this->DOC->createCDATASection($XMLElement->getData()));
            } else {
                $node->appendChild($this->DOC->createTextNode($XMLElement->getData()));
            }
        }
    }
}
