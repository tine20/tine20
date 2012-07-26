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

class Syncroton_Model_SyncCollection
{
    protected $_collection = array();
    
    protected $_xmlCollection;
    
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
            throw new InvalidArgumentException('no collection xml element set');
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
            throw new InvalidArgumentException('no collection xml element set');
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
            throw new InvalidArgumentException('no collection xml element set');
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
            throw new InvalidArgumentException('no collection xml element set');
        }
        
        return isset($this->_xmlCollection->Commands->Fetch);
    }
    
    public function setFromArray(array $properties)
    {
        $this->_collection = array();
    
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
     * @param SimpleXMLElement $xmlCollection
     * @throws InvalidArgumentException
     */
    public function setFromSimpleXMLElement(SimpleXMLElement $xmlCollection)
    {
        if ($xmlCollection->getName() !== 'Collection') {
            throw new InvalidArgumentException('Unexpected element name: ' . $xmlCollection->getName());
        }
        
        $this->_xmlCollection = $xmlCollection;
        
        $this->_collection = array(
            'syncKey'          => (int)$xmlCollection->SyncKey,
            'collectionId'     => (string)$xmlCollection->CollectionId,
            'deletesAsMoves'   => isset($xmlCollection->DeletesAsMoves)   && (string)$xmlCollection->DeletesAsMoves   === '0' ? false : true,
            'conversationMode' => isset($xmlCollection->ConversationMode) && (string)$xmlCollection->ConversationMode === '0' ? false : true,
            'getChanges'       => isset($xmlCollection->GetChanges) ? true : false,
            'windowSize'       => isset($xmlCollection->WindowSize) ? (int)$xmlCollection->WindowSize : 100,
            'class'            => isset($xmlCollection->Class) ? (string)$xmlCollection->Class : null,
            
            'syncState'        => null,
            'folder'           => null
        );
        
        if (isset($xmlCollection->Supported)) {
            // @todo collected supported elements
        }
        
        $this->_collection['filterType']      = Syncroton_Command_Sync::FILTER_NOTHING;
        $this->_collection['mimeSupport']     = Syncroton_Command_Sync::MIMESUPPORT_DONT_SEND_MIME;
        $this->_collection['mimeTruncation']  = Syncroton_Command_Sync::TRUNCATE_NOTHING;
        $this->_collection['bodyPreferences'] = array();
        
        // process options
        if (isset($xmlCollection->Options)) {
            // optional parameters
            if (isset($xmlCollection->Options->FilterType)) {
                $this->_collection['filterType'] = (int)$xmlCollection->Options->FilterType;
            }
            if (isset($xmlCollection->Options->MIMESupport)) {
                $this->_collection['mimeSupport'] = (int)$xmlCollection->Options->MIMESupport;
            }
            if (isset($xmlCollection->Options->MIMETruncation)) {
                $this->_collection['mimeTruncation'] = (int)$xmlCollection->Options->MIMETruncation;
            }
            if (isset($xmlCollection->Options->Class)) {
                $this->_collection['class'] = (string)$xmlCollection->Options->Class;
            }
            
            // try to fetch element from AirSyncBase:BodyPreference
            $airSyncBase = $xmlCollection->Options->children('uri:AirSyncBase');
        
            if (isset($airSyncBase->BodyPreference)) {
        
                foreach ($airSyncBase->BodyPreference as $bodyPreference) {
                    $type = (int) $bodyPreference->Type;
                    $this->_collection['bodyPreferences'][$type] = array(
                            'type' => $type
                    );
        
                    // optional
                    if (isset($bodyPreference->TruncationSize)) {
                        $this->_collection['bodyPreferences'][$type]['truncationSize'] = (int) $bodyPreference->TruncationSize;
                    }
                }
            }
            
            if (isset($airSyncBase->BodyPartPreference)) {
                // process BodyPartPreference elements
            }
        }
    }
    
    public function &__get($name)
    {
        if (array_key_exists($name, $this->_collection)) {
            return $this->_collection[$name];
        }
        echo $name . PHP_EOL;
        return null; 
    }
    
    public function __set($name, $value)
    {
        $this->_collection[$name] = $value;
    }
    
    public function __isset($name)
    {
        return isset($this->_collection[$name]);
    }
    
    public function __unset($name)
    {
        unset($this->_collection[$name]);
    }
}