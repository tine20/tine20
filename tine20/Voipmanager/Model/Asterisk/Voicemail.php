<?php
/**
 * class to hold asterisk voicemail data
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * class to hold asterisk voicemail data
 * 
 * @package     Voipmanager 
 */
class Voipmanager_Model_Asterisk_Voicemail extends Tinebase_Record_Abstract
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
        'context_id'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'context'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'mailbox'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'password'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'fullname'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'email'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pager'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tz'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'attach'                => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'saycid'                => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'dialout'               => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
        'callback'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),  
        'review'                => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'operator'              => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'envelope'              => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'sayduration'           => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0), 
        'saydurationm'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),  
        'sendvoicemail'         => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'delete'                => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'nextaftercmd'          => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),  
        'forcename'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'forcegreetings'        => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'hidefromdir'           => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0) 
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
        $this->_filters['attach'] = new Zend_Filter_Empty(0);
        $this->_filters['saycid'] = new Zend_Filter_Empty(0);
        $this->_filters['review'] = new Zend_Filter_Empty(0);
        $this->_filters['operator'] = new Zend_Filter_Empty(0);
        $this->_filters['envelope'] = new Zend_Filter_Empty(0);
        $this->_filters['sayduration'] = new Zend_Filter_Empty(0);
        $this->_filters['saydurationm'] = new Zend_Filter_Empty(0);
        $this->_filters['sendvoicemail'] = new Zend_Filter_Empty(0);
        $this->_filters['delete'] = new Zend_Filter_Empty(0);
        $this->_filters['nextaftercmd'] = new Zend_Filter_Empty(0);
        $this->_filters['forcename'] = new Zend_Filter_Empty(0);
        $this->_filters['forcegreetings'] = new Zend_Filter_Empty(0);
        $this->_filters['hidefromdir'] = new Zend_Filter_Empty(0);
        
        parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
    
    /**
     * converts a int, string or Voipmanager_Model_Asterisk_Voicemail to an voicemail id
     *
     * @param int|string|Voipmanager_Model_Asterisk_Voicemail $_voicemailId the voicemail id to convert
     * @return int
     * @throws  Voipmanager_Exception_InvalidArgument
     */
    static public function convertAsteriskVoicemailIdToInt($_voicemailId)
    {
        if ($_voicemailId instanceof Voipmanager_Model_Asterisk_Voicemail) {
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