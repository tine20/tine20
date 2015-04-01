<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2015-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */


/**
 * backend for purchase invoices
 *
 * @package     Sales
 * @subpackage  Backend
 */
class Sales_Backend_PurchaseInvoice extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'sales_purchase_invoices';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Sales_Model_PurchaseInvoice';

    /**
     * default column(s) for count
     *
     * @var string
     */
    protected $_defaultCountCol = 'id';
    
    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = TRUE;
    
}
