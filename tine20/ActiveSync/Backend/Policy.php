<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * backend device class
 * @package     ActiveSync
 * @subpackage  Backend
 */
class ActiveSync_Backend_Policy extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'acsync_policy';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'ActiveSync_Model_Policy';
}
