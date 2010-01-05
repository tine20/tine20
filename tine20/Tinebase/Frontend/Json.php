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
     * Wrapper for {@see Tinebase_Core::getCountrylist}
     * 
     *
     * @return array list of countrys
     */
    public function getCountryList()
    {
        return Tinebase_Translation::getCountryList();
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
        
        return array(
            'success'      => TRUE
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
     * @legacy
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return array with results array & totalcount (int)
     * 
     * @deprecated
     * @todo    remove this if it isn't used anymore
     */
    public function getGroups($filter, $sort, $dir, $start, $limit)
    {
        Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . " Calling deprecated function.");
        
        $filter = array(
            array('field' => 'query', 'operator' => 'contains', 'value' => $filter),
        );
        
        $paging = array(
            'sort'  => $sort,
            'dir'   => $dir,
            'start' => $start,
            'limit' => $limit
        );
        
        return $this->searchGroups($filter, $paging);
    }
    
    /**
     * Search for groups matching given arguments
     *
     * @param string $_filter json encoded
     * @param string $_paging json encoded
     * @return array
     * 
     * @todo replace this by Admin.searchGroups / getGroups (without acl check)? or add getGroupCount to Tinebase_Group
     */
    public function searchGroups($filter, $paging)
    {
    	$filterData = Zend_Json::decode($filter);
    	$pagingData = Zend_Json::decode($paging);
    	
    	$result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        // old fn style yet
        $sort = (isset($pagingData['sort']))    ? $pagingData['sort']   : 'name';
        $dir  = (isset($pagingData['dir']))     ? $pagingData['dir']    : 'ASC';
        $groups = Tinebase_Group::getInstance()->getGroups($filterData[0]['value'], $sort, $dir, $pagingData['start'], $pagingData['limit']);

        $result['results'] = $groups->toArray();
        $result['totalcount'] = Admin_Controller_Group::getInstance()->searchCount($filterData[0]['value']);
        
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
     * clears state
     * 
     * @param string $name
     * @return void
     */
    public function clearState($name)
    {
    	Tinebase_State::getInstance()->clearState($name);
    }
    
    /**
     * retuns all states
     * 
     * @return array of name => value
     */
    public function loadState()
    {
        return Tinebase_State::getInstance()->loadStateInfo();
    }
    
    /**
     * set state
     * 
     * @param string $name
     * @param string $value
     * @return void
     */
    public function setState($name, $value)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Setting state: {$name} -> {$value}");
        Tinebase_State::getInstance()->setState($name, $value);
    }
    
    
    /**
     * adds a new personal tag
     */
    public function saveTag($tag)
    {
        $tagData = Zend_Json::decode($tag);
        $inTag = new Tinebase_Model_Tag($tagData);
        
        if (strlen($inTag->getId()) < 40) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' creating tag: ' . print_r($inTag->toArray(), true));
            $outTag = Tinebase_Tags::getInstance()->createTag($inTag);
        } else {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' updating tag: ' .print_r($inTag->toArray(), true));
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
     * attach tag to multiple records identified by a filter
     * 
     * @param array  $filterData
     * @param string $filterName
     * @param mixed  $tag       string|array existing and non-existing tag
     * @return void
     */
    public function attachTagToMultipleRecords($filterData, $filterName, $tag)
    {
        // NOTE: this functino makes a new instance of a class whose name is given by user input.
        //       we need to do some sanitising first!
        list($appName, $modelString, $filterGroupName) = explode('_', $filterName);
        if($modelString !== 'Model') {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' spoofing attempt detected, affected account: ' . print_r(Tinebase_Core::getUser()->toArray(), TRUE));
            die('go away!');
        }
        
        if (! Tinebase_Core::getUser()->hasRight($appName, Tinebase_Acl_Rights_Abstract::RUN)) {
            throw new Tinebase_Exception_AccessDenied('No right to access application');
        }
        
        $filterGroup = new $filterName(array());
        if (! $filterGroup instanceof Tinebase_Model_Filter_FilterGroup) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' spoofing attempt detected, affected account: ' . print_r(Tinebase_Core::getUser()->toArray(), TRUE));
            die('go away!');
        }
        
        // at this point we are shure request is save ;-)
        $filterGroup->setFromArray($filterData);
        
        Tinebase_Tags::getInstance()->attachTagToMultipleRecords($filterGroup, $tag);
        return array('success' => true);
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
        
        $records = Tinebase_Notes::getInstance()->searchNotes($filter, $paging);
        $result = $this->_multipleRecordsToJson($records);
        
        return array(
            'results'       => $result,
            'totalcount'    => Tinebase_Notes::getInstance()->searchNotesCount($filter)
        );        
    }
    
    /**
     * get note types
     *
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
        // try to login user
        $success = (Tinebase_Controller::getInstance()->login($username, $password, $_SERVER['REMOTE_ADDR']) === TRUE); 
        
        if ($success) {
            $response = array(
				'success'       => TRUE,
                'account'       => Tinebase_Core::getUser()->getPublicUser()->toArray(),
				'jsonKey'       => Tinebase_Core::get('jsonKey'),
                'welcomeMessage' => "Welcome to Tine 2.0!"
            );
            
            if (Tinebase_Core::isRegistered(Tinebase_Core::USERCREDENTIALCACHE)) {
                $cacheId = Tinebase_Core::get(Tinebase_Core::USERCREDENTIALCACHE)->getCacheId();
                setcookie('usercredentialcache', base64_encode(Zend_Json::encode($cacheId)));
            } else {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Something went wrong with the CredentialCache / no CC registered.');
                $success = FALSE;
            }
        
        }

        if (! $success) {
            
            // reset credentials cache
            setcookie('usercredentialcache', '', time() - 3600);
            
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
        
        setcookie('usercredentialcache', '', time() - 3600);
                
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
            'serviceMap'       => Tinebase_Frontend_Http::getServiceMap(),
            'timeZone'         => Tinebase_Core::get(Tinebase_Core::USERTIMEZONE),
            'locale'           => array(
                'locale'   => $locale->toString(), 
                'language' => Zend_Locale::getTranslation($locale->getLanguage(), 'language', $locale),
                'region'   => Zend_Locale::getTranslation($locale->getRegion(), 'country', $locale),
            ),
            'version'          => array(
                'buildType'     => TINE20_BUILDTYPE,
                'codeName'      => TINE20_CODENAME,
                'packageString' => TINE20_PACKAGESTRING,
                'releaseTime'   => TINE20_RELEASETIME,
            ),
            'defaultUsername'   => $defaultUsername,
            'defaultPassword'   => $defaultPassword,
            'denySurveys'       => Tinebase_Core::getConfig()->denySurveys,
            'titlePostfix'      => Tinebase_Config::getInstance()->getConfig(Tinebase_Model_Config::PAGETITLEPOSTFIX, NULL, '')->value,
            'redirectUrl'       => Tinebase_Config::getInstance()->getConfig(Tinebase_Model_Config::REDIRECTURL, NULL, '')->value,
        );
        
        if (Tinebase_Core::isRegistered(Tinebase_Core::USER)) {
            $user = Tinebase_Core::getUser();
            $registryData += array(    
                'currentAccount'    => $user->toArray(),
                'userContact'       => Addressbook_Controller_Contact::getInstance()->getContactByUserId($user->getId())->toArray(),
                'accountBackend'    => Tinebase_User::getConfiguredBackend(),
                'jsonKey'           => Tinebase_Core::get('jsonKey'),
                'userApplications'  => $user->getApplications()->toArray(),
                'NoteTypes'         => $this->getNoteTypes(),
                'stateInfo'         => Tinebase_State::getInstance()->loadStateInfo(),
                'changepw'          => Tinebase_User::getBackendConfiguration('changepw', true),
                'mapPanel'          => Tinebase_Config::getInstance()->getConfig(Tinebase_Model_Config::MAPPANEL, NULL, TRUE)->value,
            );
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
        
        if (Tinebase_Core::getUser()) { 
            $userApplications = Tinebase_Core::getUser()->getApplications(TRUE);
            
            foreach($userApplications as $application) {
                
                $jsonAppName = $application->name . '_Frontend_Json';
                
                if(class_exists($jsonAppName)) {
                    $applicationJson = new $jsonAppName;
                    
                    $registryData[$application->name] = $applicationJson->getRegistryData();
                    $registryData[$application->name]['rights'] = Tinebase_Core::getUser()->getRights($application->name);
                    $registryData[$application->name]['config'] = Tinebase_Config::getInstance()->getConfigForApplication($application);
                    $registryData[$application->name]['customfields'] = Tinebase_CustomField::getInstance()->getCustomFieldsForApplication($application)->toArray();
                    
                    // add preferences for app
                    $appPrefs =Tinebase_Core::getPreference($application->name);
                    if ($appPrefs !== NULL) {
                        $allPrefs = $appPrefs->getAllApplicationPreferences();
                        foreach($allPrefs as $pref) {
                            $registryData[$application->name]['preferences'][$pref] = $appPrefs->{$pref};
                        }
                    }
                }
            }
        } else {
            $registryData['Tinebase'] = $this->getRegistryData();
        }
        
        return $registryData;
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' returning registry data by dying to avoid servers success property to be part of the registry.');
        //die(Zend_Json::encode($registryData));
    }


    /**
     * search / get custom field values
     *
     * @param string $filter json encoded filter array
     * @param string $paging json encoded pagination info
     * @return array
     */
    public function searchCustomFieldValues($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Tinebase_CustomField::getInstance(), 'Tinebase_Model_CustomField_ValueFilter');
        return $result;
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
        
        if (! empty($decodedFilter)) {
            $filter->setFromArrayInUsersTimezone($decodedFilter);
        }
        
        // make sure account is set in filter
        $userId = Tinebase_Core::getUser()->getId();
        if (! $filter->isFilterSet('account')) {
            $accountFilter = $filter->createFilter('account', 'equals', array(
                'accountId' => $userId, 
                'accountType' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER
            ));
            $filter->addFilter($accountFilter);
        } else {
            // only admins can search for other users prefs
            $accountFilter = $filter->getAccountFilter();
            $accountFilterValue = $accountFilter->getValue(); 
            if ($accountFilterValue['accountId'] != $userId && $accountFilterValue['accountType'] == Tinebase_Acl_Rights::ACCOUNT_TYPE_USER) {
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
            
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($filter->toArray(), true));
            
            $paging = new Tinebase_Model_Pagination(array(
                'dir'       => 'ASC',
                'sort'      => array('name')
            ));
            $allPrefs = $backend->search($filter, $paging);
            
            // get single matching preferences for each different pref
            $records = $backend->getMatchingPreferences($allPrefs);
            
            // add default prefs if not already in array
            if (! $filter->isFilterSet('name')) {
                $missingDefaultPrefs = array_diff($backend->getAllApplicationPreferences(), $records->name);
                foreach ($missingDefaultPrefs as $prefName) {
                    $records->addRecord($backend->getPreferenceDefaults($prefName));
                }
            }
            
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($records->toArray(), true));
            
            $result = $this->_multipleRecordsToJson($records);

            
            // add translated labels and descriptions
            $translations = $backend->getTranslatedPreferences();
            foreach ($result as &$prefArray) {
                if (isset($translations[$prefArray['name']])) {
                    $prefArray = array_merge($prefArray, $translations[$prefArray['name']]);
                } else {
                    $prefArray = array_merge($prefArray, array('label' => $prefArray['name']));
                }
            }
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
     * @return array with the changed prefs
     */
    public function savePreferences($data, $adminMode)
    {
        $decodedData = Zend_Json::decode($data);
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($decodedData, true));
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($adminMode, true));
        
        $result = array();
        foreach ($decodedData as $applicationName => $data) {
            
            $backend = Tinebase_Core::getPreference($applicationName); 
            
            if (! $backend instanceof Tinebase_Preference_Abstract) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' No preferences class found for app ' . $applicationName);
                continue;
            }
            
            if ($adminMode == TRUE) {
                // only admins are allowed to update app pref defaults/forced prefs
                if (!Tinebase_Acl_Roles::getInstance()->hasRight($applicationName, Tinebase_Core::getUser()->getId(), Tinebase_Acl_Rights_Abstract::ADMIN)) {
                    throw new Tinebase_Exception_AccessDenied('You are not allowed to change the preference defaults.');
                }
                
                // create prefs that don't exist in the db
                foreach($data as $id => $prefData) {
                    if (preg_match('/^default/', $id)) {
                        $newPref = $backend->getPreferenceDefaults($prefData['name']);
                        $newPref->value = $prefData['value'];
                        $newPref->type = $prefData['type'];
                        unset($newPref->id);
                        $backend->create($newPref);
                        
                        unset($data[$id]);
                    }
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
                    $result[$applicationName][] = array('name' => $name, 'value' => $value['value']);
                }
            }
        }
        
        return array(
            'status'    => 'success',
            'results'   => $result
        );
    }
    
    /************************ protected functions ***************************/
    
    /**
     * returns multiple records prepared for json transport
     *
     * @param Tinebase_Record_RecordSet $_records Tinebase_Record_Abstract
     * @return array data
     */
    protected function _multipleRecordsToJson(Tinebase_Record_RecordSet $_records, $_filter=NULL)
    {
        if (count($_records) == 0) {
            return array();
        }
        
        switch ($_records->getRecordClassName()) {
            case 'Tinebase_Model_Preference':
                foreach ($_records as $record) {
                    // get application
                    if (! isset($app) || $record->application_id != $app->getId()) {
                        $app = Tinebase_Application::getInstance()->getApplicationById($record->application_id);
                    }
                    
                    // convert options xml to array
                    $preference = Tinebase_Core::getPreference($app->name);
                    if ($preference) {
                        $preference->convertOptionsToArray($record);
                    } else {
                        throw new Tinebase_Exception_NotFound('No preference class found for app ' . $app->name);
                    }
                }
                break;
        }
        
        $result = parent::_multipleRecordsToJson($_records, $_filter);
        return $result;
    }
}
