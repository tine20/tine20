<?php

class Voipmanager_Snom_XML_IPPhoneInput extends DOMDocument
{
    public function __construct()
    {
        parent::__construct('1.0', 'utf-8');

        $this->appendChild($this->createElement('SnomIPPhoneText'));
    }

    public function setText($text)
    {
        $text = $this->documentElement->appendChild($this->createElement('Text', $text));

        return $text;
    }

    public function addSoftKeyItem($name)
    {
        $softKeyItem = $this->documentElement->appendChild($this->createElement('SoftKeyItem'));

        $softKeyItem->appendChild($this->createElement('Name', $name));

        return $softKeyItem;
    }
    
    /**
     * @see DOMDocument::createElement()
     * @return DOMElement
     */
    public function createElement($name, $value = null, $uri = null)
    {
        switch ($name) {
            case 'SoftKeyItem':
                $element = new Voipmanager_Snom_XML_SoftKeyItem($name, $value, $uri);
                break;
        
            default:
                $element = new DOMElement($name, $value, $uri);
        }
    
        $docFragment = $this->createDocumentFragment();
        $docFragment->appendChild($element); 
        
        $element = $docFragment->removeChild($element);
        
        return $element;
    }
}
