<?php
/**
 * Crm csv generation class
 *
 * @package     Crm
 * @subpackage	Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        add products
 */

/**
 * Crm csv generation class
 * 
 * @package     Crm
 * @subpackage	Export
 * 
 */
class Crm_Export_Csv extends Tinebase_Export_Csv
{
    /**
     * @var string application name of this export class
     */
    protected $_applicationName = 'Crm';
    
    /**
     * the record model
     *
     * @var string
     */
    protected $_modelName = 'Crm_Model_Lead';
    
    /**
     * lead relation types
     * 
     * @var array
     */
    protected $_relationsTypes = array('CUSTOMER', 'PARTNER', 'RESPONSIBLE', 'TASK');

    /**
     * special fields
     * 
     * @var array
     */
    protected $_specialFields = array('leadstate_id' => 'Leadstate', 'leadtype_id' => 'Leadtype', 'leadsource_id' => 'Leadsource');
    
    /**
     * get record relations
     * 
     * @var boolean
     */
    protected $_getRelations = TRUE;    
    
    /**
     * fields to skip
     * 
     * @var array
     */
    protected $_skipFields = array(
        'id'                    ,
        'created_by'            ,
        'creation_time'         ,
        'last_modified_by'      ,
        'last_modified_time'    ,
        'is_deleted'            ,
        'deleted_time'          ,
        'deleted_by'            ,
        'relations'             ,
    );
        
    /**
     * special field value function
     * 
     * @param Tinebase_Record_Abstract $_record
     * @param string $_fieldName
     * @return string
     */
    protected function _addSpecialValue(Tinebase_Record_Abstract $_record, $_fieldName)
    {
        return Tinebase_Config::getOptionString($_record, preg_replace('/_id/', '', $_fieldName));
    }
}
