<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @version     $Id$
 */

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