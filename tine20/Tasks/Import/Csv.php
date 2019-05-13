<?php
/**
 * Tine 2.0
 *
 * @package     Tasks
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Christian Feitl<c.feitl@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * csv import class for the Tasks
 *
 * @package     Tasks
 * @subpackage  Import
 *
 */
class Tasks_Import_Csv extends Tinebase_Import_Csv_Generic
{

    protected $_additionalOptions = array(
        'container_id' => '',
        'dates' => array('due')
    );
    /**
     * @param array $_data
     * @return array
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _doConversions($_data)
    {
        $result = parent::_doConversions($_data);

        if($this->_options['demoData']) $result = $this->_getDay($result, $this->_additionalOptions['dates']);

        return $result;
    }

}
