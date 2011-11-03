<?php
/**
 * Tine 2.0
 *
 * @package     Projects
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class Projects_Setup_Update_Release1 extends Setup_Update_Abstract
{
    /**
     * update favorites to new filter syntax and update to 5.0 
     * 
     * @return void
     */
    public function update_0()
    {
        $filters = Tinebase_PersistentFilter::getInstance()->getAll();
        $projectFilters = $filters->filter('application_id', Tinebase_Application::getInstance()->getApplicationByName('Projects')->getId());
        $pfBackend = new Tinebase_PersistentFilter_Backend_Sql();
        
        foreach ($projectFilters as $pfilter) {
            foreach ($pfilter->filters as $filter) {
                if ($filter->getField() === 'contact' && $filter instanceof Tinebase_Model_Filter_Relation) {
                    $values = array();
                    foreach ($filter->getValue() as $idx => $subfilter) {
                        $values[$idx] = $subfilter;
                        if (in_array($subfilter['field'], array('relation_type', 'id'))) {
                            $values[$idx]['field'] = ':' . $subfilter['field'];
                        }
                    }
                    $filter->setValue($values);
                    $pfBackend->update($pfilter);
                }
            }
        }
        
        $this->setApplicationVersion('Projects', '5.0');
    }
}
