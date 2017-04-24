<?php
/**
 * Addressbook Doc generation class
 *
 * @package     Addressbook
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Addressbook Doc generation class
 *
 * @package     Addressbook
 * @subpackage  Export
 *
 */
class Addressbook_Export_Doc extends Tinebase_Export_Richtext_Doc
{
    protected $_defaultExportname = 'adb_default_doc';

    /**
     * @param Tinebase_Record_RecordSet $_records
     * @throws Tinebase_Exception_NotImplemented
     */
    public function processIteration($_records)
    {
        $record = $_records->getFirstRecord();

        $converter = Tinebase_Convert_Factory::factory($record);
        $resolved = $converter->fromTine20Model($record);

        $this->_docTemplate->setValue('salutation_letter', $this->_getSalutation($resolved));
        $this->_docTemplate->setValue('salutation_resolved', $this->_getShortSalutation($resolved));

        $this->_setAddressBlock($resolved);

        parent::processIteration($_records);
    }

    /**
     * returns a formal salutation
     *
     * @param Tinebase_Record_Interface $resolved
     * @return string
     */
    protected function _getSalutation($resolved)
    {
        $i18n = $this->_translate->getAdapter();

        if ($resolved['salutation'] == 'MR') {
            $ret = $i18n->_('Dear Mister');
        } elseif ($resolved['salutation'] == 'MRS') {
            $ret = $i18n->_('Dear Miss');
        } else {
            $ret = $i18n->_('Dear');
        }
        return $ret . ' ' . $resolved['n_given'] . ' ' . $resolved['n_family'];
    }

    /**
     * returns a short salutation
     *
     * @param Tinebase_Record_Interface $resolved
     * @return string
     */
    protected function _getShortSalutation($resolved)
    {
        $i18n = $this->_translate->getAdapter();

        if ($resolved['salutation'] == 'MR') {
            $ret = $i18n->_('Mister');
        } elseif ($resolved['salutation'] == 'MRS') {
            $ret = $i18n->_('Misses');
        } else {
            $ret = '';
        }
        return $ret . ' ' . $resolved['n_given'] . ' ' . $resolved['n_family'];
    }

    /**
     * Renders either private or business address blocked according the preferred address setting
     *
     * The markers to replace are:
     *  ${company#1}
     *  ${firstname#1} ${lastname#1}
     *  ${street#1}
     *  ${postalcode#1} ${locality#1}
     *
     * @param $resolved
     */
    protected function _setAddressBlock($resolved)
    {
        switch ($resolved['preferred_address']) {
            // Private
            case "1":
                $this->_docTemplate->setValue('company', '');
                $this->_docTemplate->setValue('firstname', $resolved['n_given']);
                $this->_docTemplate->setValue('lastname', $resolved['n_family']);
                $this->_docTemplate->setValue('street', $resolved['adr_two_street']);
                $this->_docTemplate->setValue('postalcode', $resolved['adr_two_postalcode']);
                $this->_docTemplate->setValue('locality', $resolved['adr_two_locality']);
                break;
            // Business
            case "0":
            default:
                $this->_docTemplate->setValue('company', $resolved['org_name']);
                $this->_docTemplate->setValue('firstname', $resolved['n_given']);
                $this->_docTemplate->setValue('lastname', $resolved['n_family']);
                $this->_docTemplate->setValue('street', $resolved['adr_one_street']);
                $this->_docTemplate->setValue('postalcode', $resolved['adr_one_postalcode']);
                $this->_docTemplate->setValue('locality', $resolved['adr_one_locality']);
        }
    }
}
