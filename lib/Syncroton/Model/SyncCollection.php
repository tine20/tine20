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
 * class to handle ActiveSync Sync collection
 *
 * @package     Model
 * @property    string  class
 * @property    string  collectionId
 * @property    bool    deletesAsMoves
 * @property    bool    getChanges
 * @property    string  syncKey
 * @property    int     windowSize
 */

class Syncroton_Model_SyncCollection extends Syncroton_Model_AEntry
{
    protected $_elements = array();
    
    protected $_xmlCollection;
    
    protected $_xmlBaseElement = 'Collection';
    
    public function __construct($properties = null)
    {
        if ($properties instanceof SimpleXMLElement) {
            $this->setFromSimpleXMLElement($properties);
        } elseif (is_array($properties)) {
            $this->setFromArray($properties);
        }
    }
    
    /**
     * return XML element which holds all client Add commands
     * 
     * @return SimpleXMLElement
     */
    public function getClientAdds()
    {
        if (! $this->_xmlCollection instanceof SimpleXMLElement) {
            throw new InvalidArgumentException('no collection xml element set');
        }
        
        return $this->_xmlCollection->Commands->Add;
    }
    
    /**
     * return XML element which holds all client Change commands
     * 
     * @return SimpleXMLElement
     */
    public function getClientChanges()
    {
        if (! $this->_xmlCollection instanceof SimpleXMLElement) {
            throw new InvalidArgumentException('no collection xml element set');
        }
        
        return $this->_xmlCollection->Commands->Change;
    }
    
    /**
     * return XML element which holds all client Delete commands
     * 
     * @return SimpleXMLElement
     */
    public function getClientDeletes()
    {
        if (! $this->_xmlCollection instanceof SimpleXMLElement) {
            throw new InvalidArgumentException('no collection xml element set');
        }
        
        return $this->_xmlCollection->Commands->Delete;
    }
    
    /**
     * return XML element which holds all client Fetch commands
     * 
     * @return SimpleXMLElement
     */
    public function getClientFetches()
    {
        if (! $this->_xmlCollection instanceof SimpleXMLElement) {
            throw new InvalidArgumentException('no collection xml element set');
        }
        
        return $this->_xmlCollection->Commands->Fetch;
    }
    
    /**
     * check if client sent a Add command
     * 
     * @throws InvalidArgumentException
     * @return bool
     */
    public function hasClientAdds()
    {
        if (! $this->_xmlCollection instanceof SimpleXMLElement) {
            return false;
        }
        
        return isset($this->_xmlCollection->Commands->Add);
    }
    
    /**
     * check if client sent a Change command
     * 
     * @throws InvalidArgumentException
     * @return bool
     */
    public function hasClientChanges()
    {
        if (! $this->_xmlCollection instanceof SimpleXMLElement) {
            return false;
        }
        
        return isset($this->_xmlCollection->Commands->Change);
    }
    
    /**
     * check if client sent a Delete command
     * 
     * @throws InvalidArgumentException
     * @return bool
     */
    public function hasClientDeletes()
    {
        if (! $this->_xmlCollection instanceof SimpleXMLElement) {
            return false;
        }
        
        return isset($this->_xmlCollection->Commands->Delete);
    }
    
    /**
     * check if client sent a Fetch command
     * 
     * @throws InvalidArgumentException
     * @return bool
     */
    public function hasClientFetches()
    {
        if (! $this->_xmlCollection instanceof SimpleXMLElement) {
            return false;
        }
        
        return isset($this->_xmlCollection->Commands->Fetch);
    }
    
    public function setFromArray(array $properties)
    {
        $this->_elements = array('options' => array(
            'filterType'      => Syncroton_Command_Sync::FILTER_NOTHING,
            'mimeSupport'     => Syncroton_Command_Sync::MIMESUPPORT_DONT_SEND_MIME,
            'mimeTruncation'  => Syncroton_Command_Sync::TRUNCATE_NOTHING,
            'bodyPreferences' => array()
        ));
    
        foreach($properties as $key => $value) {
            try {
                $this->$key = $value; //echo __LINE__ . PHP_EOL;
            } catch (InvalidArgumentException $iae) {
                //ignore invalid properties
                //echo __LINE__ . PHP_EOL;
            }
        }
    }
    
    /**
     * 
     * @param SimpleXMLElement $properties
     * @throws InvalidArgumentException
     */
    public function setFromSimpleXMLElement(SimpleXMLElement $properties)
    {
        if (!in_array($properties->getName(), (array) $this->_xmlBaseElement)) {
            throw new InvalidArgumentException('Unexpected element name: ' . $properties->getName());
        }
        
        $this->_xmlCollection = $properties;
        
        $this->_elements = array(
            'syncKey'          => (int)$properties->SyncKey,
            'collectionId'     => (string)$properties->CollectionId,
            'deletesAsMoves'   => isset($properties->DeletesAsMoves)   && (string)$properties->DeletesAsMoves   === '0' ? false : true,
            'conversationMode' => isset($properties->ConversationMode) && (string)$properties->ConversationMode === '0' ? false : true,
            'getChanges'       => isset($properties->GetChanges)       && (string) $properties->GetChanges      === '0' ? false : true,
            'windowSize'       => isset($properties->WindowSize) ? (int)$properties->WindowSize : 100,
            'class'            => isset($properties->Class)      ? (string)$properties->Class   : null,
            'options'          => array(
                'filterType'      => Syncroton_Command_Sync::FILTER_NOTHING,
                'mimeSupport'     => Syncroton_Command_Sync::MIMESUPPORT_DONT_SEND_MIME,
                'mimeTruncation'  => Syncroton_Command_Sync::TRUNCATE_NOTHING,
                'bodyPreferences' => array()
            ),
            
            'syncState'        => null,
            'folder'           => null
        );
        
        if (isset($properties->Supported)) {
            // @todo collected supported elements
        }
        
        // process options
        if (isset($properties->Options)) {
            // optional parameters
            if (isset($properties->Options->FilterType)) {
                $this->_elements['options']['filterType'] = (int)$properties->Options->FilterType;
            }
            if (isset($properties->Options->MIMESupport)) {
                $this->_elements['options']['mimeSupport'] = (int)$properties->Options->MIMESupport;
            }
            if (isset($properties->Options->MIMETruncation)) {
                $this->_elements['options']['mimeTruncation'] = (int)$properties->Options->MIMETruncation;
            }
            if (isset($properties->Options->Class)) {
                $this->_elements['options']['class'] = (string)$properties->Options->Class;
            }
            
            // try to fetch element from AirSyncBase:BodyPreference
            $airSyncBase = $properties->Options->children('uri:AirSyncBase');
            
            if (isset($airSyncBase->BodyPreference)) {
                
                foreach ($airSyncBase->BodyPreference as $bodyPreference) {
                    $type = (int) $bodyPreference->Type;
                    $this->_elements['options']['bodyPreferences'][$type] = array(
                        'type' => $type
                    );
                    
                    // optional
                    if (isset($bodyPreference->TruncationSize)) {
                        $this->_elements['options']['bodyPreferences'][$type]['truncationSize'] = (int) $bodyPreference->TruncationSize;
                    }
                }
            }
            
            if (isset($airSyncBase->BodyPartPreference)) {
                // process BodyPartPreference elements
            }
        }
    }
    
    public function toArray()
    {
        $result = array();
        
        foreach (array('syncKey', 'collectionId', 'deletesAsMoves', 'conversationMode', 'getChanges', 'windowSize', 'class', 'options') as $key) {
            if (isset($this->$key)) {
                $result[$key] = $this->$key;
            }
        }
        
        return $result;
    }
    
    public function &__get($name)
    {
        if (array_key_exists($name, $this->_elements)) {
            return $this->_elements[$name];
        }
        //echo $name . PHP_EOL;
        return null; 
    }
    
    public function __set($name, $value)
    {
        $this->_elements[$name] = $value;
    }
}