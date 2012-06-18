<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */


/**
 * backend for HumanResources
 *
 * @package     HumanResources
 * @subpackage  Backend
 */
class HumanResources_Backend_FreeTime extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'humanresources_freetime';

    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'HumanResources_Model_FreeTime';

    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = true;
}
