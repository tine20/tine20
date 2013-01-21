<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to import addressbooks/contacts from egw14
 * 
 * 
 * @package     Addressbook
 * @subpackage  Setup
 */
class Addressbook_Setup_Import_Egw14 extends Tinebase_Setup_Import_Egw14_Abstract
{
    protected $_appname = 'Addressbook';
    protected $_egwTableName = 'egw_addressbook';
    protected $_egwOwnerColumn = 'contact_owner';
    protected $_defaultContainerConfigProperty = Addressbook_Preference::DEFAULTADDRESSBOOK;
    protected $_tineRecordModel = 'Addressbook_Model_Contact';
    
    
    protected $_tineRecordBackend = NULL;
    
    /**
     * country mapping
     * 
     * @var array
     */
    protected $_countryMapping = array(
        
        "BELGIEN"          => "BE",
        "BULGARIEN"        => "BG",
        "DEUTSCHLAND"      => "DE",
        "FRANKREICH"       => "FR",
        "GERMANY"          => "DE",
        "GREAT BRITAIN"    => "GB",
        "IRELAND"          => "IE",
        "JAPAN"            => "JP",
        "LUXEMBURG"        => "LU",
        "NEW ZEALAND"      => "NZ",
        "NIEDERLANDE"      => "NL",
        "ÖSTERREICH"       => "AT",
        "SCHWEIZ"          => "CH",
        "SLOVAKEI"         => "SK",
        "SPANIEN"          => "ES",
        "SWEDEN"           => "SE",
        "USA"              => "US",
        "VEREINIGTE STAATEN VON AMERIKA" => "US",
        
    );
    
    /**
     * do the import 
     */
    public function import()
    {
        $this->_log->NOTICE(__METHOD__ . '::' . __LINE__ . ' starting egw import for Adressbook');
        
        $this->_migrationStartTime = Tinebase_DateTime::now();
        $this->_tineRecordBackend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
        
        $estimate = $this->_getEgwRecordEstimate();
        $this->_log->NOTICE(__METHOD__ . '::' . __LINE__ . " found {$estimate} contacts for migration");
        
        $pageSize = 100;
        $numPages = ceil($estimate/$pageSize);
        
        for ($page=1; $page <= $numPages; $page++) {
            $this->_log->info(__METHOD__ . '::' . __LINE__ . " starting migration page {$page} of {$numPages}");
            
            Tinebase_Core::setExecutionLifeTime($pageSize*10);
            
            $recordPage = $this->_getRawEgwRecordPage($page, $pageSize);
            $this->_migrateEgwRecordPage($recordPage);
        }
        
        $this->_log->NOTICE(__METHOD__ . '::' . __LINE__ . ' ' . ($this->_importResult['totalcount'] - $this->_importResult['failcount']) . ' contacts imported sucessfully ' . ($this->_importResult['failcount'] ? " {$this->_importResult['failcount']} contacts skipped with failures" : ""));
    }
    
    /**
     * @TODO: egw can have groups as owner 
     * -> map this to shared folder
     * -> find appropriate creator / current_user for this
     * -> support maps for group => creator and owner => folder?
     */
    protected function _migrateEgwRecordPage($recordPage)
    {
        foreach($recordPage as $egwContactData) {
            try {
                $this->_importResult['totalcount']++;
                $currentUser = Tinebase_Core::get(Tinebase_Core::USER);
                $owner = $egwContactData['contact_owner'] ? Tinebase_User::getInstance()->getFullUserById($this->mapAccountIdEgw2Tine($egwContactData['contact_owner'])) : $currentUser;
                Tinebase_Core::set(Tinebase_Core::USER, $owner);
                
                $contactData = array_merge($egwContactData, array(
                    'id'                    => $egwContactData['contact_id'],
                    'creation_time'         => $this->convertDate($egwContactData['contact_created']),
                    'created_by'            => $this->mapAccountIdEgw2Tine($egwContactData['contact_creator'], FALSE),
                    'last_modified_time'    => $egwContactData['contact_modified'] ? $this->convertDate($egwContactData['contact_modified']) : NULL,
                    'last_modified_by'      => $egwContactData['contact_modifier'] ? $this->mapAccountIdEgw2Tine($contact['contact_modifier'], FALSE) : NULL,
                ));
                
                $contactData['created_by'] = $contactData['created_by'] ?: $owner;
                $contactData['$egwContactData'] = $contactData['last_modified_time'] && !$contactData['last_modified_by'] ?: $owner;
                
                // fix mandentory fields
                if (! ($egwContactData['org_name'] || $egwContactData['n_family'])) {
                    $contactData['org_name'] = 'N/A';
                }
                
                // add 'http://' if missing
                foreach(array('contact_url', 'contact_url_home') as $urlProperty) {
                    if ( !preg_match("/^http/i", $egwContactData[$urlProperty]) && !empty($egwContactData[$urlProperty]) ) {
                        $contactData[$urlProperty] = "http://" . $egwContactData[$urlProperty];
                    }
                }
                
                // normalize countynames
                $contactData['adr_one_countryname'] = $this->convertCountryname2Iso($egwContactData['adr_one_countryname']);
                $contactData['adr_two_countryname'] = $this->convertCountryname2Iso($egwContactData['adr_two_countryname']);
                
                // handle bday
                if (array_key_exists('contact_bday', $egwContactData) && $egwContactData['contact_bday']) {
                    // @TODO evaluate contact_tz
                    $contactData['bday'] = new Tinebase_DateTime($egwContactData['contact_bday'], $this->_config->birthdayDefaultTimezone);
                } else if (array_key_exists('bday', $egwContactData) && $egwContactData['bday']) {
                    // egw <= 1.4
                    $contactData['bday'] = $this->convertDate($egwContactData['bday']);
                }
                
                // handle tags
                $contactData['tags'] = $this->convertCategories($egwContactData['cat_id']);
                
                // @TODO handle photo
                
                // handle container
                if ($egwContactData['contact_owner'] && ! $egwContactData['account_id']) {
                    $contactData['container_id'] = $egwContactData['contact_private'] && $this->_config->setPersonalContainerGrants ? 
                        $this->getPrivateContainer($this->mapAccountIdEgw2Tine($egwContactData['contact_owner']))->getId() :
                        $this->getPersonalContainer($this->mapAccountIdEgw2Tine($egwContactData['contact_owner']))->getId();
                }
                
                // finally create the record
                $tineContact = new Addressbook_Model_Contact ($contactData);
                $this->saveTineRecord($tineContact);
                
            } catch (Exception $e) {
                $this->_importResult['failcount']++;
                Tinebase_Core::set(Tinebase_Core::USER, $currentUser);
                $this->_log->ERR(__METHOD__ . '::' . __LINE__ . ' could not migrate contact "' . $egwContactData['contact_id'] . '" cause: ' . $e->getMessage());
                $this->_log->DEBUG(__METHOD__ . '::' . __LINE__ . ' ' . $e);
            }
        }
    }
    
    /**
     * save tine20 record to db
     * 
     * @param Tinebase_Record_Abstract $record
     */
    public function saveTineRecord(Tinebase_Record_Abstract $record)
    {
        if (! $record->account_id) {
            $savedRecord = $this->_tineRecordBackend->create($record);
        }
            
        else if ($this->_config->updateAccountRecords) {
            $accountId = $this->mapAccountIdEgw2Tine($record->account_id);
            $account = Tinebase_User::getInstance()->getUserById($accountId);
            
            if (! ($account && $account->contact_id)) {
                $this->_log->WARN(__METHOD__ . '::' . __LINE__ . " could not migrate account contact for {$record->n_fn} - no contact found");
                return;
            }
            
            $contact = $this->_tineRecordBackend->get($account->contact_id);
            $record->setId($account->contact_id);
            $record->container_id = $contact->container_id;
            
            $savedRecord = $this->_tineRecordBackend->update($record);
        }
        
        // tags
        $this->attachTags($record->tags, $savedRecord->getId());
    }
    
    /**
     * get iso code of localised country name
     * 
     * @TODO iterate zend_translate
     */
    public function convertCountryname2Iso($countryname)
    {
        // normalize empty
        if (!$countryname) return NULL;
        
        $countryname = strtoupper(trim($countryname));
        
        if (! array_key_exists($countryname, $this->_countryMapping)) {
            $this->_log->WARN(__METHOD__ . '::' . __LINE__ . " could not get coutry code for {$countryname}");
            
            return NULL;
        }
        
        return $this->_countryMapping[$countryname];
    }
}
