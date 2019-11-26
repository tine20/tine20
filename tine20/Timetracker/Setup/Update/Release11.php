<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

echo __FILE__ . ' must not be used or autoloaded or required etc.' . PHP_EOL;
exit(1);


class Timetracker_Setup_Update_Release11 extends Setup_Update_Abstract
{
    /**
     * update to 11.1
     *
     * set default type for TA - Contract relations
     */
    public function update_0()
    {
        // get all TA-Contract relations without type
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel('Tinebase_Model_Relation', [
            ['field' => 'own_model', 'operator' => 'equals', 'value' => 'Timetracker_Model_Timeaccount'],
            ['field' => 'related_model', 'operator' => 'equals', 'value' => 'Sales_Model_Contract'],
            ['field' => 'type', 'operator' => 'equals', 'value' => ''],
        ]);
        $relations = Tinebase_Relations::getInstance()->search($filter);
        $updated = 0;
        Timetracker_Controller_Timeaccount::getInstance()->doGrantChecks(false);
        foreach ($relations as $relation) {
            $ta = Timetracker_Controller_Timeaccount::getInstance()->get($relation->own_id);
            if ($ta->relations->filter('type', 'TIME_ACCOUNT')->count() === 0) {
                $relation->type = 'TIME_ACCOUNT';
                $updated++;
                Tinebase_Relations::getInstance()->getBackend()->update($relation);
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Updated ' . $updated . ' TA <-> Contract relations');

        $this->setApplicationVersion('Timetracker', '11.1');
    }

    /**
     * update to 12.0
     */
    public function update_1()
    {
        $this->setApplicationVersion('Timetracker', '12.0');
    }
}
