<?php
/**
 * @package     Tinebase
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * this class represents a key field record of a key field config
 * @see http://www.tine20.org/wiki/index.php/Developers/Concepts/KeyFields
 * 
 * @package     Tinebase
 * @subpackage  Config
 */
class Tinebase_Config_KeyFieldRecord extends Tinebase_Record_Abstract
{
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Record/Abstract::$_identifier
     */
    protected $_identifier = 'id';
    
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