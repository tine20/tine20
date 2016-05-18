<?php
/**
 * Tine 2.0
 *
 * @package     Crm
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2015-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Crm_Setup_Update_Release9 extends Setup_Update_Abstract
{
    /**
     * update to 9.1
     * - 0011676: apply new config concept to CRM
     */
    public function update_0()
    {
        $this->_updateLeadConfig();

        $this->setApplicationVersion('Crm', '9.1');
    }

    protected function _updateLeadConfig()
    {
        // get all configs for crm from DB
        $crmApp = Tinebase_Application::getInstance()->getApplicationByName('Crm');

        // either put default to DB or delete form DB
        $cb = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_Config',
            'tableName' => 'config',
        ));
        $configRecords = $cb->search(new Tinebase_Model_ConfigFilter(array(array(
            'field' => 'application_id', 'operator'=> 'equals', 'value' => $crmApp->getId()
        ))));

        $appDefaults = $configRecords->filter('name', 'appdefaults')->getFirstRecord();

        foreach (array('leadstate', 'leadtype', 'leadsource') as $oldValueName) {
            $keyFieldName = $oldValueName . 's';
            $DBconfig = $configRecords->filter('name', $keyFieldName)->getFirstRecord();
            // only update if custom config is found and if it is still in old format
            if ($DBconfig && strpos($DBconfig->value, $oldValueName) !== false) {
                $decodedConfig = json_decode($DBconfig->value, true);
                foreach ($decodedConfig as $key => $oldRecord) {
                    $decodedConfig[$key]['value'] = $oldRecord[$oldValueName];
                    unset($decodedConfig[$key][$oldValueName]);
                }

                // if no app defaults: use the first record as default
                $default = isset($appDefaults[$keyFieldName]) ? $appDefaults[$keyFieldName] : $decodedConfig[0]['id'];

                $DBconfig->value = json_encode(array(
                    'records' => $decodedConfig,
                    'default' => $default,
                ));
                $cb->update($DBconfig);
            }
        }

        if ($appDefaults) {
            $cb->delete($appDefaults->getId());
        }
    }

    /**
     * update to 9.2: repair broken update 9.1
     *
     * - 0011706: After Update from Elena to Egon Some elements are broken
     */
    public function update_1()
    {
        $this->_updateLeadConfig();

        $this->setApplicationVersion('Crm', '9.2');
    }
}
