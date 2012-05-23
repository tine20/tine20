<?php

class Voipmanager_Snom_XML_SoftKeyItem extends DOMElement
{
    public function setLabel($text)
    {
        $text = $this->appendChild($this->ownerDocument->createElement('Label', $text));

        return $text;
    }

    public function setSoftKey($keyName)
    {
        $softKeyElement = $this->appendChild($this->ownerDocument->createElement('Softkey', $keyName));
        
        return $softKeyElement;
    }

    public function setURL($url)
    {
        $urlElement = $this->ownerDocument->createElement('URL');
        
        $urlElement->appendChild($this->ownerDocument->createTextNode($url));
        $this->appendChild($urlElement);
        
        return $urlElement;
    }
}
