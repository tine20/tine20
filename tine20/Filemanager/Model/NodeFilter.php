<?php
/**
 * Tine 2.0
 *
 * @package     Filemanager
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */


class Filemanager_Model_NodeFilter extends Tinebase_Model_Tree_Node_Filter
{
    /**
     * sets this filter group from filter data in array representation
     *
     * @param array $_data
     */
    public function setFromArray($_data)
    {
        foreach ($_data as $key => &$filterData) {
            if (isset($filterData['field']) && $filterData['field'] === 'foreignRecord' &&
                $filterData['value']['linkType'] === 'relation') {
                if (!isset($filterData['options']) || !is_array($filterData['options'])) {
                    $filterData['options'] = array();
                }
                $filterData['options']['own_model'] = 'Filemanager_Model_Node';
            }
        }

        parent::setFromArray($_data);
    }
}
