<?php

class Voipmanager_Snom_XML_Exit extends DOMDocument
{
    public function __construct()
    {
        parent::__construct('1.0', 'utf-8');

        $this->appendChild($this->createElement('exit'));
    }
}
