<?php
/**
 * class to hold number data
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        add more types
 */

/**
 * class to hold number data
 * 
 * @package     Sales
 */
class Sales_Model_Number extends Tinebase_Record_Abstract
{  
    /**
     * constant for contract type
     *
     */
    const TYPE_CONTRACT = 'contract';    
    
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
    protected $_application = 'Sales';
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'               => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'number'           => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'account_id'       => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'type'             => array(
            Zend_Filter_Input::ALLOW_EMPTY => false, 
            'presence'=>'required',
            'InArray' => array(self::TYPE_CONTRACT)
        ),
        'update_time'      => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
    );

    /**
     * name of fields containing datetime or an array of datetime information
     *
     * @var array list of datetime fields
     */    
    protected $_datetimeFields = array(
        'update_time',
    );
}
