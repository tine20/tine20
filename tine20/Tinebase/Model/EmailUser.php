<?php

/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  EmailUser
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @todo        make default quota configurable
 */

/**
 * class Tinebase_Model_EmailUser
 * 
 * - this class contains all email specific user settings like quota, forwards, ...
 * 
 * @package     Tinebase
 * @subpackage  LDAP
 *
 * @property string $emailUID
 * @property string $emailGID
 * @property string $emailMailQuota
 * @property string $emailMailSize
 * @property string $emailSieveQuota
 * @property string $emailSieveSize
 * @property string $emailUserId
 * @property string $emailLastLogin
 * @property string $emailPassword
 * @property string $emailForwardOnly
 * @property Tinebase_Record_RecordSet $emailAliases (Tinebase_Model_EmailUser_Alias)
 * @property Tinebase_Record_RecordSet $emailForwards (Tinebase_Model_EmailUser_Forward)
 * @property string $emailAddress
 * @property string $emailLoginname
 * @property string $emailUsername
 * @property string $emailHost
 * @property string $emailPort
 * @property string $emailSecure
 * @property string $emailAuth
 */
class Tinebase_Model_EmailUser extends Tinebase_Record_Abstract 
{
    protected $_identifier = 'emailUserId';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';
    
    /**
     * validators / fields
     *
     * @var array
     */
    protected $_validators = array(
        'emailUID'          => array('allowEmpty' => true),
        'emailGID'          => array('allowEmpty' => true),
        'emailMailQuota'    => array('allowEmpty' => true, 'Digits'),
        'emailMailSize'     => array('allowEmpty' => true),
        'emailSieveQuota'   => array('allowEmpty' => true, 'Digits'),
        'emailSieveSize'    => array('allowEmpty' => true),
        'emailUserId'       => array('allowEmpty' => true),
        'emailLastLogin'    => array('allowEmpty' => true),
        'emailPassword'     => array('allowEmpty' => true),
        'emailForwards'     => array('allowEmpty' => true, Zend_Filter_Input::DEFAULT_VALUE => array()),
        'emailForwardOnly'  => array('allowEmpty' => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'emailAliases'      => array('allowEmpty' => true, Zend_Filter_Input::DEFAULT_VALUE => array()),
        'emailAddress'      => array('allowEmpty' => true),
        'emailLoginname'    => array('allowEmpty' => true),
    // dbmail username (tine username + dbmail domain)
        'emailUsername'     => array('allowEmpty' => true),
        'emailHost'         => array('allowEmpty' => true),
        'emailPort'         => array('allowEmpty' => true),
        'emailSecure'       => array('allowEmpty' => true),
        'emailAuth'         => array('allowEmpty' => true)
    );
    
    /**
     * datetime fields
     *
     * @var array
     */
    protected $_datetimeFields = array(
        'emailLastLogin'
    );

    /**
     * list of zend inputfilter
     *
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = [
        'emailForwardOnly'      => [['Empty', 0]],
        'emailMailSize'         => [['Empty', 0]],
        'emailMailQuota'        => [['Empty', 0]],
        'emailForwards'         => [['Empty', []]],
        'emailAliases'          => [['Empty', []]],
    ];
    
    /**
     * sets the record related properties from user generated input.
     * 
     * Input-filtering and validation by Zend_Filter_Input can enabled and disabled
     *
     * @param array $_data            the new data to set
     */
    public function setFromArray(array &$_data)
    {
        foreach ([
                    'emailForwards' => Tinebase_Model_EmailUser_Forward::class,
                    'emailAliases' => Tinebase_Model_EmailUser_Alias::class
                 ] as $arrayField => $model) {
            if (isset($_data[$arrayField])) {
                $data = ! is_array($_data[$arrayField])
                    ? explode(',', preg_replace('/ /', '', $_data[$arrayField]))
                    : $_data[$arrayField];
                array_walk($data, function (&$value) {
                    if (! is_array($value) && $value) {
                        $value = [
                            'email' => $value
                        ];
                    }
                });
                $data = array_filter($data, function($value) {
                    return is_array($value) && isset($value['email']) && $value['email'];
                });
                $_data[$arrayField] = new Tinebase_Record_RecordSet($model, $data);
            }
        }

        if (isset($_data['emailAddress'])) {
            $_data['emailAddress'] = Tinebase_Helper::convertDomainToPunycode($_data['emailAddress']);
        }
        
        parent::setFromArray($_data);
    }

    /**
     * @return array
     */
    public function getAliasesAsEmails()
    {
        return $this->emailAliases->{Tinebase_Model_EmailUser_Alias::FLDS_EMAIL};
    }

    /**
     * @param bool $_recursive
     * @return array
     */
    public function toArray($_recursive = TRUE)
    {
        $result = parent::toArray($_recursive);

        if ($this->emailAddress) {
            $result['emailAddress'] = Tinebase_Helper::convertDomainToUnicode($this->emailAddress);
        }

        return $result;
    }
} 
