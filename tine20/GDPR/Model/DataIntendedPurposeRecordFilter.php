<?php
/**
 * class to filter for DataIntendedPurposeRecord
 *
 * @package     GDPR
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to filter for DataIntendedPurposeRecord
 *
 * @package     GDPR
 * @subpackage  Model
 */
class GDPR_Model_DataIntendedPurposeRecordFilter extends Tinebase_Model_Filter_FilterGroup
{
    const OPTIONS_SHOW_WITHDRAWN = 'showWithdrawn';

    protected $_configuredModel = GDPR_Model_DataIntendedPurposeRecord::class;

    public function getFilterObjects()
    {
        $filters = parent::getFilterObjects();
        if ((!is_array($this->_options) || !isset($this->_options[self::OPTIONS_SHOW_WITHDRAWN]) ||
                !$this->_options[self::OPTIONS_SHOW_WITHDRAWN]) && null === $this->_findFilter('withdrawDate')) {
            $filter = $this->createFilter('withdrawDate', 'equals', null);
            $filter->isImplicit(true);
            $filters[] = $filter;
        }

        return $filters;
    }
}
