<?php
/**
 * class to hold asterisk context data
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:  $
 *
 */

/**
 * class to hold asterisk context data
 * 
 * @package     Voipmanager 
 */
class Voipmanager_Model_Asterisk_Context extends Tinebase_Record_Abstract
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
        'name'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'description'   => array(Zend_Filter_Input::ALLOW_EMPTY => true)
    );

    /**
     * converts a int, string or Voipmanager_Model_Asterisk_Context to an context id
     *
     * @param int|string|Voipmanager_Model_Asterisk_Context $_contextId the context id to convert
     * @return int
     * @throws Voipmanager_Exception_InvalidArgument
     */
    static public function convertAsteriskContextIdToInt($_contextId)
    {
        if ($_contextId instanceof Voipmanager_Model_Asterisk_Context) {
            if (empty($_contextId->id)) {
                throw new Voipmanager_Exception_InvalidArgument('no context id set');
            }
            $id = (string) $_contextId->id;
        } else {
            $id = (string) $_contextId;
        }
        
        if ($id == '') {
            throw new Voipmanager_Exception_InvalidArgument('context id can not be 0');
        }
        
        return $id;
    }
}