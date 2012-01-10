<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Task-Status Record Class
 * @package Tasks
 */
class Tasks_Model_Status extends Tinebase_Config_KeyFieldRecord
{
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tasks';
    
    /**
     * additional status specific validators
     * 
     * @var array
     */
    protected $_additionalValidators = array(
        'is_open'              => array('allowEmpty' => true,  'Int'  ),
    );
}
