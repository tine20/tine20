<?php
/**
 * Tine 2.0
 * 
 * @package     tests
 * @subpackage  tinebase_record
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @version     $$
 */
 
 
require_once 'Zend/Loader.php';

Zend_Loader::registerAutoload();


class Tinebase_Record_DummyRecord extends Tinebase_Record_Abstract
{   

	protected $_filters = array(
        '*'      => 'StringTrim'
    );
	
	
	protected  $_identifier = 'test_id';
	
	protected  $_isValidated = true;
	
	
	protected $_validators = array(
		'id'            => array('allowEmpty' => true,  'Int'   ),
        'string'        => array('allowEmpty' => false, 'Alpha' ),
        'test_1'        => array('allowEmpty' => true,  'Int'   ),
        'test_2'        => array('allowEmpty' => true,  'Int'   ),
        'test_3'        => array('allowEmpty' => true,  'Int'   ),
        'test_4'        => array('allowEmpty' => true,  'Int'   ),
		'test_id'       => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
		'date_single'   => array(),
		'date_multiple' => array(),
	);

	protected $_datetimeFields = array(
		'date_single',
		'date_multiple',
	);
	

	
}