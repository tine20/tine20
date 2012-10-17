<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Addressbook_Frontend_Json
 *
 * This class handles all Json requests for the addressbook application
 *
 * @package     Addressbook
 * @subpackage  Frontend
 */
class Addressbook_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * app name
     * 
     * @var string
     */
    protected $_applicationName = 'Addressbook';
    
    /**
     * resolve images
     * @param Tinebase_Record_RecordSet $_records
     */
    public static function resolveImages(Tinebase_Record_RecordSet $_records)
    {
        foreach($_records as &$record) {
            if($record['jpegphoto'] == '1') {
                $record['jpegphoto'] = Tinebase_Model_Image::getImageUrl('Addressbook', $record->__get('id'), '');
            }
        }
    }

    /**
     * get one contact identified by $id
     *
     * @param string $id
     * @return array
     */
    public function getContact($id)
    {
        return $this->_get($id, Addressbook_Controller_Contact::getInstance());
    }
    
    /**
     * Search for contacts matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchContacts($filter, $paging)
    {
        return $this->_search($filter, $paging, Addressbook_Controller_Contact::getInstance(), 'Addressbook_Model_ContactFilter');
    }    
    
    /**
     * return autocomplete suggestions for a given property and value
     * 
     * @todo have spechial controller/backend fns for this
     * @todo move to abstract json class and have tests
     *
     * @param  string   $property
     * @param  string   $startswith
     * @return array
     */
    public function autoCompleteContactProperty($property, $startswith)
    {
        if (preg_match('/[^A-Za-z0-9_]/', $property)) {
            // NOTE: it would be better to ask the model for property presece, but we can't atm.
            throw new Tasks_Exception_UnexpectedValue('bad property name');
        }
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => $property, 'operator' => 'startswith', 'value' => $startswith),
        ));
        
        $paging = new Tinebase_Model_Pagination(array('sort' => $property));
        
        $values = array_unique(Addressbook_Controller_Contact::getInstance()->search($filter, $paging)->{$property});
        
        $result = array(
            'results'   => array(),
            'totalcount' => count($values)
        );
        
        foreach($values as $value) {
            $result['results'][] = array($property => $value);
        }
        
        return $result;
    }
    
    /**
     * Search for lists matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchLists($filter, $paging)
    {
        return $this->_search($filter, $paging, Addressbook_Controller_List::getInstance(), 'Addressbook_Model_ListFilter');
    }    

    /**
     * delete multiple contacts
     *
     * @param array $ids list of contactId's to delete
     * @return array
     */
    public function deleteContacts($ids)
    {
        return $this->_delete($ids, Addressbook_Controller_Contact::getInstance());
    }
    
    /**
     * save one contact
     *
     * if $recordData['id'] is empty the contact gets added, otherwise it gets updated
     *
     * @param  array $recordData an array of contact properties
     * @param  boolean $duplicateCheck
     * @return array
     */
    public function saveContact($recordData, $duplicateCheck = TRUE)
    {
        return $this->_save($recordData, Addressbook_Controller_Contact::getInstance(), 'Contact', 'id', array($duplicateCheck));
    }
    
    /**
     * import contacts
     * 
     * @param string $tempFileId to import
     * @param string $definitionId
     * @param array $importOptions
     * @param array $clientRecordData
     * @return array
     */
    public function importContacts($tempFileId, $definitionId, $importOptions, $clientRecordData = array())
    {
        return $this->_import($tempFileId, $definitionId, $importOptions, $clientRecordData);
    }
    
    /**
    * get contact information from string by parsing it using predefined rules
    *
    * @param string $address
    * @return array
    */
    public function parseAddressData($address)
    {
        $result = Addressbook_Controller_Contact::getInstance()->parseAddressData($address);
        
        return array(
            'contact'             => $this->_recordToJson($result['contact']),
            'unrecognizedTokens'  => $result['unrecognizedTokens'],
        );
    }
    
    /**
     * get default addressbook
     * 
     * @return array
     */
    public function getDefaultAddressbook()
    {
        $defaultAddressbook = Addressbook_Controller_Contact::getInstance()->getDefaultAddressbook();
        $defaultAddressbookArray = $defaultAddressbook->toArray();
        $defaultAddressbookArray['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $defaultAddressbook->getId())->toArray();
        
        return $defaultAddressbookArray;
    }
    
    /**
    * returns contact prepared for json transport
    *
    * @param Addressbook_Model_Contact $_contact
    * @return array contact data
    */
    protected function _recordToJson($_contact)
    {
        $result = parent::_recordToJson($_contact);
        $result['jpegphoto'] = $this->_getImageLink($result);
    
        return $result;
    }
    
    /**
     * returns multiple records prepared for json transport
     *
     * @param Tinebase_Record_RecordSet $_records Tinebase_Record_Abstract
     * @param Tinebase_Model_Filter_FilterGroup
     * @param Tinebase_Model_Pagination $_pagination
     * @return array data
     */
    protected function _multipleRecordsToJson(Tinebase_Record_RecordSet $_records, $_filter = NULL, $_pagination = NULL)
    {
        $result = parent::_multipleRecordsToJson($_records, $_filter, $_pagination);
        
        foreach ($result as &$contact) {
            $contact['jpegphoto'] = $this->_getImageLink($contact);
        }
        
        return $result;
    }

    /**
     * returns a image link
     * 
     * @param  array $contactArray
     * @return string
     */
    protected function _getImageLink($contactArray)
    {
        $link = 'images/empty_photo_blank.png';
        if (! empty($contactArray['jpegphoto'])) {
            $link = Tinebase_Model_Image::getImageUrl('Addressbook', $contactArray['id'], '');
        } else if (isset($contactArray['salutation']) && ! empty($contactArray['salutation'])) {
            $salutations = Addressbook_Config::getInstance()->get(Addressbook_Config::CONTACT_SALUTATION, NULL);
            if ($salutations && $salutations->records instanceof Tinebase_Record_RecordSet) {
                $salutationRecord = $salutations->records->getById($contactArray['salutation']);
                if ($salutationRecord && $salutationRecord->image) {
                    $link = $salutationRecord->image;
                }
            }
        }
        
        return $link;
    }

    /**
     * Returns registry data of addressbook.
     * @see Tinebase_Application_Json_Abstract
     * 
     * @return mixed array 'variable name' => 'data'
     */
    public function getRegistryData()
    {
        $definitionConverter = new Tinebase_Convert_ImportExportDefinition_Json();
        $importDefinitions = $this->_getImportDefinitions();
        $defaultDefinition = $this->_getDefaultImportDefinition($importDefinitions);
        
        $registryData = array(
            'defaultAddressbook'        => $this->getDefaultAddressbook(),
            'defaultImportDefinition'   => $definitionConverter->fromTine20Model($defaultDefinition),
            'importDefinitions'         => array(
                'results'               => $definitionConverter->fromTine20RecordSet($importDefinitions),
                'totalcount'            => count($importDefinitions),
            ),
        );
        return $registryData;
    }
    
    /**
     * get addressbook import definitions
     * 
     * @return Tinebase_Record_RecordSet
     * 
     * @todo generalize this
     */
    protected function _getImportDefinitions()
    {
        $filter = new Tinebase_Model_ImportExportDefinitionFilter(array(
            array('field' => 'application_id',  'operator' => 'equals', 'value' => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId()),
            array('field' => 'type',            'operator' => 'equals', 'value' => 'import'),
        ));
        
        $importDefinitions = Tinebase_ImportExportDefinition::getInstance()->search($filter);
        
        return $importDefinitions;
    }
    
    /**
     * get default definition
     * 
     * @param Tinebase_Record_RecordSet $_importDefinitions
     * @return Tinebase_Model_ImportExportDefinition
     * 
     * @todo generalize this
     */
    protected function _getDefaultImportDefinition($_importDefinitions)
    {
        try {
            $defaultDefinition = Tinebase_ImportExportDefinition::getInstance()->getByName('adb_tine_import_csv');
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (count($_importDefinitions) > 0) {
                $defaultDefinition = $_importDefinitions->getFirstRecord();
            } else {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' No import definitions found for Addressbook');
                $defaultDefinition = NULL;
            }
        }
        
        return $defaultDefinition;
    }
}
