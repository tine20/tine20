<?php
/**
 * Syncroton
 *
 * @package     Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync event
 *
 * @package     Model
 * @property    string  ContentType
 * @property    string  Data
 */

class Syncroton_Model_FileReference extends Syncroton_Model_AEntry
{
    protected $_xmlBaseElement = 'ApplicationData';
    
    protected $_properties = array(
        'AirSyncBase' => array(
            'contentType' => array('type' => 'string'),
        ),
        'ItemOperations' => array(
            'data'        => array('type' => 'string', 'encoding' => 'base64'),
            'part'        => array('type' => 'number')
        )
    );
        
    /**
     * 
     * @param SimpleXMLElement $xmlCollection
     * @throws InvalidArgumentException
     */
    public function setFromSimpleXMLElement(SimpleXMLElement $properties)
    {
        //do nothing
                
        return;
    }
}