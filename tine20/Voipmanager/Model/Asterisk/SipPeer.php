<?php
/**
 * class to hold asterisk sip peer data
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * class to hold asterisk sip peer data
 * 
 * @package     Voipmanager 
 */
class Voipmanager_Model_Asterisk_SipPeer extends Tinebase_Record_Abstract
{
    /**
     * set call forward off
     * @var string
     */
    const CFMODE_OFF        = 'off';
    
    /**
     * forward call to number
     * @var string
     */
    const CFMODE_NUMBER     = 'number';
    
    /**
     * forward call to voicemail
     * @var string
     */
    const CFMODE_VOICEMAIL  = 'voicemail';
    
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Voipmanager';
    
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    #protected $_filters = array(
    #    '*'                     => 'StringTrim'
    #);
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'name'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'accountcode'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'amaflags'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'callgroup'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'callerid'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'canreinvite'           => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'context_id'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'context'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'defaultip'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'dtmfmode'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'fromuser'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'fromdomain'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'fullcontact'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'host'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 'dynamic'),
        'insecure'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'language'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'mailbox'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'md5secret'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'nat'                   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deny'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'permit'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'mask'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pickupgroup'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'port'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'qualify'               => array(array('InArray', array('yes', 'no')), Zend_Filter_Input::DEFAULT_VALUE => 'no'),
        'restrictcid'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'rtptimeout'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'rtpholdtimeout'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'secret'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'type'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'defaultuser'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'disallow'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'allow'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'musiconhold'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'regseconds'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'ipaddr'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'regexten'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'cancallforward'        => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'setvar'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'notifyringing'         => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'useclientcode'         => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'authuser'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'call-limit'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'busy-level'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'regserver'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'useragent'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'lastms'                => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => -1),
        'cfi_mode'              => array(Zend_Filter_Input::ALLOW_EMPTY => true, 'InArray' => array(self::CFMODE_OFF, self::CFMODE_NUMBER, self::CFMODE_VOICEMAIL), 'default' => self::CFMODE_OFF),
        'cfi_number'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'cfb_mode'              => array(Zend_Filter_Input::ALLOW_EMPTY => true, 'InArray' => array(self::CFMODE_OFF, self::CFMODE_NUMBER, self::CFMODE_VOICEMAIL), 'default' => self::CFMODE_OFF),
        'cfb_number'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'cfd_mode'              => array(Zend_Filter_Input::ALLOW_EMPTY => true, 'InArray' => array(self::CFMODE_OFF, self::CFMODE_NUMBER, self::CFMODE_VOICEMAIL), 'default' => self::CFMODE_OFF),
        'cfd_number'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'cfd_time'              => array(Zend_Filter_Input::ALLOW_EMPTY => true, 'Int', 'default' => 30)
    );
    
    /**
     * name of fields containing datetime or or an array of datetime information
     *
     * @var array list of datetime fields
     */
    protected $_datetimeFields = array(
        'regseconds'
    );
    
    /**
     * overwrite constructor to add more filters
     *
     * @param mixed $_data
     * @param bool $_bypassFilters
     * @param mixed $_convertDates
     * @return void
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        // set default value if field is empty
        $this->_filters['nat'] = new Zend_Filter_Empty('no');
        
        parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
    
    /**
     * converts a int, string or Voipmanager_Model_Asterisk_SipPeer to an line id
     *
     * @param int|string|Voipmanager_Model_Asterisk_SipPeer $_sipPeerId the sip peer id to convert
     * @return int
     * @throws  Voipmanager_Exception_InvalidArgument
     */
    static public function convertAsteriskSipPeerIdToInt($_sipPeerId)
    {
        if ($_sipPeerId instanceof Voipmanager_Model_Asterisk_SipPeer) {
            if (empty($_sipPeerId->id)) {
                throw new Voipmanager_Exception_InvalidArgument('no sip peer id set');
            }
            $id = (string) $_sipPeerId->id;
        } else {
            $id = (string) $_sipPeerId;
        }
        
        if ($id == '') {
            throw new Voipmanager_Exception_InvalidArgument('sip peer id can not be 0');
        }
        
        return $id;
    }

    /**
     * (non-PHPdoc)
     * @see Tinebase/Record/Tinebase_Record_Abstract#_setFromJson($_data)
     */
    protected function _setFromJson(array &$_data)
    {
        // readonly fields, only setable by asterisk
        unset($_data['ipaddr']);
        unset($_data['lastms']);
        unset($_data['regseconds']);
        unset($_data['regserver']);
        unset($_data['useragent']);
    }
}
