<?php
/**
 * class to hold myPhone data
 * 
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     
 *
 */

/**
 * class to hold myPhone data
 * 
 * @package     Voipmanager Management
 */
class Voipmanager_Model_MyPhone extends Tinebase_Record_Abstract
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
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id' 			        => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'redirect_event'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'redirect_number'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'redirect_time'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'template_id'           => array(Zend_Filter_Input::ALLOW_EMPTY => true) 
    );
    

    
    /**
     * converts a int, string or Voipmanager_Model_MyPhone to an phone id
     *
     * @param int|string|Voipmanager_Model_MyPhone $_phoneId the phone id to convert
     * @return int
     */
    static public function convertMyPhoneIdToInt($_phoneId)
    {
        if ($_phoneId instanceof Voipmanager_Model_MyPhone) {
            if (empty($_phoneId->id)) {
                throw new Exception('no phone id set');
            }
            $id = (string) $_phoneId->id;
        } else {
            $id = (string) $_phoneId;
        }
        
        if ($id == '') {
            throw new Exception('phone id can not be 0');
        }

        return $id;
    }

}