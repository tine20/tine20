<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * @var string
     */
    protected $_defaultImportDefinitionName = 'adb_tine_import_csv';

    /**
     * resolve images
     * @param Tinebase_Record_RecordSet $_records
     */
    public static function resolveImages(Tinebase_Record_RecordSet $_records)
    {
        /** @var Tinebase_Record_Interface $record */
        foreach($_records as &$record) {
            if($record['jpegphoto'] == '1') {
                $record['jpegphoto'] = Tinebase_Model_Image::getImageUrl('Addressbook', $record->getId(), '');
            }
        }
    }

    /**
     * get one list identified by $id
     *
     * @param string $id
     * @return array
     */
    public function getList($id)
    {
        return $this->_get($id, Addressbook_Controller_List::getInstance());
    }

    /**
     * save one list
     *
     * if $recordData['id'] is empty the list gets added, otherwise it gets updated
     *
     * @param  array $recordData an array of list properties
     * @param  boolean $duplicateCheck
     * @return array
     */
    public function saveList($recordData, $duplicateCheck = FALSE)
    {
        return $this->_save($recordData, Addressbook_Controller_List::getInstance(), 'List', 'id', array($duplicateCheck));
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
        $expander = new Tinebase_Record_Expander(Addressbook_Model_Contact::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                'container_id'  => [],
                'tags'          => [],
                'attachments'   => [],
            ],
            Tinebase_Record_Expander::EXPANDER_PROPERTY_CLASSES => [
                Tinebase_Record_Expander::PROPERTY_CLASS_USER => [],
            ],
        ]);

        return $this->_search($filter, $paging, Addressbook_Controller_Contact::getInstance(),
            Addressbook_Model_ContactFilter::class, $expander);
    }

    /**
     * Search for Email Addresses with the Email Model in Lists and Contacts
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchEmailAddresss($filter, $paging)
    {
        $results = array();
        $contactPaging = $paging;
        $contactPaging["sort"] = "n_fn"; // Field are not named the same for contacts and lists
        $contacts = $this->_search($filter, $contactPaging, Addressbook_Controller_Contact::getInstance(), 'Addressbook_Model_ContactFilter');
        foreach ($contacts["results"] as $contact) {
            array_push($results, array("n_fileas" => $contact["n_fileas"], "email" => $contact["email"], "email_home" => $contact["email_home"]));
        }

        $dont_add = false;
        if (isset($paging["start"])) {
            $paging["start"] = $paging["start"] - $contacts["totalcount"] + count($results);
            $paging["limit"] = $paging["limit"] - count($results);
            if (($paging["limit"] <= 0) || ($paging["start"] < 0)) {
                $dont_add = true;
                $paging["limit"] = 1;
                $paging["start"] = 0;
            }
        }

        // TODO discuss this behaviour - do we still need it when we have the group mailing sieve rules?
        $oldFeatureValue = null;
        $adbConfig = Addressbook_Config::getInstance();
        try {
            if (!$dont_add) {
                // need to enable this feature to get the "emails" property
                if (false === ($oldFeatureValue =
                        $adbConfig->featureEnabled(Addressbook_Config::FEATURE_LIST_VIEW))) {
                    $features = $adbConfig->get(Addressbook_Config::ENABLED_FEATURES);
                    $features->{Addressbook_Config::FEATURE_LIST_VIEW} = true;
                    $adbConfig->clearCache();
                    Addressbook_Controller_List::destroyInstance();
                }
            }
            // NOTE: please ignore the "Skipping filter (no filter model defined)" INFO message in the logs ...
            $lists = $this->_search($filter, $paging, Addressbook_Controller_List::getInstance(),
                'Addressbook_Model_ListFilter');
            if (!$dont_add) {
                foreach ($lists["results"] as $list) {
                    if (! empty($list["emails"])) {
                        array_push($results, array("n_fileas" => $list["name"], "emails" => $list["emails"]));
                    }
                }
            }
        } finally {
            if (false === $oldFeatureValue) {
                $features = $adbConfig->get(Addressbook_Config::ENABLED_FEATURES);
                $features->{Addressbook_Config::FEATURE_LIST_VIEW} = false;
                $adbConfig->clearCache();
            }
        }

        return array("results" => $results, "totalcount" => $lists["totalcount"]+$contacts["totalcount"]);
    }

    /**
     * return autocomplete suggestions for a given property and value
     *
     * @todo have special controller/backend fns for this
     * @todo move to abstract json class and have tests
     *
     * @param  string $property
     * @param  string $startswith
     * @return array
     * @throws Tasks_Exception_UnexpectedValue
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
        return $this->_search($filter, $paging, Addressbook_Controller_List::getInstance(), 'Addressbook_Model_ListFilter', true);
    }   

    /**
     * delete multiple lists
     *
     * @param array $ids list of listId's to delete
     * @return array
     */
    public function deleteLists($ids)
    {
        return $this->_delete($ids, Addressbook_Controller_List::getInstance());
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
     * get one list role identified by $id
     *
     * @param string $id
     * @return array
     */
    public function getListRole($id)
    {
        return $this->_get($id, Addressbook_Controller_ListRole::getInstance());
    }

    /**
     * Search for lists roles matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchListRoles($filter, $paging)
    {
        return $this->_search($filter, $paging, Addressbook_Controller_ListRole::getInstance(), 'Addressbook_Model_ListRoleFilter');
    }

    /**
     * delete multiple list roles
     *
     * @param array $ids list of listId's to delete
     * @return array
     */
    public function deleteListRoles($ids)
    {
        return $this->_delete($ids, Addressbook_Controller_ListRole::getInstance());
    }

    /**
     * save list role
     *
     * @param $recordData
     * @return array
     */
    public function saveListRole($recordData)
    {
        return $this->_save($recordData, Addressbook_Controller_ListRole::getInstance(), 'ListRole');
    }
    
    /**
     * get one industry identified by $id
     *
     * @param string $id
     * @return array
     */
    public function getIndustry($id)
    {
        return $this->_get($id, Addressbook_Controller_Industry::getInstance());
    }
    
    /**
     * Search for industries matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchIndustrys($filter, $paging)
    {
        return $this->_search($filter, $paging, Addressbook_Controller_Industry::getInstance(), 'Addressbook_Model_IndustryFilter');
    }
    
    /**
     * delete multiple industries
     *
     * @param array $ids list of listId's to delete
     * @return array
     */
    public function deleteIndustrys($ids)
    {
        return $this->_delete($ids, Addressbook_Controller_Industry::getInstance());
    }
    
    /**
     * save industry
     *
     * @param $recordData
     * @return array
     */
    public function saveIndustry($recordData)
    {
        return $this->_save($recordData, Addressbook_Controller_Industry::getInstance(), 'Industry');
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
        $adbController = Addressbook_Controller_Contact::getInstance();
        $context = $adbController->getRequestContext() ?: [];
        try {
            $context['jsonFE'] = true;
            $adbController->setRequestContext($context);

            return $this->_save($recordData, $adbController, 'Contact', 'id', array($duplicateCheck));

        } finally {
            unset($context['jsonFE']);
            $adbController->setRequestContext($context);
        }
    }
    

    
    /**
    * get contact information from string by parsing it using predefined rules
    *
    * @param string $address
    * @return array
    */
    public function parseAddressData($address)
    {
        if (preg_match('/^http/', $address)) {
            $vcard = file_get_contents($address);

            // Could not load file from remote
            if ($vcard === false) {
                return array('exceptions' => "Cannot get file from remote.");
            }

            $converter = Addressbook_Convert_Contact_VCard_Factory::factory(
                strpos($address, 'dastelefonbuch')
                ? Addressbook_Convert_Contact_VCard_Factory::CLIENT_TELEFONBUCH
                : Addressbook_Convert_Contact_VCard_Factory::CLIENT_GENERIC
            );

            $record = $converter->toTine20Model($vcard);
            $contactData = $this->_recordToJson($record);

            if (array_key_exists('jpegphoto', $contactData)) {
                unset($contactData['jpegphoto']);
            }

            return array('contact' => $contactData);
        } else {
            $result = Addressbook_Controller_Contact::getInstance()->parseAddressData($address);
            $contactData = $this->_recordToJson($result['contact']);

            if (array_key_exists('jpegphoto', $contactData)) {
                unset($contactData['jpegphoto']);
            }

            if (array_key_exists('salutation', $contactData)) {
                unset($contactData['salutation']);
            }

            return array(
                'contact' => $contactData,
                'unrecognizedTokens' => $result['unrecognizedTokens'],
            );
        }
    }
    
    /**
     * get default addressbook
     * 
     * @return array
     */
    public function getDefaultAddressbook()
    {
        $defaultAddressbook = Addressbook_Controller_Contact::getInstance()->getDefaultAddressbook();

        $account_grants = Tinebase_Container::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $defaultAddressbook)->toArray();
        $defaultAddressbookArray = $defaultAddressbook->toArray();
        $defaultAddressbookArray['account_grants'] = $account_grants;
        
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
     * @param Tinebase_Record_RecordSet $_records Tinebase_Record_Interface
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
        $link = 'images/icon-set/icon_undefined_contact.svg';
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
     * Returns registry data of Addressbook.
     * @see Tinebase_Application_Json_Abstract
     * 
     * @return mixed array 'variable name' => 'data'
     */
    public function getRegistryData()
    {
        $registryData = array(
            'defaultAddressbook'        => $this->getDefaultAddressbook(),
        );

        $registryData = array_merge($registryData, $this->_getImportDefinitionRegistryData());

        return $registryData;
    }
}
