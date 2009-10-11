<?php
/**
 * Crm csv generation class
 *
 * @package     Crm
 * @subpackage	Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        add relations / products / state / type / source
 * @todo        add test
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
     * export leads to csv file
     *
     * @param Crm_Model_LeadFilter $_filter
     * @return string filename
     */
    public function generate(Crm_Model_LeadFilter $_filter, $_toStdout = FALSE) {
        
        $pagination = new Tinebase_Model_Pagination(array(
            'start' => 0,
            'limit' => 0,
            'sort' => 'lead_name',
            'dir' => 'ASC',
        ));
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_filter->toArray(), true));
        
        $leads = Crm_Controller_Lead::getInstance()->search($_filter, $pagination, TRUE);
        if (count($leads) < 1) {
            throw new Tinebase_Exception_NotFound('No Leads found.');
        }

        $skipFields = array(
            'id'                    ,
            'created_by'            ,
            'creation_time'         ,
            'last_modified_by'      ,
            'last_modified_time'    ,
            'is_deleted'            ,
            'deleted_time'          ,
            'deleted_by'            ,
        );
        
        $filename = parent::exportRecords($leads, $_toStdout, $skipFields);
        return $filename;
    }
}
