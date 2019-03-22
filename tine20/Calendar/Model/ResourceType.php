<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Calendar ResourceType Record Class
 *
 * @package     Calendar
 * @subpackage  Model
 */
class Calendar_Model_ResourceType extends Tinebase_Config_KeyFieldRecord
{
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Calendar';

    public function __construct($_data = NULL, $_bypassFilters = FALSE, $_convertDates = TRUE) {
        $this->_validators['is_location'] =  array('allowEmpty' => TRUE);
        parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
}
