<?php
/**
 * Call controller for Phone application
 * 
 * @package     Phone
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Call controller class for Phone application
 * 
 * @package     Phone
 * @subpackage  Controller
 */
class Phone_Controller_Call extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_applicationName = 'Phone';
        $this->_backend = Phone_Backend_Factory::factory(Phone_Backend_Factory::CALLHISTORY);
        $this->_modelName = 'Phone_Model_Call';
        $this->_purgeRecords = TRUE;
        $this->_doRightChecks = FALSE;
        // activate this if you want to use containers
        $this->_doContainerACLChecks = FALSE;
    }    
    
    /**
     * holds the instance of the singleton
     *
     * @var Phone_Controller_Call
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Phone_Controller_Call
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }

    /**
     * inspect creation of one record (before create)
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        $this->resolveCallNumberToContact($_record);
    }

    /**
     * resolveCallNumberToContact (if possible)
     *
     * @param Phone_Model_Call $call
     * @param bool $ignoreAcl
     */
    public function resolveCallNumberToContact(Phone_Model_Call $call, $ignoreAcl = false)
    {
        // resolve telephone number to contacts if possible
        $telNumber = Addressbook_Model_Contact::normalizeTelephoneNum(
            $this->resolveInternalNumber($call->destination));
        if (null !== $telNumber && ! empty($telNumber)) {

            $call->resolved_destination = $telNumber;

            $filter = new Addressbook_Model_ContactFilter(array(
                array('field' => 'telephone_normalized', 'operator' => 'equals', 'value' => $telNumber),
            ));

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' Searching contacts with filter: '
                . print_r($filter->toArray(), true));

            $controller = Addressbook_Controller_Contact::getInstance();
            if ($ignoreAcl) {
                $oldAclChecks = $controller->doContainerACLChecks();
                $controller->doContainerACLChecks(false);
            }

            $contacts = $controller->search($filter);

            if ($ignoreAcl) {
                $controller->doContainerACLChecks($oldAclChecks);
            }

            if ($contacts->count() > 0) {
                $call->contact_id = $contacts->getFirstRecord()->getId();
            }
        }
    }

    /**
     * normalize number
     *
     * @param $number
     * @return string
     */
    public function resolveInternalNumber($number)
    {
        $number = preg_replace('/[^\d\+]/u', '', $number);
        if (empty($number)) {
            return $number;
        }

        $config = Phone_Config::getInstance();
        $localPrefix = $config->get(Phone_Config::LOCAL_PREFIX);

        // if the number does not start with the local prefix, its an internal number. prefix and return
        if (strpos($number, $localPrefix) !== 0) {

            //if the number is longer than 5 digits, its actually an international number with/out + *sight*
            if (strlen($number) > 5) {
                if ($number[0] !== '+')
                    $number = '+' . $number;
                return $number;
            } else {
                return $config->get(Phone_Config::OWN_NUMBER_PREFIX) . $number;
            }
        } else {
            // cut local prefix
            $number = substr($number, strlen($localPrefix));
        }

        // if its a local number, prefix area code and return
        if (preg_match($config->get(Phone_Config::LOCAL_CALL_REGEX), $number)) {
            return $config->get(Phone_Config::AREA_CODE) . $number;
        }

        return $number;
    }

    /**
     * get list of records
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @param boolean $_getRelations
     * @param boolean $_onlyIds
     * @param string $_action for right/acl check
     * @return Tinebase_Record_RecordSet|array
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        $_filter->setId('OuterFilter');
    
        return parent::search($_filter, $_pagination, $_getRelations, $_onlyIds, $_action);
    }
// TODO: make this work and remove setting totalcount in fe
//     /**
//      * Gets total count of search with $_filter
//      *
//      * @param Tinebase_Model_Filter_FilterGroup $_filter
//      * @param string $_action for right/acl check
//      * @return int
//      */
//     public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
//     {
//         $_filter->setId('OuterFilter');
        
//         return count($this->search($_filter, NULL, FALSE, TRUE));
//     }
    
    /**
     * Gets the phone id filter for the first record in the given recordset
     *
     * @param Tinebase_Record_RecordSet $userPhones
     * @return Tinebase_Model_Filter_Abstract
     */
    protected function _getDefaultPhoneFilter($userPhones)
    {
        if ($userPhones->count()) {
            $filter = new Phone_Model_CallFilter(
                array('id' => 'defaultAdded', 'field' => 'phone_id', 'operator' => 'AND', 'value' => array(
                    array('field' => ':id', 'operator' => 'in', 'value' => $userPhones->getId())
                ))
            );
        } else {
            $filter = new Tinebase_Model_Filter_Text(array('id' => 'defaultAdded', 'field' => 'id', 'operator' => 'equals', 'value' => 'notexists'));
        }
    
        return $filter;
    }
}
