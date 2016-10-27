<?php

/**
 * contacts ldap sync backend
 *
 * @package     Addressbook
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * contacts ldap sync backend
 *
 * this is just a sync backend, not the main backend responsible for persisting the record
 *
 * NOTE: LDAP charset is allways UTF-8 (RFC2253) so we don't have to cope with
 *       charset conversions here ;-)
 *
 * @package     Addressbook
 * @subpackage  Backend
 */
class Addressbook_Backend_Sync_Ldap implements Tinebase_Backend_Interface
{
    /**
     * @var Tinebase_Ldap
     */
    protected $_ldap = NULL;

    /**
     * attribute mapping
     *
     * @var array
     */
    protected  $_attributesMap = array(

        /**
         * RFC2798: Internet Organizational Person
         */
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

        /**
         * Mozilla LDAP Address Book Schema (alpha)
         *
         * @link https://wiki.mozilla.org/MailNews:Mozilla_LDAP_Address_Book_Schema
         * @link https://wiki.mozilla.org/MailNews:LDAP_Address_Books#LDAP_Address_Book_Schema
         */

          /*  'adr_one_street2'       => 'mozillaworkstreet2',
            'adr_one_countryname'   => 'c', // 2 letter country code
            'adr_two_street'        => 'mozillahomestreet',
            'adr_two_street2'       => 'mozillahomestreet2',
            'adr_two_locality'      => 'mozillahomelocalityname',
            'adr_two_region'        => 'mozillahomestate',
            'adr_two_postalcode'    => 'mozillahomepostalcode',
            'adr_two_countryname'   => 'mozillahomecountryname',
            'email_home'            => 'mozillasecondemail',
            'url_home'              => 'mozillahomeurl',*/
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
    );

    protected $_objectClasses = array(
        'inetOrgPerson' => array(
            'cn',
            'sn',
        ),
        //'mozillaAbPersonAlpha' => array(
            //'cn',
        //),
    );

    /**
     * base DN
     *
     * @var string
     */
    protected $_baseDN = NULL;

    /**
     * constructor
     *
     * @param array $_options
     * @throws Tinebase_Exception_Backend_Ldap
     */
    public function __construct(array $_options)
    {
        if (isset($_options['attributesMap']) && is_array($_options['attributesMap']) && count($_options['attributesMap']) > 0) {
            $this->_attributesMap = $_options['attributesMap'];
        }

        if (!isset($_options['baseDN']) || empty($_options['baseDN']) || !is_string($_options['baseDN'])) {
            throw new Tinebase_Exception_Backend_Ldap('baseDN not set in configuration');
        }
        $this->_baseDN = $_options['baseDN'];

        // use user backend configuration or own ldap connection configuration?
        if (isset($_options['ldapConnection'])) {
            $ldapOptions = $_options['ldapConnection'];
        } else {
        //if (isset($_options['useUserBackend'])) {
            $ldapOptions = Tinebase_User::getBackendConfiguration();
        }

        $this->_ldap = new Tinebase_Ldap($ldapOptions);
        try {
            $this->_ldap->bind();
        } catch (Zend_Ldap_Exception $zle) {
            throw new Tinebase_Exception_Backend_Ldap('Could not bind to LDAP: ' . $zle->getMessage());
        }
    }

    /**
     * Search for records matching given filter
     *
     *
     * @param  Tinebase_Model_Filter_FilterGroup    $_filter
     * @param  Tinebase_Model_Pagination            $_pagination
     * @param  array|string|boolean                 $_cols columns to get, * per default / use self::IDCOL or TRUE to get only ids
     * @return Tinebase_Record_RecordSet
     * @throws Tinebase_Exception_NotImplemented
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_cols = '*')
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * Gets total count of search with $_filter
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return int
     * @throws Tinebase_Exception_NotImplemented
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter)
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * Return a single record
     *
     * @param string $_id
     * @param boolean $_getDeleted get deleted records
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_NotImplemented
     */
    public function get($_id, $_getDeleted = FALSE)
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * Returns a set of records identified by their id's
     *
     * @param string|array $_ids Ids
     * @param array $_containerIds all allowed container ids that are added to getMultiple query
     * @return Tinebase_Record_RecordSet of Tinebase_Record_Interface
     * @throws Tinebase_Exception_NotImplemented
     */
    public function getMultiple($_ids, $_containerIds = NULL)
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * Gets all entries
     *
     * @param string $_orderBy Order result by
     * @param string $_orderDirection Order direction - allowed are ASC and DESC
     * @throws Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_RecordSet
     * @throws Tinebase_Exception_NotImplemented
     */
    public function getAll($_orderBy = 'id', $_orderDirection = 'ASC')
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * Create a new persistent contact
     *
     * @param  Tinebase_Record_Interface $_record
     * @return Tinebase_Record_Interface|void
     */
    public function create(Tinebase_Record_Interface $_record)
    {
        /** @var Addressbook_Model_Contact $_record */
        $dn = $this->_generateDn($_record);

        if ($this->_ldap->getEntry($dn) !== null) {
            $this->update($_record);
            return;
        }

        $ldapData = $this->_contact2ldap($_record);

        $ldapData['objectclass'] = array_keys($this->_objectClasses);

        foreach($this->_objectClasses as $reqAttributes) {
            foreach($reqAttributes as $reqAttrb) {
                if (!isset($ldapData[$reqAttrb]) || empty($ldapData[$reqAttrb])) {
                    $ldapData[$reqAttrb] = '-';
                }
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE))
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' dn: ' . $dn . ' ldapData: ' . print_r($ldapData, true));

        $this->_ldap->add($dn, $ldapData);
    }

    /**
     * Upates an existing persistent record
     *
     * @param  Tinebase_Record_Interface $_record
     * @return NULL|Tinebase_Record_Interface|void
     */
    public function update(Tinebase_Record_Interface $_record)
    {
        /** @var Addressbook_Model_Contact $_record */
        $dn = $this->_generateDn($_record);

        if ($this->_ldap->getEntry($dn) === null) {
            $this->create($_record);
            return;
        }

        $ldapData = $this->_contact2ldap($_record);

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE))
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' dn: ' . $dn . ' ldapData: ' . print_r($ldapData, true));

        $this->_ldap->update($dn, $ldapData);
    }

    /**
     * Updates multiple entries
     *
     * @param array $_ids to update
     * @param array $_data
     * @return integer number of affected rows
     * @throws Tinebase_Exception_NotImplemented
     */
    public function updateMultiple($_ids, $_data)
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * Deletes one existing persistent record
     *
     * @param Tinebase_Record_Interface $_record
     */
    public function delete($_record)
    {
        /** @var Addressbook_Model_Contact $_record */
        $dn = $this->_generateDn($_record);

        $this->_ldap->delete($dn);
    }

    /**
     * get backend type
     *
     * @return string
     */
    public function getType()
    {
        return 'Ldap';
    }

    /**
     * @param Addressbook_Model_Contact $_record
     * @return string
     */
    protected function _generateDN(Addressbook_Model_Contact $_record)
    {
        return 'uid=' . Zend_Ldap_Filter_Abstract::escapeValue($_record->type===Addressbook_Model_Contact::CONTACTTYPE_USER?
                Tinebase_User::getInstance()->getFullUserById($_record->account_id)->accountLoginName:
                $_record->getId())
            . ',' . $this->_baseDN;
    }

    /**
     * @param Addressbook_Model_Contact $_record
     * @return array
     */
    protected function _contact2ldap(Addressbook_Model_Contact $_record)
    {
        $ldapData = array();

        foreach($_record as $key => $val) {
            if (isset($this->_attributesMap[$key])) {
                if ($key === 'jpegphoto') {
                    if ($val === '0' || $val === 0)
                    {
                        $val = '';
                    } elseif ($val === '1' || $val === 1) {
                        $abs = new Addressbook_Backend_Sql();
                        $val = $abs->getImage($_record->getId());
                    }
                }
                $ldapData[$this->_attributesMap[$key]] = (is_null($val) ? '' : $val);
            }
        }

        return $ldapData;
    }
}