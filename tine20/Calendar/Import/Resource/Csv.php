<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Christian Feitl<c.feitl@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * csv import class for the Timetracker
 *
 * @package     Timetracker
 * @subpackage  Import
 *
 */
class Calendar_Import_Resource_Csv extends Tinebase_Import_Csv_Abstract
{
    /**
     * do conversions
     *
     * @param array $_data
     * @return array
     */
    protected function _doConversions($_data)
    {
        $config =Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP)->toArray();
        $result = parent::_doConversions($_data);
        $result['max_number_of_people'] = intval($result['max_number_of_people']);
        $result['suppress_notification']= intval($result['suppress_notification']);

        if($result['email'] == "")
        {
            $result['email'] = $result['name'] . '@' . $config['primarydomain'];
        }

        $result['grants'] = [[
            Calendar_Model_ResourceGrants::RESOURCE_INVITE => true,
            Calendar_Model_ResourceGrants::RESOURCE_READ => true,
            Calendar_Model_ResourceGrants::RESOURCE_EDIT => true,
            Calendar_Model_ResourceGrants::RESOURCE_EXPORT => true,
            Calendar_Model_ResourceGrants::RESOURCE_SYNC => true,
            Calendar_Model_ResourceGrants::RESOURCE_ADMIN => true,
            Calendar_Model_ResourceGrants::EVENTS_ADD => true,
            Calendar_Model_ResourceGrants::EVENTS_READ => true,
            Calendar_Model_ResourceGrants::EVENTS_EXPORT => true,
            Calendar_Model_ResourceGrants::EVENTS_SYNC => true,
            Calendar_Model_ResourceGrants::EVENTS_FREEBUSY => true,
            Calendar_Model_ResourceGrants::EVENTS_EDIT => true,
            Calendar_Model_ResourceGrants::EVENTS_DELETE => true,
            'account_id' => '0',
            'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE]];

        $result = $this->_setRelation($result);

        return $result;
    }

    /**
     * set relation for site in resource
     * @param $result
     * @return mixed
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _setRelation($result)
    {
        if (!empty($result['site'])) {
            $contracts = Addressbook_Controller_Contact::getInstance()->getAll();
            foreach ($contracts as $contract) {
                if ($contract['n_family'] == $result['site']) {
                    $result['relations'] = [
                        [
                            'own_model' => 'Calendar_Model_Resource',
                            'own_backend' => Tasks_Backend_Factory::SQL,
                            'own_id' => null,
                            'related_degree' => Tinebase_Model_Relation::DEGREE_CHILD,
                            'related_model' => 'Addressbook_Model_Contact',
                            'related_backend' => Tasks_Backend_Factory::SQL,
                            'related_id' => $contract['id'],
                            'type' => 'STANDORT'
                        ]];
                }
            }
        }
        return $result;
    }

}