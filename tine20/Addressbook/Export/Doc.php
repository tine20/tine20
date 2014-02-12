<?php
/**
 * Addressbook Doc generation class
 *
 * @package     Addressbook
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Addressbook Doc generation class
 *
 * @package     Addressbook
 * @subpackage  Export
 *
 */
class Addressbook_Export_Doc extends Tinebase_Export_Richtext_Doc {
    /**
     * @var string application name of this export class
     */
    protected $_applicationName = 'Addressbook';
    
    /**
     * the record model
     *
     * @var string
     */
    protected $_modelName = 'Contact';
    
    protected $_defaultExportname = 'adb_default_doc';
    
    
    protected function _onAfterExportRecords($result)
    {
        $user = Tinebase_Core::getUser();
        
        $this->_docTemplate->setValue('date', Tinebase_DateTime::now()->format('Y-m-d'));
        $this->_docTemplate->setValue('account_n_given', $user->accountFirstName);
        $this->_docTemplate->setValue('account_n_family', $user->accountLastName);
    }
    
    public function processIteration($_records)
    {
        $record = $_records->getFirstRecord();
        
        $converter = Tinebase_Convert_Factory::factory($record);
        $resolved = $converter->fromTine20Model($record);
        
        $this->_docTemplate->setValue('salutation_letter', $this->_getSalutation($resolved));
        $this->_docTemplate->setValue('salutation_resolved', $this->_getShortSalutation($resolved));
        
        parent::processIteration($_records);
    }
    
    /**
     * returns a formal salutation
     *
     * @param Tinebase_Record_Interface $record
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
     * @param Tinebase_Record_Interface $record
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
}
