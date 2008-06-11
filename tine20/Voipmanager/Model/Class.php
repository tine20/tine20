<?php
/**
 * class to hold classes data
 * 
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Product.php 2531 2008-05-18 07:52:12Z nelius_weiss $
 *
 */

/**
 * class to hold classes data
 * 
 * @package     Voipmanager Management
 */
class Voipmanager_Model_Class extends Tinebase_Record_Abstract
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
        'id'						=> array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'description'				=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'model'                     => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'config_id'                 => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'setting_id'                => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'software_id'               => array(Zend_Filter_Input::ALLOW_EMPTY => false)
    );

    /**
     * converts a int, string or Voipmanager_Model_Class to an class id
     *
     * @param int|string|Voipmanager_Model_Class $_classId the class id to convert
     * @return int
     */
    static public function convertClassIdToInt($_classId)
    {
        if ($_classId instanceof Voipmanager_Model_Class) {
            if (empty($_classId->id)) {
                throw new Exception('no class id set');
            }
            $id = (string) $_classId->id;
        } else {
            $id = (string) $_classId;
        }
        
        if ($id == '') {
            throw new Exception('class id can not be 0');
        }
        
        return $id;
    }

}