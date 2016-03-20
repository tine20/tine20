<?php
/**
 * Tine 2.0
 *
 * @package     Crm
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
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

        foreach(array('leadstate', 'leadtype', 'leadsource') as $oldValueName) {
            $keyFieldName = $oldValueName + 's';
            $DBconfig = $configRecords->filter('name', $keyFieldName)->getFirstRecord();
            if ($DBconfig) {
                $decodedConfig = json_decode($DBconfig->value, true);
                foreach($decodedConfig as $oldRecord) {
                    $oldRecord['value'] = $oldRecord[$oldValueName];
                    unset($oldRecord[$oldValueName]);
                }
                $default = isset($appDefaults[$keyFieldName]) ? $appDefaults[$keyFieldName] : 1;
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

        $this->setApplicationVersion('Crm', '9.1');
    }
}
