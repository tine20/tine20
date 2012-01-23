<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 */

class Tinebase_Record_DummyRecord extends Tinebase_Record_Abstract
{
    /**
    * application
    *
    * @var string
    */
    protected $_application = 'Crm';
    
    /**
     * filters
     * 
     * @var array
     */
    protected $_filters = array(
        'date_stringtrim' => 'StringTrim',
        'stringtrim'      => 'StringTrim'
        
    );

    /**
     * identifier
     * 
     * @var string
     */
    protected  $_identifier = 'id';
    
    /**
     * is validated
     * 
     * @var boolean
     */
    protected  $_isValidated = true;
    
    /**
     * validators
     * 
     * @var array
     */
    protected $_validators = array(
        'id'              => array(Zend_Filter_Input::ALLOW_EMPTY => true,  'Int'   ),
        'string'          => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'Alpha' ),
        'stringtrim'      => array('allowEmpty' => false, 'Alpha' ),
        'test_1'          => array('allowEmpty' => true,  'Int'   ),
        'test_2'          => array('allowEmpty' => true,  'Int'   ),
        'test_3'          => array('allowEmpty' => true,  'Int'   ),
        'test_4'          => array('allowEmpty' => true,  'Int'   ),
        'test_id'         => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'date_single'     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'date_stringtrim' => array(),
        'date_multiple'   => array(),
        'leadstate'       => array('allowEmpty' => false, 'Alpha' ),
        'inarray'         => array(array('InArray', array('value1', 'value2')), 'allowEmpty' => true),
    );

    /**
    * validators
    *
    * @var array
    */
    protected $_datetimeFields = array(
        'date_single',
        'date_multiple',
    );
    
    /**
     * fields for translations
     * 
     * @var array
     */ 
    protected $_toTranslate = array(
        'leadstate'
    );
}
