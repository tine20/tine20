<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * @package     Tinebase
 * @subpackage  Import
 */
class Tinebase_Import_Xls_Generic extends Tinebase_Import_Xls_Abstract
{
    /**
     * Used to store mapping information
     *
     * @var array
     */
    protected $_mapping = [];

    /**
     * creates a new importer from an importexport definition
     *
     * @param  Tinebase_Model_ImportExportDefinition $_definition
     * @param  array $_config
     * @return Tinebase_Import_Abstract
     */
    public static function createFromDefinition(
        Tinebase_Model_ImportExportDefinition $_definition,
        array $_config = []
    ) {
        return new Tinebase_Import_Xls_Generic(self::getOptionsArrayFromDefinition($_definition, $_config));
    }

    /**
     * @param  $_resource
     */
    protected function _beforeImport($_resource = null)
    {
        if (null === $this->_options['headlineRow']) {
            return;
        }

        $rowIterator = $this->_worksheet->getRowIterator($this->_options['headlineRow'],
            $this->_options['headlineRow']);

        foreach ($rowIterator->current()->getCellIterator($this->_options['startColumn'],
            $this->_options['endColumn']) as $cell) {

            /* @var $cell Cell */
            $this->_mapping[] = $cell->getValue();
        }
    }

    /**
     * do the mapping and replacements
     *
     * @param array $_data
     * @return array
     */
    protected function _doMapping($_data)
    {
        $mappedData = [];

        foreach ($_data as $index => $data) {
            $mappedData[$this->_mapping[$index]] = $data;
        }

        return $mappedData;
    }
}
