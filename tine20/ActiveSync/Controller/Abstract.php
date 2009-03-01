<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * class documentation
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 */
 
abstract class ActiveSync_Controller_Abstract
{
    /**
     * timestamp to use for all sync requests
     *
     * @var Zend_Date
     */
    protected $_syncTimeStamp;
    
    /**
     * class to use for search entries
     *
     * @var Tinebase_Model_Filter_FilterGroup
     */
    protected $_contentFilterClass;
    
    /**
     * instance of the content specific controller
     *
     * @var unknown_type
     */
    protected $_contentController;
    
    #protected $_contentModel;
    
    public function __construct(Zend_Date $_syncTimeStamp)
    {
        $this->_syncTimeStamp = $_syncTimeStamp;
        $this->_contentFilterClass = $this->_applicationName . '_Model_' . $this->_modelName . 'Filter';
        #$this->_contentModel       = $this->_applicationName . '_Model_' . $this->_modelName;
        $this->_contentController = Tinebase_Core::getApplicationInstance($this->_applicationName, $this->_modelName);
    }
    
    public function getFolders()
    {
        return $this->_folders;
    }
    
    public function getFolder($_folderId)
    {
        foreach($this->_folders as $folder) {
            if($folder['folderId'] == $_folderId) {
                return $folder;
            }
        }
        
        throw new ActiveSync_Exception_FolderNotFound('folder not found. ' . $_folderId);
    }
    
    /**
     * get estimate of add or changed entries
     *
     * @param Zend_Date $_startTimeStamp
     * @param Zend_Date $_endTimeStamp
     * @return int total count of changed items
     */
    public function getItemEstimate($_startTimeStamp = NULL, $_endTimeStamp = NULL)
    {
        $count = 0;
        $startTimeStamp = ($_startTimeStamp instanceof Zend_Date) ? $_startTimeStamp->get(Tinebase_Record_Abstract::ISO8601LONG) : $_startTimeStamp;
        
        if($_startTimeStamp === NULL && $_endTimeStamp === NULL) {
            $filter = new $this->_contentFilterClass(array()); 
            $count = $this->_contentController->searchCount($filter);
        } elseif($_endTimeStamp === NULL) {
            foreach(array('creation_time', 'last_modified_time') as $fieldName) {
                $filter = new $this->_contentFilterClass(array(
                    array(
                        'field'     => $fieldName,
                        'operator'  => 'after',
                        'value'     => $startTimeStamp
                    ),
                )); 
                $count += $this->_contentController->searchCount($filter);
            }
        } else {
            $endTimeStamp = ($_endTimeStamp instanceof Zend_Date) ? $_endTimeStamp->get(Tinebase_Record_Abstract::ISO8601LONG) : $_endTimeStamp;
            
            foreach(array('creation_time', 'last_modified_time') as $fieldName) {
                $filter = new $this->_contentFilterClass(array(
                    array(
                        'field'     => $fieldName,
                        'operator'  => 'after',
                        'value'     => $startTimeStamp
                    ),
                    array(
                        'field'     => $fieldName,
                        'operator'  => 'before',
                        'value'     => $endTimeStamp
                    ),
                )); 
                $count += $this->_contentController->searchCount($filter);
            }
        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Count: $count Timestamps: ($startTimeStamp / $endTimeStamp)");
                    
        return $count;
    }
    
    /**
     * add entry from xml data
     *
     * @param unknown_type $_collectionId
     * @param SimpleXMLElement $_data
     * @return Tinebase_Record_Abstract
     */
    public function add($_collectionId, SimpleXMLElement $_data)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " add entry");
        
        $entry = $this->_toTineModel($_data);
        $entry->creation_time = $this->_syncTimeStamp;
        
        $entry = $this->_contentController->create($entry);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " added entry id " . $entry->getId());

        return $entry;
    }
        
    /**
     * update existing entry
     *
     * @param unknown_type $_collectionId
     * @param string $_id
     * @param SimpleXMLElement $_data
     * @return Tinebase_Record_Abstract
     */
    public function change($_collectionId, $_id, SimpleXMLElement $_data)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " CollectionId: $_collectionId Id: $_id");
        
        $oldEntry = $this->_contentController->get($_id); 
        
        $entry = $this->_toTineModel($_data, $oldEntry);
        $entry->last_modified_time = $this->_syncTimeStamp;
        
        $entry = $this->_contentController->update($entry);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " updated entry id " . $entry->getId());

        return $entry;
    }
        
    /**
     * delete entry
     *
     * @param string $_collectionId
     * @param string $_id
     */
    public function delete($_collectionId, $_id)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " ColectionId: $_collectionId Id: $_id");
        
        $this->_contentController->delete($_id);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " deleted entry id " . $_id);
    }
    
    /**
     * search for existing entry
     *
     * @param unknown_type $_collectionId
     * @param SimpleXMLElement $_data
     * @return Tinebase_Record_Abstract
     */
    public function search($_collectionId, SimpleXMLElement $_data)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " CollectionId: $_collectionId");
        
        $filter = $this->_toTineFilter($_data);
        
        $foundEmtries = $this->_contentController->search($filter);

        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " found " . count($foundEmtries));
            
        return $foundEmtries;
    }
    
    /**
     * get all entries changed between to dates
     *
     * @param unknown_type $_field
     * @param unknown_type $_startTimeStamp
     * @param unknown_type $_endTimeStamp
     * @return array
     */
    public function getSince($_field, $_startTimeStamp, $_endTimeStamp)
    {
        switch($_field) {
            case 'added':
                $fieldName = 'creation_time';
                break;
            case 'changed':
                $fieldName = 'last_modified_time';
                break;
            case 'deleted':
                $fieldName = 'deleted_time';
                break;
            default:
                throw new Exception("$_field must be either added, changed or deleted");                
        }
        
        $startTimeStamp = ($_startTimeStamp instanceof Zend_Date) ? $_startTimeStamp->get(Tinebase_Record_Abstract::ISO8601LONG) : $_startTimeStamp;
        $endTimeStamp = ($_endTimeStamp instanceof Zend_Date) ? $_endTimeStamp->get(Tinebase_Record_Abstract::ISO8601LONG) : $_endTimeStamp;
        
        $filter = new $this->_contentFilterClass(array(
            array(
                'field'     => $fieldName,
                'operator'  => 'after',
                'value'     => $startTimeStamp
            ),
            array(
                'field'     => $fieldName,
                'operator'  => 'before',
                'value'     => $endTimeStamp
            ),
        ));

        $result = $this->_contentController->search($filter, NULL, false, true);
        
        return $result;
    }    
    
    /**
     * get id's of all contacts available on the server
     *
     * @return array
     */
    public function getServerEntries()
    {
        $contentFilter     = new $this->_contentFilterClass(array(
            array(
                'field'     => 'containerType',
                'operator'  => 'equals',
                'value'     => 'all'
            )
        ));
        
        $foundEntries      = $this->_contentController->search($contentFilter, NULL, false, true);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " found " . count($foundEntries) . ' entries');
            
        return $foundEntries;
    }
    
    abstract protected function _toTineModel(SimpleXMLElement $_data, $_entry = null);
    
    abstract protected function _toTineFilter(SimpleXMLElement $_data);
    
    abstract public function appendXML(DOMDocument $_xmlDocument, DOMElement $_xmlNode, $_serverId);    
}