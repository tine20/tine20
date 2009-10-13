<?php

/**
 * contacts ldap backend
 * 
 * @package     Addressbook
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * contacts ldap backend
 * 
 * NOTE: LDAP charset is allways UTF-8 (RFC2253) so we don't have to cope with
 *       charset conversions here ;-)
 * 
 * @package     Addressbook
 * @subpackage  Backend
 */
class Addressbook_Backend_Ldap implements Tinebase_Backend_Interface
{
    /**
     * backend type constant
     */
    const TYPE = 'Ldap';
    
    /**
     * date representation used by ldap
     */
    const LDAPDATEFORMAT = 'YYYYmmddHHmmss';
    
    /**
     * ldap directory connection
     *
     * @var Tinebase_Ldap
     */
    protected $_ldap = NULL;
    
    /**
     * base dn
     *
     * @var string
     */
    protected $_baseDn = NULL;
    
    /**
     * options object 
     * @see Zend_Ldap options + userDn + groupDn
     *
     * @var object
     */
    protected $_options = NULL;
    
    /**
     * list of reqired schemas 
     *
     * @var array
     */
    protected $_requierdSchemas = array(
        'posixaccount', 
        'openldap', 
        'inetorgperson'
    );
    
    /**
     * list of available schemas in ldap server
     *
     * @todo get this list from $this->_ldap
     * @var array
     */
    protected $_availableSchemas = array(
        'posixaccount', 
        'openldap', 
        'inetorgperson', 
        'mozillaabpersonalpha', 
        'evolutionperson'
    );
    
    /**
     * list of supportetd tine contact record fields
     * 
     * NOTE: this list depends on the supportet schemas by current ldap server
     * @see $this->__attributesMaps
     *
     * @var array
     */
    protected $_supportedRecordFields = NULL;
    
    /**
     * list of supported attributes by current ldap server
     * 
     * NOTE: dynamically determined by supported schemas
     *
     * @var array
     */
    protected $_supportedLdapAttributes = NULL;
    
    /**
     * attributes mapping for supported schemas
     * 
     * NOTE: The mapping is _not_ one-to-one. one recordField could map to n ldapAttributes.
     *       When reading a ldap entry we map the first non empty value.
     *       When saving a ldap entry we write all n attributes with the same one record value //@todo to be discussed!
     * 
     * @var array schemaName => array (recordField => ldapAttribute)
     */
    protected  $_attributesMaps = array(
        /**
         * Abstraction of an account with POSIX attributes
         * NOTE: For contacts we only use the reference to the account
         */
        'posixaccount' => array(
            'account_id'    => 'uidnumber',
        ),
        
        /**
         * generic openldap attributes
         */
        'openldap' => array(
            'id'                    => 'entryuuid',
            'created_by'            => 'creatorsname',
            'creation_time'         => 'createtimestamp',
            'last_modified_by'      => 'modifiersname',
            'last_modified_time'    => 'modifytimestamp',
        ),
        
        /**
         * RFC2798: Internet Organizational Person
         */
        'inetorgperson' => array(
            'n_fn'                  => 'cn',
            'n_given'               => 'givenname',
            'n_family'              => 'sn',
            'sound'                 => 'audio',
            'note'                  => 'description',
            'url'                   => 'labeleduri',
            'org_name'              => 'o',
            'org_unit'              => 'ou',
            'title'                 => 'title',
            'adr_one_street'        => 'street',
            'adr_one_locality'      => 'l',
            'adr_one_region'        => 'st',
            'adr_one_postalcode'    => 'postalcode',
            'tel_work'              => 'telephonenumber',
            'tel_home'              => 'homephone',
            'tel_fax'               => 'facsimiletelephonenumber',
            'tel_cell'              => 'mobile',
            'tel_pager'             => 'pager',
            'email'                 => 'mail',
            'room'                  => 'roomnumber',
            'jpegphoto'             => 'jpegphoto',
            'n_fileas'              => 'displayname',
            'label'                 => 'postaladdress',
            'pubkey'                => 'usersmimecertificate',
        ),
        
        /**
         * Mozilla LDAP Address Book Schema (alpha)
         * 
         * @link https://wiki.mozilla.org/MailNews:Mozilla_LDAP_Address_Book_Schema
         * @link https://wiki.mozilla.org/MailNews:LDAP_Address_Books#LDAP_Address_Book_Schema
         */
        'mozillaabpersonalpha' => array(
            'adr_one_street2'       => 'mozillaworkstreet2',
            'adr_one_countryname'   => 'c', // 2 letter country code
            'adr_two_street'        => 'mozillahomestreet',
            'adr_two_street2'       => 'mozillahomestreet2',
            'adr_two_locality'      => 'mozillahomelocalityname',
            'adr_two_region'        => 'mozillahomestate',
            'adr_two_postalcode'    => 'mozillahomepostalcode',
            'adr_two_countryname'   => 'mozillahomecountryname',
            'email_home'            => 'mozillasecondemail',
            'url_home'              => 'mozillahomeurl',
            //'' => 'displayName'
            //'' => 'mozillaCustom1'
            //'' => 'mozillaCustom2'
            //'' => 'mozillaCustom3'
            //'' => 'mozillaCustom4'
            //'' => 'mozillaHomeUrl'
            //'' => 'mozillaNickname'
            //'' => 'mozillaUseHtmlMail'
            //'' => 'nsAIMid'
            //'' => 'postOfficeBox'
        ),
        
        /**
         * Mozilla LDAP Address Book Schema
         * similar to the newer mozillaAbPerson, but uses mozillaPostalAddress2 instead of mozillaStreet2
         * 
         * @deprecated 
         * @link https://bugzilla.mozilla.org/attachment.cgi?id=104858&action=view
         */
        'mozillaorgperson' => array(
            'adr_one_street2'       => 'mozillapostaladdress2',
            'adr_one_countryname'   => 'c',  // 2 letter country code
            'adr_one_countryname'   => 'co', // human readable country name, must be after 'c' to take precedence on read!
            'adr_two_street'        => 'mozillahomestreet',
            'adr_two_street2'       => 'mozillahomepostaladdress2',
            'adr_two_locality'      => 'mozillahomelocalityname',
            'adr_two_region'        => 'mozillahomestate',
            'adr_two_postalcode'    => 'mozillahomepostalcode',
            'adr_two_countryname'   => 'mozillahomecountryname',
            'email_home'            => 'mozillasecondemail',
            'url_home'              => 'mozillahomeurl',
        ),
        
        /**
         * Objectclass geared to Evolution Usage
         * 
         * @link http://projects.gnome.org/evolution/index.shtml
         */
        'evolutionperson' => array(
            'bday'          => 'birthdate',
            'note'          => 'note',
            'tel_car'       => 'carphone',
            'tel_prefer'    => 'primaryphone',
            'cat_id'        => 'category',  // special handling in _egw2evolutionperson method
            'role'          => 'businessrole',
            'tel_assistent' => 'assistantphone',
            'assistent'     => 'assistantname',
            'n_fileas'      => 'fileas',
            'tel_fax_home'  => 'homefacsimiletelephonenumber',
            'freebusy_uri'  => 'freeBusyuri',
            'calendar_uri'  => 'calendaruri',
            'tel_other'     => 'otherphone',
            'tel_cell_private' => 'callbackphone',  // not the best choice, but better then nothing
            //'' => 'managerName'
            //'' => 'otherPostalAddress'
            //'' => 'mailer'
            //'' => 'anniversary'
            //'' => 'spouseName'
            //'' => 'companyPhone' 
            //'' => 'otherFacsimileTelephoneNumber'
            //'' => 'radio'
            //'' => 'telex'
            //'' => 'tty'
            //'' => 'categories' //(deprecated)
        ),
        
        /**
         * unsupported tine fields:
         */
        //'is_deleted'            => '',
        //'deleted_time'          => '',
        //'deleted_by'            => '',
        
        //'container_id'          => '',
        //'salutation_id'         => '',
        
        //'tz'                    => '',
        //'geo'                   => '',
        
    );
    
    /**
     * constructs a contacts ldap backend
     *
     * @param  array $options Options used in connecting, binding, etc.
     */
    public function __construct(array $_options) 
    {
        $this->_options = $_options;
        $this->_ldap = new Tinebase_Ldap($_options);
        $this->_ldap->bind();
        
        //$this->_baseDn = "ou=contacts,ou=von-und-zu-weiss.de,dc=d80-237-148-76";
        $this->baseDn = $this->_options['userDn'];
        
        $this->_checkSchemas();
        
    }
    
    /**
     * Search for records matching given filter
     *
     * @param  Tinebase_Record_Interface  $_filter
     * @param  Tinebase_Model_Pagination $_pagination
     * @return Tinebase_Record_RecordSet
     */
    public function search(Tinebase_Record_Interface $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL)
    {
        
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Record_Interface $_filter
     * @return int
     */
    public function searchCount(Tinebase_Record_Interface $_filter)
    {
        
    }
    
    /**
     * returns a ldap filter string
     *
     * @param  Tinebase_Record_Abstract $_filter
     * @return String
     */
    protected function _getFilter(Tinebase_Record_Abstract $_filter) {
        
    }
    
    /**
     * Return a single record
     *
     * @param string $_id uuid / uidnumber ???
     * @return Tinebase_Record_Interface
     */
    public function get($_id)
    {
        $contactData = $this->_ldap->fetch($this->_baseDn, "entryuuid=$_id", $this->_getSupportedLdapAttributes());
        
        if (! $contactData) {
            throw new Addressbook_Exception_NotFound("Contact with id $_id not found.");
        }
        $contact = $this->_ldap2Contacts(array($contactData))->offsetGet(0);
        $contact->jpegphoto = $this->_ldap->fetchBinaryAttribute($this->_baseDn, "entryuuid=$_id", 'jpegphoto');
        
        return $contact;
    }
    
    /**
     * Returns a set of contacts identified by their id's
     * 
     * @param  string|array $_id Ids
     * @param array $_containerIds all allowed container ids that are added to getMultiple query [not used]
     * @return Tinebase_RecordSet of Tinebase_Record_Interface
     */
    public function getMultiple($_ids, $_containerIds = NULL)
    {
        $ids = is_array($_ids) ? $_ids : (array) $_ids;
        
        $idFilter = '';
        foreach ($ids as $id) {
            $idFilter .= "(entryuuid=$id)";
        }
        $filter = "(&(objectclass=inetorgperson)(|$idFilter))";
        
        $rawLdapData = $this->_ldap->fetchAll($this->_baseDn, $filter, $this->_getSupportedLdapAttributes());
        
        $contacts = $this->_ldap2Contacts($rawLdapData);
        
        return $contacts;
    }

    /**
     * Gets all entries
     *
     * @param string $_orderBy Order result by
     * @param string $_orderDirection Order direction - allowed are ASC and DESC
     * @throws Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_RecordSet
     */
    public function getAll($_orderBy = 'id', $_orderDirection = 'ASC')
    {
        if(! in_array($_orderBy, $this->_getSupportedRecordFields())) {
            throw new Tinebase_Exception_InvalidArgument('$_orderBy field "'. $_orderBy . '" is not supported by this backend instance');
        }
        
        $rawLdapData = $this->_ldap->fetchAll($this->_baseDn, 'objectclass=inetorgperson', $this->_getSupportedLdapAttributes());
        
        $contacts = $this->_ldap2Contacts($rawLdapData);
        
        $contacts->sort($_orderBy, $_orderDirection);

        return $contacts;
    }
    
    /**
     * Create a new persistent contact
     *
     * @param  Tinebase_Record_Interface $_record
     * @return Tinebase_Record_Interface
     */
    public function create(Tinebase_Record_Interface $_record)
    {
        
    }
    
    /**
     * Upates an existing persistent record
     *
     * @param  Tinebase_Record_Interface $_contact
     * @return Tinebase_Record_Interface
     */
    public function update(Tinebase_Record_Interface $_record)
    {
        
    }
    
    /**
     * Deletes one or more existing persistent record(s)
     *
     * @param string|array $_identifier
     * @return void
     */
    public function delete($_identifier)
    {
        
    }
    
    /**
     * fetch one contact of a user identified by his user_id
     *
     * @param   int $_userId
     * @return  Addressbook_Model_Contact 
     * @throws  Addressbook_Exception_NotFound if contact not found
     */
    public function getByUserId($_userId)
    {
        $userId = Tinebase_Model_User::convertUserIdToInt($_userId);
        
        $contactData = $this->_ldap->fetch($this->_baseDn, "uidnumber=$userId", $this->_getSupportedLdapAttributes());
        
        if (! $contactData) {
            throw new Addressbook_Exception_NotFound("Contact with user id $_userId not found.");
        }
        $contact = $this->_ldap2Contacts(array($contactData))->offsetGet(0);
        $contact->jpegphoto = $this->_ldap->fetchBinaryAttribute($this->_baseDn, "uidnumber=$userId", 'jpegphoto');
        
        return $contact;
    }
    
    /**
     * get backend type
     *
     * @return string
     */
    public function getType()
    {
        return self::TYPE;
    }
    
    /**
     * returns a record set of Addressbook_Model_Contacts filled from raw ldap data
     * 
     * @param  array $_data raw ldap contacts data
     * @return Tinebase_Record_RecordSet of Addressbook_Model_Contacts
     */
    protected function _ldap2Contacts($_data)
    {
        $contactsArray = array();
        
        foreach ($_data as $ldapEntry) {
            $contactArray = $this->_mapLdap2Contact($ldapEntry);
            array_push($contactsArray, $contactArray);
        }
        
        $contacts = new Tinebase_Record_RecordSet('Addressbook_Model_Contact', $contactsArray, true, self::LDAPDATEFORMAT);
        
        // dn to userids -> later lets just unset this data for the moment
        $contacts->created_by = '';
        $contacts->last_modified_by = '';
        
        return $contacts;
    }
    
    /**
     * maps data of raw ldapEntry to fields of a contact record
     * 
     * @param array natvie ldap data of an entry
     * @return array raw contact data
     */
    protected function _mapLdap2Contact($_data)
    {
        $contactArray = array();
        $schemaMap = array();
        
        // look for each supported record filed if we find a value in the data
        foreach ($this->_getSupportedRecordFields() as $field) {
            foreach ($this->_attributesMaps as $schemaName => $mapping) {
                if(in_array($schemaName, $this->_availableSchemas)) {
                    if (array_key_exists($field, $mapping)) {
                        
                        $attributeName = $mapping[$field];
                        if (array_key_exists($attributeName, $_data) && $_data[$attributeName]['count'] > 0) {
                            // heureka! we found a value for the current field.
                            // Lets take it and search for the next field.
                            $schemaMap[$field] = $schemaName;
                            if ($_data[$attributeName]['count'] == 1) {
                                $contactArray[$field] = $_data[$attributeName][0];
                            } else {
                                unset($_data[$attributeName]['count']);
                                $contactArray[$field] = $_data[$attributeName];
                            }
                            break;
                        }
                    }
                }
            }
        }
        
        $this->_transformLdap2Contact($contactArray, $schemaMap);
        return $contactArray;
    }
    
    /**
     * do final transformation from ldap to contact after mapping
     *
     * @param array $_contactArray $fieldName => $fieldValue
     * @param array $_schemaMap $fieldName => $schemaName (origin of value)
     */
    protected function _transformLdap2Contact(&$_contactArray, $_schemaMap)
    {
        // find out n_prefix/n_suffix and clear n_given (inetorgperson)
        // adopt country codes (do we need a mapping?)
    }
    
    /**
     * checks if all required schemas are available
     * 
     * @throws Addressbook_Exception_Backend
     * @return void
     */
    protected function _checkSchemas()
    {
        $missingSchemas = array_diff($this->_requierdSchemas, $this->_availableSchemas);
        if (count($missingSchemas) > 0) {
            throw new Addressbook_Exception_Backend("missing required schemas: " . print_r($missingSchemas, true));
        }
    }
    
    /**
     * returns supported contact attributes of the ldap entry
     *
     * @return array of ldap attributes 
     */
    protected function _getSupportedLdapAttributes()
    {
        if (! $this->_supportedLdapAttributes) {
            $attributes = array();
            
            foreach ($this->_attributesMaps as $schemaName => $mapping) {
                if(in_array($schemaName, $this->_availableSchemas)) {
                    $attributes = array_merge($attributes, array_values($mapping));
                }
            }
            $this->_supportedLdapAttributes = array_values(array_unique($attributes));
        }
        
        return $this->_supportedLdapAttributes;
    }
    
    /**
     * returns supported contact record fields
     *
     * @return array
     */
    public function _getSupportedRecordFields()
    {
        if (! $this->_supportedRecordFields) {
            $fields = array();
            
            foreach ($this->_attributesMaps as $schemaName => $mapping) {
                if(in_array($schemaName, $this->_availableSchemas)) {
                    $fields = array_merge($fields, array_keys($mapping));
                }
            }
            $this->_supportedRecordFields = array_values(array_unique($fields));
        }
        
        return $this->_supportedRecordFields;
    }
}
