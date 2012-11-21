<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * class Tinebase_Model_PersistentObserver
 * 
 * @package     Tinebase
 * @subpackage  Record
 * 
 * NOTE: this is not used atm but might come in handy at some point
 */
class Tinebase_Model_PersistentObserver extends Tinebase_Record_Abstract 
{

    protected $_identifier = 'identifier';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';
    
    protected $_validators = array(
        'identifier'             => array('allowEmpty' => true, 'Int'  ),
        'created_by'             => array('allowEmpty' => true,        ),
        'creation_time'          => array('allowEmpty' => true         ),
        'last_modified_by'       => array('allowEmpty' => true         ),
        'last_modified_time'     => array('allowEmpty' => true         ),
        'is_deleted'             => array('allowEmpty' => true         ),
        'deleted_time'           => array('allowEmpty' => true         ),
        'deleted_by'             => array('allowEmpty' => true         ),
        'seq'                    => array('allowEmpty' => true         ),
        'observable_application' => array('presence' => 'required', 'allowEmpty' => false, 'Alnum'),
        'observable_identifier'  => array('presence' => 'required', 'allowEmpty' => false, 'Int'),
        'observer_application'   => array('presence' => 'required', 'allowEmpty' => false, 'Alnum'),
        'observer_identifier'    => array('presence' => 'required', 'allowEmpty' => false, 'Int'),
        'observed_event'         => array('presence' => 'required', 'allowEmpty' => false, )
    );
}
