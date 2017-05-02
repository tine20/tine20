<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * class Tinebase_Model_PersistentObserver
 *
 * @package     Tinebase
 * @subpackage  Record
 *
 * @property string     observable_model
 * @property string     observable_identifier
 * @property string     observer_model
 * @property string     observer_identifier
 * @property string     observed_event
 */
class Tinebase_Model_PersistentObserver extends Tinebase_Record_Abstract 
{

    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';
    
    protected $_validators = array(
        'id'                     => array('allowEmpty' => true, 'Int'  ),
        'created_by'             => array('allowEmpty' => true,        ),
        'creation_time'          => array('allowEmpty' => true         ),
        'observable_model'       => array('presence' => 'required', 'allowEmpty' => false),
        'observable_identifier'  => array('presence' => 'required', 'allowEmpty' => false, 'Alnum'),
        'observer_model'         => array('presence' => 'required', 'allowEmpty' => false),
        'observer_identifier'    => array('presence' => 'required', 'allowEmpty' => false, 'Alnum'),
        'observed_event'         => array('presence' => 'required', 'allowEmpty' => false)
    );
}
