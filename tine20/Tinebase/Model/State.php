<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 */

/**
 * class Tinebase_Model_State
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Model_State extends Tinebase_Record_Abstract 
{
    /**
     * identifier field name
     *
     * @var string
     */
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
        'id'                => array('allowEmpty' => TRUE),
        'user_id'           => array('presence' => 'required'),
        'data'              => array('allowEmpty' => TRUE),
        'state_id'          => array('allowEmpty' => FALSE)
    );
}
