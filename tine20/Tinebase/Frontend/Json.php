<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id: Json.php 5047 2008-10-22 10:51:07Z c.weiss@metaways.de $
 * 
 * @todo        remove deprecated functions
 */

/**
 * Json interface to Tinebase
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * wait for changes
     *
     */
	public function ping()
	{
	    Zend_Session::writeClose(true);
	    sleep(10);
	    return array('changes' => 'contacts');
	}
	
    /**
     * get list of translated country names
     *
     * @return array list of countrys
     */
    public function getCountryList()
    {
        $locale = Tinebase_Core::get('locale');

        $countries = $locale->getCountryTranslationList();
        asort($countries);
        foreach($countries as $shortName => $translatedName) {
            $results[] = array(
				'shortName'         => $shortName, 
				'translatedName'    => $translatedName
            );
        }

        $result = array(
			'results'	=> $results
        );

        return $result;
    }

    /**
     * returns list of all available translations
     * NOTE available are those, having a Tinebase translation
     * 
     * @return array list of all available translations
     */
    public function getAvailableTranslations()
    {
        $availableTranslations = Tinebase_Translation::getAvailableTranslations();
        return array(
            'results'    => $availableTranslations,
            'totalcount' => count($availableTranslations)
        );
    }
    
    /**
     * sets locale
     *
     * @param  string $localeString
     * @param  bool   $saveaspreference
     * @param  bool   $setcookie
     * @return array
     */
    public function setLocale($localeString, $saveaspreference, $setcookie)
    {
        Tinebase_Core::setupUserLocale($localeString, $saveaspreference);
        $locale = Tinebase_Core::get('locale');
        
        // save in cookie (expires in 30 days)
        if ($setcookie) {
            setcookie('TINE20LOCALE', $localeString, time()+60*60*24*30);
        }
    }

    /**
     * returns list of all available timezones in the current locale
     * 
     * @return array list of all available timezones
     *
     * @todo add territory to translation?
     * @deprecated moved to preferences / do we need this elsewhere?
     */
    public function getAvailableTimezones()
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' This function is marked as deprecated.');
        
        $locale =  Tinebase_Core::get('locale');

        $availableTimezonesTranslations = $locale->getTranslationList('citytotimezone');
        
        $availableTimezones = DateTimeZone::listIdentifiers();
        $result = array();
        foreach ($availableTimezones as $timezone) {
            $result[] = array(
                'timezone' => $timezone,
                'timezoneTranslation' => array_key_exists($timezone, $availableTimezonesTranslations) ? $availableTimezonesTranslations[$timezone] : NULL
            );
        }
        
        return array(
            'results'    => $result,
            'totalcount' => count($result)
        );
    }
    
    /**
     * sets timezone
     *
     * @param  string $timezoneString
     * @param  bool   $saveaspreference
     * @return string
     */
    public function setTimezone($timezoneString, $saveaspreference)
    {
        $timezone = Tinebase_Core::setupUserTimezone($timezoneString, $saveaspreference);
        
        return $timezone;
    }
    
    /**
     * get users
     *
     * @param string $filter
     * @param string $sort
     * @param string $dir
     * @param int $start
     * @param int $limit
     * @return array with results array & totalcount (int)
     */
    public function getUsers($filter, $sort, $dir, $start, $limit)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Tinebase_User::getInstance()->getUsers($filter, $sort, $dir, $start, $limit)) {
            $result['results']    = $rows->toArray();
            if($start == 0 && count($result['results']) < $limit) {
                $result['totalcount'] = count($result['results']);
            } else {
                //$result['totalcount'] = $backend->getCountByAddressbookId($addressbookId, $filter);
            }
        }
        
        return $result;
    }
    
    /**
     * get list of groups
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return array with results array & totalcount (int)
     */
    public function getGroups($filter, $sort, $dir, $start, $limit)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $groups = Tinebase_Group::getInstance()->getGroups($filter, $sort, $dir, $start, $limit);

        $result['results'] = $groups->toArray();
        $result['totalcount'] = count($groups);
        
        return $result;
    }
    
    /**
     * change password of user 
     *
     * @param string $oldPassword the old password
     * @param string $newPassword the new password
     * @return array
     */
    public function changePassword($oldPassword, $newPassword)
    {
        $response = array(
            'success'      => TRUE
        );
        
        try {
            Tinebase_Controller::getInstance()->changePassword($oldPassword, $newPassword, $newPassword);
        } catch (Tinebase_Exception $e) {
            $response = array(
                'success'      => FALSE,
                'errorMessage' => "New password could not be set! Error: " . $e->getMessage()
            );   
        }
        
        return $response;        
    }    
    
    /**
     * adds a new personal tag
     */
    public function saveTag($tag)
    {
        $tagData = Zend_Json::decode($tag);
        $inTag = new Tinebase_Model_Tag($tagData);
        
        if (strlen($inTag->getId()) < 40) {
            Tinebase_Core::getLogger()->debug('creating tag: ' . print_r($inTag->toArray(), true));
            $outTag = Tinebase_Tags::getInstance()->createTag($inTag);
        } else {
            Tinebase_Core::getLogger()->debug('updating tag: ' .print_r($inTag->toArray(), true));
            $outTag = Tinebase_Tags::getInstance()->updateTag($inTag);
        }
        return $outTag->toArray();
    }
    
    /**
     * search tags
     *
     * @param string $filter json encoded filter array
     * @param string $paging json encoded pagination info
     * @return array
     */
    public function searchTags($filter, $paging)
    {
        $filter = new Tinebase_Model_TagFilter(Zend_Json::decode($filter));
        $paging = new Tinebase_Model_Pagination(Zend_Json::decode($paging));
        
        return array(
            'results'    => Tinebase_Tags::getInstance()->searchTags($filter, $paging)->toArray(),
            'totalCount' => Tinebase_Tags::getInstance()->getSearchTagsCount($filter)
        );
    }
    
    /**
     * search / get notes
     * - used by activities grid
     *
     * @param string $filter json encoded filter array
     * @param string $paging json encoded pagination info
     */
    public function searchNotes($filter, $paging)
    {
        $filter = new Tinebase_Model_NoteFilter(Zend_Json::decode($filter));
        $paging = new Tinebase_Model_Pagination(Zend_Json::decode($paging));
        
        return array(
            'results'       => Tinebase_Notes::getInstance()->searchNotes($filter, $paging)->toArray(),
            'totalcount'    => Tinebase_Notes::getInstance()->searchNotesCount($filter)
        );        
    }
    
    /**
     * get note types
     *
     * @todo add test
     */
    public function getNoteTypes()
    {
        $noteTypes = Tinebase_Notes::getInstance()->getNoteTypes();
        $noteTypes->translate();
        
        return array(
            'results'       => $noteTypes->toArray(),
            'totalcount'    => count($noteTypes)
        );        
    }
    
    /**
     * deletes tags identified by an array of identifiers
     * 
     * @param  array $ids
     * @return array 
     */
    public function deleteTags($ids)
    {
        Tinebase_Tags::getInstance()->deleteTags(Zend_Json::decode($ids));
        return array('success' => true);
    }
    
    /**
     * authenticate user by username and password
     *
     * @param string $username the username
     * @param string $password the password
     * @return array
     */
    public function login($username, $password)
    {
        if (Tinebase_Controller::getInstance()->login($username, $password, $_SERVER['REMOTE_ADDR']) === true) {
            $response = array(
				'success'       => TRUE,
                'account'       => Tinebase_Core::getUser()->getPublicUser()->toArray(),
				'jsonKey'       => Tinebase_Core::get('jsonKey'),
                'welcomeMessage' => "Welcome to Tine 2.0!"
			);
        } else {
            $response = array(
				'success'      => FALSE,
				'errorMessage' => "Wrong username or password!"
			);
        }

        return $response;
    }

    /**
     * destroy session
     *
     * @return array
     */
    public function logout()
    {
        Tinebase_Controller::getInstance()->logout($_SERVER['REMOTE_ADDR']);
        
        $result = array(
			'success'=> true,
        );

        return $result;
    }
    
    /**
     * Returns registry data of tinebase.
     * @see Tinebase_Application_Json_Abstract
     * 
     * @return mixed array 'variable name' => 'data'
     */
    public function getRegistryData()
    {
        $locale = Tinebase_Core::get('locale');
        
        // default credentials
        if(isset(Tinebase_Core::getConfig()->login)) {
            $loginConfig = Tinebase_Core::getConfig()->login;
            $defaultUsername = (isset($loginConfig->username)) ? $loginConfig->username : '';
            $defaultPassword = (isset($loginConfig->password)) ? $loginConfig->password : '';
        } else {
            $defaultUsername = '';
            $defaultPassword = '';
        }
        
        $registryData =  array(
            'timeZone'         => Tinebase_Core::get(Tinebase_Core::USERTIMEZONE),
            'locale'           => array(
                'locale'   => $locale->toString(), 
                'language' => $locale->getLanguageTranslation($locale->getLanguage()),
                'region'   => $locale->getCountryTranslation($locale->getRegion()),
            ),
            'defaultUsername' => $defaultUsername,
            'defaultPassword' => $defaultPassword
        );

        if (Tinebase_Core::isRegistered(Tinebase_Core::USER)) {
            $registryData += array(    
                'currentAccount'   => Tinebase_Core::getUser()->toArray(),
                'accountBackend'   => Tinebase_User::getConfiguredBackend(),
                'jsonKey'          => Tinebase_Core::get('jsonKey'),
                'userApplications' => Tinebase_Core::getUser()->getApplications()->toArray(),
                'NoteTypes'        => $this->getNoteTypes(),
                'CountryList'      => $this->getCountryList(),
                'version'          => array(
                    'codename'      => TINE20_CODENAME,
                    'packageString' => TINE20_PACKAGESTRING,
                    'releasetime'   => TINE20_RELEASETIME
                ), 
                'changepw'         => (isset(Tinebase_Core::getConfig()->accounts)
                                        && isset(Tinebase_Core::getConfig()->accounts->changepw))
                                            ? Tinebase_Core::getConfig()->accounts->changepw
                                            : true
            );
        }
        
        if (TINE20_BUILDTYPE == 'DEVELOPMENT') {
            $registryData['build'] = getDevelopmentRevision();
        }
        
        return $registryData;
    }
    
    /**
     * Returns registry data of all applications current user has access to
     * @see Tinebase_Application_Json_Abstract
     * 
     * @return mixed array 'variable name' => 'data'
     */
    public function getAllRegistryData()
    {
        $registryData = array();
        
        if (Tinebase_Core::isRegistered(Tinebase_Core::USER)) { 
            $userApplications = Tinebase_Core::getUser()->getApplications(TRUE);
            
            foreach($userApplications as $application) {
                
                $jsonAppName = $application->name . '_Frontend_Json';
                
                if(class_exists($jsonAppName)) {
                    $applicationJson = new $jsonAppName;
                    
                    $registryData[$application->name] = $applicationJson->getRegistryData();
                    $registryData[$application->name]['rights'] = Tinebase_Core::getUser()->getRights($application->name);
                    $registryData[$application->name]['config'] = Tinebase_Config::getInstance()->getConfigForApplication($application);
                    $registryData[$application->name]['customfields'] = Tinebase_Config::getInstance()->getCustomFieldsForApplication($application)->toArray();
                }
            }
        } else {
            $registryData['Tinebase'] = $this->getRegistryData();
        }
        
        die(Zend_Json::encode($registryData));
    }

    /************************ preferences functions ***************************/
    
    /**
     * search preferences
     *
     * @param string $applicationName
     * @param string $filter json encoded
     * @return array
     */
    public function searchPreferencesForApplication($applicationName, $filter)
    {
        $decodedFilter = Zend_Json::decode($filter);
        
        $filter = new Tinebase_Model_PreferenceFilter(array());
        $filter->setFromArrayInUsersTimezone($decodedFilter);
        
        // make sure, appid is set (tinebase appid is default)
        $tinebaseAppId = Tinebase_Application::getInstance()->getApplicationByName($applicationName)->getId();
        $filter->createFilter('application_id', 'equals', $tinebaseAppId);
        
        // make sure account is set in filter
        $userId = Tinebase_Core::getUser()->getId();
        if (!$filter->isFilterSet('account')) {
            $filter->createFilter('account', 'equals', array(
                'accountId' => $userId, 
                'accountType' => Tinebase_Model_Preference::ACCOUNT_TYPE_USER
            ));
        } else {
            // only admins can search for other users prefs
            $accountFilter = $filter->getAclFilter();
            $accountFilterValue = $accountFilter->getValue(); 
            if ($accountFilterValue['accountId'] != $userId && $accountFilterValue['accountType'] == Tinebase_Model_Preference::ACCOUNT_TYPE_USER) {
                if (!Tinebase_Acl_Roles::getInstance()->hasRight($applicationName, Tinebase_Core::getUser()->getId(), Tinebase_Acl_Rights_Abstract::ADMIN)) {
                    return array(
                        'results'       => array(),
                        'totalcount'    => 0
                    );
                }
            }
        }
        
        // check if application has preference class
        if ($backend = Tinebase_Core::getPreference($applicationName)) {
            $allPrefs = $backend->search($filter, NULL);
            
            // get single matching preferences for each different pref
            $records = $backend->getMatchingPreferences($allPrefs);
            
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($records->toArray(), true));
            
            $result = $this->_multipleRecordsToJson($records);
        } else {
            $result = array();
        }
        
        return array(
            'results'       => $result,
            'totalcount'    => count($result)
        );
    }
    
    /**
     * save preferences for application
     *
     * @param string    $data       json encoded preferences data
     * @param bool      $adminMode  submit in admin mode?
     */
    public function savePreferences($data, $adminMode)
    {
        $decodedData = Zend_Json::decode($data);
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($decodedData, true));
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($adminMode, true));
        
        foreach ($decodedData as $applicationName => $data) {
            
            $backend = Tinebase_Core::getPreference($applicationName); 
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($data, true));
            
            if ($adminMode == TRUE) {
                // only admins are allowed to update app pref defaults/forced prefs
                if (!Tinebase_Acl_Roles::getInstance()->hasRight($applicationName, Tinebase_Core::getUser()->getId(), Tinebase_Acl_Rights_Abstract::ADMIN)) {
                    throw new Tinebase_Exception_AccessDenied('You are not allowed to change the preference defaults.');
                }
                
                // update default/forced preferences
                $records = $backend->getMultiple(array_keys($data));
                foreach ($records as $preference) {
                    $preference->value = $data[$preference->getId()]['value'];
                    $preference->type = $data[$preference->getId()]['type'];
                    $backend->update($preference);
                }
                
            } else {
                //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($data, true));

                // set user prefs
                foreach ($data as $name => $value) {
                    $backend->doSpecialJsonFrontendActions($this, $name, $value['value'], $applicationName);
                    $backend->$name = $value['value'];
                }
            }
        }
    }
    
    /************************ protected functions ***************************/
    
    /**
     * returns multiple records prepared for json transport
     *
     * @param Tinebase_Record_RecordSet $_records Tinebase_Record_Abstract
     * @return array data
     */
    protected function _multipleRecordsToJson(Tinebase_Record_RecordSet $_records)
    {
        if (count($_records) == 0) {
            return array();
        }
        
        switch ($_records->getRecordClassName()) {
            case 'Tinebase_Model_Preference':
                // convert options xml to array
                foreach ($_records as $record) {
                    Tinebase_Core::getPreference()->convertOptionsToArray($record);
                }
                break;
        }
        
        $result = parent::_multipleRecordsToJson($_records);
        return $result;
    }
}
