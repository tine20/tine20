<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * class Tinebase_Model_CustomField_Value
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Model_CustomField_Value extends Tinebase_Record_Abstract 
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
        'record_id'         => array('presence' => 'required', 'allowEmpty' => false ),
        'customfield_id'    => array('presence' => 'required', 'allowEmpty' => false ),
        'value'             => array('allowEmpty' => true ),
    );
}
