<?php
/**
 * class to hold asterisk voicemail data
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:  $
 *
 */

/**
 * class to hold asterisk voicemail data
 * 
 * @package     Voipmanager 
 */
class Voipmanager_Model_AsteriskVoicemail extends Tinebase_Record_Abstract
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
        'context'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'mailbox'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'password'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'fullname'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'email'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pager'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tz'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'attach'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'saycid'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'dialout'               => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
        'callback'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),  
        'review'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'operator'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'envelope'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'sayduration'           => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
        'saydurationm'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),  
        'sendvoicemail'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'delete'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'nextaftercmd'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),  
        'forcename'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'forcegreetings'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'hidefromdir'           => array(Zend_Filter_Input::ALLOW_EMPTY => true) 
    );

    /**
     * converts a int, string or Voipmanager_Model_AsteriskVoicemail to an voicemail id
     *
     * @param int|string|Voipmanager_Model_AsteriskVoicemail $_voicemailId the voicemail id to convert
     * @return int
     * @throws  Voipmanager_Exception_InvalidArgument
     */
    static public function convertAsteriskVoicemailIdToInt($_voicemailId)
    {
        if ($_voicemailId instanceof Voipmanager_Model_AsteriskVoicemail) {
            if (empty($_voicemailId->id)) {
                throw new Voipmanager_Exception_InvalidArgument('no voicemail id set');
            }
            $id = (string) $_voicemailId->id;
        } else {
            $id = (string) $_voicemailId;
        }
        
        if ($id == '') {
            throw new Voipmanager_Exception_InvalidArgument('voicemail id can not be 0');
        }
        
        return $id;
    }

}