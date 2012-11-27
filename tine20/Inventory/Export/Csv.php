<?php
/**
 * Inventory csv generation class
 *
 * @package     Inventory
 * @subpackage    Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * Inventory csv generation class
 * 
 * @package     Inventory
 * @subpackage    Export
 * 
 */
class Inventory_Export_Csv extends Tinebase_Export_Csv
{
    /**
     * @var string application name of this export class
     */
    protected $_applicationName = 'Inventory';
    
    /**
     * the record model
     *
     * @var string
     */
    protected $_modelName = 'Inventory_Model_InventoryItem';
    
    /**
     * fields to skip
     * 
     * @var array
     */
    protected $_skipFields = array(
        'id'                    ,
        'container_id'          ,
        'created_by'            ,
        'creation_time'         ,
        'last_modified_by'      ,
        'last_modified_time'    ,
        'is_deleted'            ,
        'deleted_time'          ,
        'deleted_by'            ,
        'jpegphoto'
    );
}
