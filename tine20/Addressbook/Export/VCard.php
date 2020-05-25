<?php
/**
 * Tine 2.0
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * @package     Addressbook
 */
class Addressbook_Export_VCard extends Tinebase_Export_VObject
{
    /**
     * @var Addressbook_Convert_Contact_VCard_Generic
     */
    protected $_converter = null;
    protected $_defaultExportname = 'adb_default_vcard';
    protected $_format = 'vcf';

    /**
     * get download content type
     *
     * @return string
     *
     * @see https://en.wikipedia.org/wiki/VCard - vCard 3.0 specification, RFC 2426 specified the format for vCards in directories
     */
    public function getDownloadContentType()
    {
        return 'text/directory';
    }

    /**
     * @return string|null
     */
    public function generate()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Generating VCARD export ...');
        }

        $this->_converter = new Addressbook_Convert_Contact_VCard_Generic();
        $this->_exportRecords();
        return $this->_returnExportFilename();
    }

    protected function _createDocument(Tinebase_Record_Interface $_record)
    {
        return "";
    }

    protected function _addRecordToDocument(Tinebase_Record_Interface $_record)
    {
        $vcard = $this->_converter->fromTine20Model($_record);
        $this->_document .= $vcard->serialize();
    }

    protected function _addRecordToFile(Tinebase_Record_Interface $_record)
    {
        $this->_checkMaxFileSize();

        $vcard = $this->_converter->fromTine20Model($_record);

        if ($this->_exportFileHandle === null) {
            $this->_createExportFilehandle();
        }

        fwrite($this->_exportFileHandle, $vcard->serialize());
    }
}
