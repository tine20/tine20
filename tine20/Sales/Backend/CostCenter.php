<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */


/**
 * backend for CostCenters
 *
 * @package     Sales
 * @subpackage  Backend
 */
class Sales_Backend_CostCenter extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'sales_cost_centers';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Sales_Model_CostCenter';
    
    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = false;
    
}
