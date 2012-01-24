<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        fix this for build script: 
 *              Notice: Use of undefined constant SQL_TABLE_PREFIX - assumed 'SQL_TABLE_PREFIX' 
 *                  in /var/www/tine20build/tine20/ActiveSync/Backend/Device.php on line 28
 */

/**
 * backend device class
 * @package     ActiveSync
 */
class ActiveSync_Backend_Device extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'acsync_device';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'ActiveSync_Model_Device';
}
