<?php
/**
 * class to hold templates data
 * 
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Product.php 2531 2008-05-18 07:52:12Z nelius_weiss $
 *
 */

/**
 * class to hold templates data
 * 
 * @package     Voipmanager Management
 */
class Voipmanager_Model_Template extends Tinebase_Record_Abstract
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
        'name'                      => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'description'				=> array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'model'                     => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'keylayout_id'              => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'setting_id'                => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'software_id'               => array(Zend_Filter_Input::ALLOW_EMPTY => false)
    );

    /**
     * converts a int, string or Voipmanager_Model_Template to an template id
     *
     * @param int|string|Voipmanager_Model_Template $_templateId the template id to convert
     * @return int
     */
    static public function convertTemplateIdToInt($_templateId)
    {
        if ($_templateId instanceof Voipmanager_Model_Template) {
            if (empty($_templateId->id)) {
                throw new Exception('no template id set');
            }
            $id = (string) $_templateId->id;
        } else {
            $id = (string) $_templateId;
        }
        
        if ($id == '') {
            throw new Exception('template id can not be 0');
        }
        
        return $id;
    }

}