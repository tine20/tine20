<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  EmailUser
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2014-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Johannes Nohl <lab@nohl.eu>
 */


/* CONFIG SETTINGS:
 * 
 * smtp => {
 *    [...]
      "simplemail":{
        "base":"ou=mail,ou=config,dc=bsp,dc=de",
        "scope":1,
        "skeleton":{
            "objectclass":["simplemail","mailrouting"],
            "mailUserDN":"%s"
        },
        "readonly":false,
        "storage_base":"ou=routing,ou=mail,ou=config,dc=bsp,dc=de",
        "storage_rdn":"cn=%u{tine20}", 
        "property_mapping":{
            "emailAliases":"mailalternateaddress", 
            "emailForwards":"mailforwardingaddress",
            "emailForwardOnly":"maildiscard:boolean"
        },
      },
 *    [...]
 *  }
 */


/**
 * plugin to handle smtp settings for simpleMail ldap schema or other 
 * custom schemes to store mail settings outside user's dn
 *
 * @package    Tinebase
 * @subpackage EmailUser
 */
class Tinebase_EmailUser_Smtp_LdapSimpleMailSchema extends Tinebase_EmailUser_Ldap implements Tinebase_EmailUser_Smtp_Interface
{
    /**
     * user properties mapping (HERE: properties of simpleMail node)
     * -> we need to use lowercase for ldap fields because ldap_fetch returns lowercase keys
     * -> if attribute should be unique (like true/false) make it boolean
     *
     * @var array
     */
    protected $_propertyMapping = array(
        'emailAliases'     => 'mailalternateaddress', 
        'emailForwards'    => 'mailforwardingaddress',
        'emailForwardOnly' => 'maildiscard:boolean'
    );

    /**
     * objectclasses required for users (original = uid of Tine 2.0)
     *
     * @var array
     */
    protected $_requiredObjectClass = array(
        'inetOrgPerson'
    );

    /**
     * simplemail config
     *
     * @var array
     */
    protected $_simpleMailConfig = NULL;

    /**
     * second ldap directory connection
     *
     * @var Tinebase_Ldap
     */
    protected $_ldap = NULL;

    /**
     * all properties of second ldap result (to compare before save)
     *
     * @var array
     */
    protected $_ldapRawData = NULL;

    /**
     * this class is NOT suitable for IMAP
     *
     * @const 
     */
    protected $_backendType = Tinebase_Config::SMTP;

    /**
     * the constructor 
     *
     */
    public function __construct(array $_options = array())
    {
        $config = Tinebase_EmailUser::getConfig(Tinebase_Config::SMTP);
        if (($this->_simpleMailConfig === null) && isset($config['simplemail']) && isset($config['simplemail']['base'])) {
            // load default values = simplemail scheme
            $this->_issetOrDefault($config['simplemail']['storage_base'], $config['simplemail']['base']);
            $this->_issetOrDefault($config['simplemail']['storage_rdn'], "cn=%u{tine20}");
            $this->_issetOrDefault($config['simplemail']['property_mapping'], array(
                'emailAliases' => "mailalternateaddress", 
                'emailForwards' => "mailforwardingaddress",
                'emailForwardOnly' => "maildiscard:boolean"
            ));
            $this->_issetOrDefault($config['simplemail']['skeleton'], array(
                'objectclass' => array("simplemail","mailrouting"),
                'mailUserDN' => "%s"
            ));
            $this->_issetOrDefault($config['simplemail']['readonly'], false);
            $this->_issetOrDefault($config['simplemail']['scope'], Zend_Ldap::SEARCH_SCOPE_SUB);

            $this->_simpleMailConfig = $config['simplemail'];
            $this->_propertyMapping = $config['simplemail']['property_mapping'];
            $this->_ldap = new Tinebase_Ldap(Tinebase_User::getBackendConfiguration());
        }
        else {
            $this->_simpleMailConfig = NULL;
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . '  SMTP config: simpleMail is missing ldap base!');
        }
    }

    /*
     * (non-PHPdoc)
     * Last call if Ldap user is removed to remove remaining data of this backend, too. 
     */
    public function inspectDeleteUser(Tinebase_Model_FullUser $_user){
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' delete simpleMail data for user '. $_user['accountLoginName']);

        if ($this->_ldapRawData === null) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' cannot delete simpleMail data from unknown account');
            return false;
        }

        foreach ($this->_propertyMapping as $property_name => $ldapName) {
            $this->_deletePropertyFromLdapRawData($property_name, false);
        }
        $this->_saveOrUpdateSpecialResultToLdap();
}

    /**
     * (non-PHPdoc)
     * @see Tinebase_EmailUser_Ldap::_user2Ldap()
     */
    protected function _user2Ldap(Tinebase_Model_FullUser $_user, array &$_ldapData, array &$_ldapEntry = array())
    {
        if ($this->_simpleMailConfig['readonly'] == true) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  readonly ldap simpleMail schema');
            return false;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  write ldap simpleMail schema');

        if ($this->_ldapRawData === null) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " unknown account (possibly new), guessed user's DN in ldap");
            $originalUserDn = Tinebase_User::getInstance()->generateDn($_user);
            $filter = $this->_prepareSpecialResultFilterForLdap($originalUserDn, $_user['accountLoginName']);
            $this->_getSpecialResultDataFromLdap($filter);
        }

        foreach ($this->_propertyMapping as $property_name => $ldapName) {

            if (!isset($_user['smtpUser'][$property_name])) {
                continue;
            }

            $existing = $this->_getPropertiesFromLdapRawData($ldapName);

            if (is_array($_user['smtpUser'][$property_name])) {
                foreach (array_diff($_user['smtpUser'][$property_name], $existing) as $property) {
                    $this->_addPropertyToLdapRawData($property_name, $property);
                }
                foreach (array_diff($existing, $_user['smtpUser'][$property_name]) as $property) {
                    $this->_deletePropertyFromLdapRawData($property_name, $property);
                }
            }
            elseif (substr($ldapName, -8) == ':boolean') {
                if ($_user['smtpUser'][$property_name] == 1) {
                    $this->_deletePropertyFromLdapRawData($property_name, false);
                    $this->_addPropertyToLdapRawData($property_name, true);
                }
                else {
                    $this->_deletePropertyFromLdapRawData($property_name, false);
                    // if also an undeletable entry sets this, it needs to be overwritten at last
                    if ($this->_getPropertiesFromLdapRawData($ldapName) == true) {
                        $this->_addPropertyToLdapRawData($property_name, false);
                    }
                }
            }

        }

        $this->_saveOrUpdateSpecialResultToLdap();
    }

    /**
     * (non-PHPdoc)
     * @see Tinebase_EmailUser_Ldap::_ldap2User()
     */
    protected function _ldap2User(Tinebase_Model_User $_user, array &$_ldapEntry)
    {
        $originalUser = parent::_ldap2User($_user, $_ldapEntry);

        if ($this->_ldapRawData === null) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' read ldap simpleMail schema');
            $filter = $this->_prepareSpecialResultFilterForLdap($_ldapEntry['dn'], $_user['accountLoginName']);
            $this->_getSpecialResultDataFromLdap($filter);
        }

        foreach ($this->_propertyMapping as $property => $ldapName) {
            $originalUser[$property] = $this->_getPropertiesFromLdapRawData($ldapName);
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' simpleMail - Tinebase_EmailUser combined with ldap: '. print_r($originalUser, true));

        return $originalUser;
    }

    /*
     * * * * * * * * H E L P E R S * * * * * * * * * * 
     */

    /**
     * (non-PHPdoc)
     */
    protected function _issetOrDefault(&$test, $default) 
    {
        if (!isset($test)) {
            $test = $default;
        }
    }

    /**
     * @var		string The primary location for user's properties
     * @var		string User's account name used for dn's uid=accountName mostly
     * @return	string The special result ldap filter for searching existing entries
     */
    protected function _prepareSpecialResultFilterForLdap($originalDn, $originalAccount) 
    {
        // replace wildcards in config
        array_walk_recursive($this->_simpleMailConfig, function(&$value, $property, $userdata ) {
            if (strpos($value, '%s') !== false) {
                $value = str_replace('%s', $userdata['dn'], $value);
            }
            elseif (strpos($value, '%u') !== false) {
                $value = str_replace('%u', $userdata['user'], $value);
            }
        }, array(
            'dn' => $originalDn,
            'user' => $originalAccount
        ));

        $filter = "&";
        foreach($this->_simpleMailConfig['skeleton'] as $attr => $val) {
            if (is_array($val)) {
                foreach ($val as $val_array) {
                    $filter .= '(' . $attr . '=' . $val_array . ')';
                }
            }
            else {
                $filter .= '(' . $attr . '=' . $val . ')';
            }  
        }
        return $filter;
    }

    /**
     * @var	string Ldap query filter 
     */
    protected function _getSpecialResultDataFromLdap($filter) 
    {
        $ldap = $this->_ldap->searchEntries(
                    Zend_Ldap_Filter::string($filter),
                    $this->_simpleMailConfig['base'], 
                    $this->_simpleMailConfig['scope'],
                    array()
                );

        /* Make sure, the managed rdn is last in array and properties are
         * ultimately read from this rdn (if entries are doubled)
         *
         * Order of array matters: 
         *  - all entries anywhere
         *  - entries within the storage path
         *  - the exact managed dn
         */
        $this->_ldapRawData = array();
        $managedPath = Zend_Ldap_Dn::fromString($this->_simpleMailConfig['storage_base'], Zend_Ldap_Dn::ATTR_CASEFOLD_LOWER);
        $managedDn = Zend_Ldap_Dn::fromString($this->_simpleMailConfig['storage_rdn'] . ',' . $this->_simpleMailConfig['storage_base'], Zend_Ldap_Dn::ATTR_CASEFOLD_LOWER);
        $managedDnExisting = false;

        foreach($ldap as $dn) {
            $dnArr = Zend_Ldap_Dn::fromString($dn['dn'], Zend_Ldap_Dn::ATTR_CASEFOLD_LOWER);
            if ($dnArr->toString() == $managedDn->toString()) { 
                array_push($this->_ldapRawData, $dn);
                $managedDnExisting = true;
            }
            elseif (Zend_Ldap_Dn::isChildOf($dnArr, $managedPath)) {
                ($managedDnExisting === true) ? array_splice($this->_ldapRawData, -1, 0, array($dn)) : array_push($this->_ldapRawData, $dn);
            }
            else {
                $dn['simplemail_readonly'] = true;
                array_unshift($this->_ldapRawData, $dn);
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' simpleMail - Tinebase_EmailUser combined with ldap: '. print_r($this->_ldapRawData, true));
    }

    /**
     * (non-PHPdoc)
     */
    protected function _saveOrUpdateSpecialResultToLdap()
    {
        foreach ($this->_ldapRawData as $dn) {
            if (isset($dn['simplemail_readonly'])) {
                continue;
            }

            $keepEntryThreshold = 0;
            foreach ($this->_propertyMapping as $property => $ldapName) {
                if (substr($ldapName, -8) == ':boolean') {
                    $ldapName = substr($ldapName, 0, -8);
                }
                if (!isset($dn[$ldapName])) {
                    $dn[$ldapName] = null;
                    $keepEntryThreshold++;
                }
                elseif ($dn[$ldapName] === null) {
                    $keepEntryThreshold++;
                }
            }

            // check for any values of worth to be saved (compared to minimal entry)
            $dn = array_change_key_case($dn);
            $skeleton = array_change_key_case($this->_simpleMailConfig['skeleton']);
            $skeleton = array_merge($skeleton, Zend_Ldap_Dn::fromString($this->_simpleMailConfig['storage_rdn'])->getRdn() );
            $skeleton['dn'] = true; // Zend_Ldap_Dn always carries the DN

            try {
                if (count(array_diff_key($dn, $skeleton)) > $keepEntryThreshold) {
                    $this->_ldap->save(Zend_Ldap_Dn::fromString($dn['dn']), $dn);
                }
                else {
                    $this->_ldap->delete(Zend_Ldap_Dn::fromString($dn['dn']), false);
                }
            }
            catch (Zend_Ldap_Exception $ldapException) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' simpleMail - cannot modify ldap entry ('. $dn['dn']. '): '. $ldapException->getMessage());
            }

        }
    }

    /**
     * (non-PHPdoc)
     */
    protected function _getPropertiesFromLdapRawData($ldapProperty) 
    {
        $properties = array();
        if (substr($ldapProperty, -8) == ':boolean') {
            $ldapProperty = substr($ldapProperty, 0, -8);
            $properties = (boolean) null;
        }
        foreach ($this->_ldapRawData as $dn) {
            if (isset($dn[$ldapProperty])) {
                if (is_array($properties)) {
                    $properties = array_merge($properties, $dn[$ldapProperty]);
                }
                else {
                    $properties = (boolean) ($dn[$ldapProperty][0] == 'TRUE') ? true : false;
                }
            }
        }
        return $properties;
    }

    /**
     * (non-PHPdoc)
     */
    protected function _addPropertyToLdapRawData($property, $value) 
    {
        $managedPath = Zend_Ldap_Dn::fromString($this->_simpleMailConfig['storage_base'], Zend_Ldap_Dn::ATTR_CASEFOLD_LOWER);
        $managedDn = Zend_Ldap_Dn::fromString($this->_simpleMailConfig['storage_rdn'] . ',' . $this->_simpleMailConfig['storage_base'], Zend_Ldap_Dn::ATTR_CASEFOLD_LOWER);

        // last elements holds managed DN (if any)
        $numberOfLdapElements = count($this->_ldapRawData);

        if (($numberOfLdapElements == 0) || isset($this->_ldapRawData[$numberOfLdapElements-1]['simplemail_readonly'])) {
            $this->_ldapRawData[0] = $this->_simpleMailConfig['skeleton'];
            $this->_ldapRawData[0]['objectclass'][] = 'top';
            $this->_ldapRawData[0]['dn'] = $managedDn->toString();
            $this->_ldapRawData[0] = array_merge($this->_ldapRawData[0], $managedDn->getRdn());
            $numberOfLdapElements ++;
        }

        $ldapProperty = $this->_propertyMapping[$property];
        if (substr($ldapProperty, -8) == ':boolean') {
            $ldapProperty = substr($ldapProperty, 0, -8);
            $this->_ldapRawData[$numberOfLdapElements-1][$ldapProperty] = array();
            $value = $value ? 'TRUE' : 'FALSE';
        }
        elseif (!isset($this->_ldapRawData[$numberOfLdapElements-1][$ldapProperty])) {
            $this->_ldapRawData[$numberOfLdapElements-1][$ldapProperty] = array();
        }

        if (!in_array($value, $this->_ldapRawData[$numberOfLdapElements-1][$ldapProperty])) {
            array_push($this->_ldapRawData[$numberOfLdapElements-1][$ldapProperty], $value);
        }
    }

    /**
     * (non-PHPdoc)
     */
    protected function _deletePropertyFromLdapRawData($property, $value) 
    {

        $ldapProperty = $this->_propertyMapping[$property];
        if (substr($ldapProperty, -8) == ':boolean') {
            $ldapProperty = substr($ldapProperty, 0, -8);
        }

        $managedPath = Zend_Ldap_Dn::fromString($this->_simpleMailConfig['storage_base'], Zend_Ldap_Dn::ATTR_CASEFOLD_LOWER);
        foreach ($this->_ldapRawData as $index => $dn) {

            // change only entries in storage_base path (if existing)
            if (isset($dn['simplemail_readonly']) || !isset($dn[$ldapProperty])) {
                continue;
            }

            if ($value === false) {
                //unset doesn't remove attribute in ldap
                $this->_ldapRawData[$index][$ldapProperty] = null;
            }
            elseif (in_array($value, $this->_ldapRawData[$index][$ldapProperty])) { 
                $del_index = array_search($value, $this->_ldapRawData[$index][$ldapProperty]);
                unset($this->_ldapRawData[$index][$ldapProperty][$del_index]);
                // don't keep empty arrays
                if (count($this->_ldapRawData[$index][$ldapProperty]) < 1) {
                    unset($this->_ldapRawData[$index][$ldapProperty]);
                }
            }

        }
    }

    /**
     * check if user exists already in email backend user table
     *
     * @param  Tinebase_Model_FullUser  $_user
     * @return boolean
     *
     * TODO implement
     */
    public function emailAddressExists(Tinebase_Model_FullUser $_user)
    {
        return false;
    }
}
