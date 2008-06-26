<?php
/**
 * class to hold asterisk line data
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * class to hold asterisk line data
 * 
 * @package     Voipmanager 
 */
class Voipmanager_Model_AsteriskPeer extends Tinebase_Record_Abstract
{
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
    protected $_filters = array(
        '*'                     => 'StringTrim'
    );
    
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
        'canreinvite'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'context'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'defaultip'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'dtmfmode'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'fromuser'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'fromdomain'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'fullcontact'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'host'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
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
        'qualify'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'restrictcid'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'rtptimeout'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'rtpholdtimeout'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'secret'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'type'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'username'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'disallow'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'allow'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'musiconhold'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'regseconds'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'ipaddr'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'regexten'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'cancallforward'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'setvar'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'notifyringing'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'useclientcode'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'authuser'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'call-limit'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'busy-level'            => array(Zend_Filter_Input::ALLOW_EMPTY => true)
    );

    /**
     * converts a int, string or Voipmanager_Model_AsteriskPeer to an line id
     *
     * @param int|string|Voipmanager_Model_AsteriskPeer $_lineId the line id to convert
     * @return int
     */
    static public function convertAsteriskPeerIdToInt($_lineId)
    {
        if ($_lineId instanceof Voipmanager_Model_AsteriskPeer) {
            if (empty($_lineId->id)) {
                throw new Exception('no line id set');
            }
            $id = (string) $_lineId->id;
        } else {
            $id = (string) $_lineId;
        }
        
        if ($id == '') {
            throw new Exception('line id can not be 0');
        }
        
        return $id;
    }

}