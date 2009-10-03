<?php
/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * class to hold asterisk redirect data
 * 
 * @package     Voipmanager 
 */
class Voipmanager_Model_Asterisk_Redirect extends Tinebase_Record_Abstract
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
    protected $_filters = array(
        '*'             => 'StringTrim'
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'sippeer_id'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'cfi_mode'      => array(Zend_Filter_Input::ALLOW_EMPTY => true, 'InArray' => array(self::CFMODE_OFF, self::CFMODE_NUMBER, self::CFMODE_VOICEMAIL), 'default' => self::CFMODE_OFF),
        'cfi_number'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'cfb_mode'      => array(Zend_Filter_Input::ALLOW_EMPTY => true, 'InArray' => array(self::CFMODE_OFF, self::CFMODE_NUMBER, self::CFMODE_VOICEMAIL), 'default' => self::CFMODE_OFF),
        'cfb_number'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'cfd_mode'      => array(Zend_Filter_Input::ALLOW_EMPTY => true, 'InArray' => array(self::CFMODE_OFF, self::CFMODE_NUMBER, self::CFMODE_VOICEMAIL), 'default' => self::CFMODE_OFF),
        'cfd_number'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'cfd_time'      => array(Zend_Filter_Input::ALLOW_EMPTY => true, 'Int', 'default' => 30)
    );

    /**
     * converts a string or Voipmanager_Model_Asterisk_Redirect to an redirect id
     *
     * @param int|string|Voipmanager_Model_Asterisk_Redirect $_id the redirect id to convert
     * @return int
     * @throws  Voipmanager_Exception_InvalidArgument
     */
    static public function convertObjectToId($_id)
    {
        if ($_id instanceof Voipmanager_Model_Asterisk_Redirect) {
            if (empty($_id->id)) {
                throw new Voipmanager_Exception_InvalidArgument('no redirect id set');
            }
            $id = (string) $_id->id;
        } else {
            $id = (string) $_id;
        }
                
        return $id;
    }

}
