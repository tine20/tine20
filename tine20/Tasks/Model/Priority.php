<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Task priority Record Class
 * 
 * @package    Tasks
 * @subpackage Model
 */
class Tasks_Model_Priority extends Tinebase_Config_KeyFieldRecord
{
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tasks';
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Record/Abstract::$_validators
     */
    protected $_validators = array(
        // tine record fields
        'id'                   => array('allowEmpty' => true,         ),
        'created_by'           => array('allowEmpty' => true,         ),
        'creation_time'        => array('allowEmpty' => true          ),
        'last_modified_by'     => array('allowEmpty' => true          ),
        'last_modified_time'   => array('allowEmpty' => true          ),
        'is_deleted'           => array('allowEmpty' => true          ),
        'deleted_time'         => array('allowEmpty' => true          ),
        'deleted_by'           => array('allowEmpty' => true          ),
        'seq'                  => array('allowEmpty' => true,  'Int'  ),
    
        // key field record specific
        'value'                => array('allowEmpty' => false         ),
        'icon'                 => array('allowEmpty' => true          ),
        'system'               => array('allowEmpty' => true,  'Int'  ),
    );
}
