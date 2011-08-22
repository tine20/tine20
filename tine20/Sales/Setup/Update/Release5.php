<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class Sales_Setup_Update_Release5 extends Setup_Update_Abstract
{
    /**
     * update from 5.0 -> 5.1
     * - save shared contracts container id in config
     * 
     * @return void
     */
    public function update_0()
    {
        try {
            $sharedContractsId = Tinebase_Config::getInstance()->getConfig(Sales_Model_Config::SHAREDCONTRACTSID, $appId)->value;
            $sharedContracts = Tinebase_Container::getInstance()->get($sharedContractsId ? $sharedContractsId : 1);
        } catch (Tinebase_Exception_NotFound $tenf) {
            // try to fetch default shared container
            $filter = new Tinebase_Model_ContainerFilter(array(
                array('field' => 'application_id', 'operator' => 'equals',
                    'value' => Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId()),
                array('field' => 'name', 'operator' => 'equals', 'value' => 'Shared Contracts'),
                array('field' => 'type', 'operator' => 'equals', 'value' => Tinebase_Model_Container::TYPE_SHARED),
            ));
            
            $sharedContracts = Tinebase_Container::getInstance()->search($filter)->getFirstRecord();
            if ($sharedContracts) {
                Tinebase_Config::getInstance()->setConfigForApplication(Sales_Model_Config::SHAREDCONTRACTSID, $sharedContracts->getId(), 'Sales');
            }
        }
        
        $this->setApplicationVersion('Sales', '5.1');
    }
}
