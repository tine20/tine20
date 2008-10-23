<?php
/**
 * lead state controller for CRM application
 * 
 * @package     Crm
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        add abstract crm controller and extend it here
 */

/**
 * lead state controller class for CRM application
 * 
 * @package     Crm
 * @subpackage  Controller
 */
class Crm_Controller_LeadStates extends Tinebase_Application_Controller_Abstract
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Crm';
    
    /**
     * holdes the instance of the singleton
     *
     * @var Crm_Controller_LeadStates
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Crm_Controller_LeadStates
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Crm_Controller_LeadStates;
        }
        
        return self::$_instance;
    }    
        
    /**
     * get one state identified by id
     *
     * @param int $_id
     * @return Crm_Model_Leadstate
     */
    public function getLeadState($_id)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEAD_STATES);
        $result = $backend->get($_id);
        $result->translate();

        return $result;    
    }

    /**
     * get lead states
     *
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Crm_Model_Leadstate
     */
    public function getLeadStates($_sort = 'id', $_dir = 'ASC')
    {
    	$backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEAD_STATES);
        $result = $backend->getAll($_sort, $_dir);

        return $result;    
    }

    /**
     * saves lead states
     *
     * Saving lead states means to calculate the difference between posted data
     * and existing data and than deleting, creating or updating as needed.
     * Every change is done one by one.
     * 
     * @param Tinebase_Record_Recordset $_leadStates Lead states to save
     * @return Tinebase_Record_Recordset Exactly the same record set as in argument $_leadStates
     */
    public function saveLeadstates(Tinebase_Record_Recordset $_leadStates)
    {
    	$backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEAD_STATES);
        $existingLeadStates = $backend->getAll();
        
        $migration = $existingLeadStates->getMigration($_leadStates->getArrayOfIds());
        
        // delete
        foreach ($migration['toDeleteIds'] as $id) {
            $backend->delete($id);
        }
        
        // add / create
        foreach ($_leadStates as $leadState) {
            if (in_array($leadState->id, $migration['toCreateIds'])) {
                $backend->create($leadState);
            }
        }
        
        // update
        foreach ($_leadStates as $leadState) {
            if (in_array($leadState->id, $migration['toUpdateIds'])) {
                $backend->update($leadState);
            }
        }
        
        return $_leadStates;
    }   
}
