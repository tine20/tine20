<?php
/**
 * class to hold snom template data
 * 
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @todo        Check setting_id and software_id in javascript edit dialogue, no empty strings allowed
 */

/**
 * class to hold snom template data
 * 
 * @package     Voipmanager Management
 */
class Voipmanager_Model_Snom_Template extends Tinebase_Record_Abstract
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
        'id'                        => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'name'                      => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'description'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'model'                     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'keylayout_id'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'setting_id'                => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'software_id'               => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required')
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
        // set turnover to 0 if not set
        $this->_filters['model'] = new Zend_Filter_Empty('snom300');
        
        parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
    
    /**
     * converts a int, string or Voipmanager_Model_Template to an template id
     *
     * @param int|string|Voipmanager_Model_Template $_templateId the template id to convert
     * @return int
     * @throws  Voipmanager_Exception_InvalidArgument
     */
    static public function convertSnomTemplateIdToInt($_templateId)
    {
        if ($_templateId instanceof Voipmanager_Model_Snom_Template) {
            if (empty($_templateId->id)) {
                throw new Voipmanager_Exception_InvalidArgument('no template id set');
            }
            $id = (string) $_templateId->id;
        } else {
            $id = (string) $_templateId;
        }
        
        if ($id == '') {
            throw new Voipmanager_Exception_InvalidArgument('template id can not be 0');
        }
        
        return $id;
    }

}
