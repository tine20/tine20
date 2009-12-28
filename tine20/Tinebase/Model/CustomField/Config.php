<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

/**
 * class Tinebase_Model_CustomField_Config
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Model_CustomField_Config extends Tinebase_Record_Abstract 
{    
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';
    
    /**
     * record validators
     *
     * @var array
     */
    protected $_validators = array(
        'id'                => array('allowEmpty' => true ),
        'application_id'    => array('presence' => 'required', 'allowEmpty' => false, 'Alnum' ),
        'name'              => array('presence' => 'required', 'allowEmpty' => false ),
        'label'             => array('presence' => 'required', 'allowEmpty' => false ),        
        'model'             => array('presence' => 'required', 'allowEmpty' => false ),
        'type'              => array('allowEmpty' => true ),
        'length'            => array('allowEmpty' => true, 'Alnum'  ),
        'group'             => array('allowEmpty' => true, Zend_Filter_Input::DEFAULT_VALUE => '' ),
        'order'             => array('allowEmpty' => true, Zend_Filter_Input::DEFAULT_VALUE => 0, 'Int' ),
    // search for values with typeahead combobox in cf panel
        'value_search'      => array('allowEmpty' => true, Zend_Filter_Input::DEFAULT_VALUE => 0 ),
    );
    
} // end of Tinebase_Model_CustomField_Config
