<?php
/**
 * Syncroton
 *
 * @package     Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync contact
 *
 * @package     Model
 */

interface Syncroton_Model_IXMLEntry extends Syncroton_Model_IEntry
{
    /**
     * 
     * @param DOMElement $_domParrent
     * @param Syncroton_Model_IDevice $device
     */
    public function appendXML(DOMElement $_domParrent, Syncroton_Model_IDevice $device);
    
    /**
     * return array of valid properties
     *  
     * @return array
     */
    public function getProperties();
    
    /**
     * 
     * @param SimpleXMLElement $xmlCollection
     * @throws InvalidArgumentException
     */
    public function setFromSimpleXMLElement(SimpleXMLElement $properties);
}