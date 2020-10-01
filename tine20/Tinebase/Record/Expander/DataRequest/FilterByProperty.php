<?php
/**
 * holds information about the requested data
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class Tinebase_Record_Expander_DataRequest_FilterByProperty extends Tinebase_Record_Expander_DataRequest
{
    protected $property;

    public function __construct($prio, $controller, $property, $ids, $callback, $getDeleted = false)
    {
        $this->property = $property;
        parent::__construct($prio, $controller, $ids, $callback, $getDeleted);
    }

    public function getData()
    {
        if ($this->_merged) {
            $this->ids = array_unique($this->ids);
            $this->_merged = false;
        }

        // get instances from datacache
        $model = $this->controller->getModel();
        $cacheKey = 'filterbyproperty' . $model;
        $data = static::_getInstancesFromCache($model, $cacheKey, $this->ids, $this->_getDeleted);

        if (!empty($this->ids)) {
            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel($model, [
                ['field' => $this->property, 'operator' => 'in', 'value' => $this->ids]
            ]);
            if ($this->_getDeleted) {
                $filter->addFilter(new Tinebase_Model_Filter_Bool('is_deleted', 'equals',
                    Tinebase_Model_Filter_Bool::VALUE_NOTSET));
            }
            $newRecords = $this->controller->search($filter);
            static::_addInstancesToCache($cacheKey, $newRecords, $this->_getDeleted);
            $data->mergeById($newRecords);
        }

        return $data;
    }
}
