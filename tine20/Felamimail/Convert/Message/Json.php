<?php
/**
 * convert functions for records from/to json (array) format
 * 
 * @package     Felamimail
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for records from/to json (array) format
 *
 * @package     Felamimail
 * @subpackage  Convert
 */
class Felamimail_Convert_Message_Json extends Tinebase_Convert_Json
{
   /**
    * parent converts Tinebase_Record_RecordSet to external format
    *
    * @param Tinebase_Record_RecordSet  $_records
    * @param Tinebase_Model_Filter_FilterGroup $_filter
    * @param Tinebase_Model_Pagination $_pagination
    * @return mixed
    */
    public function fromTine20RecordSet(Tinebase_Record_RecordSet $_records = NULL, $_filter = NULL, $_pagination = NULL)
    {
        $this->_dehydrateFileLocations($_records);

        return parent::fromTine20RecordSet($_records, $_filter, $_pagination);
    }

    /**
     * converts Tinebase_Record_Interface to external format
     *
     * @param  Tinebase_Record_Interface  $_record
     * @return mixed
     */
    public function fromTine20Model(Tinebase_Record_Interface $_record)
    {
        $this->_dehydrateFileLocations([$_record]);
        return parent::fromTine20Model($_record);
    }

    /**
     * get fileLocations and add them to records
     *
     * @param $_records
     */
    protected function _dehydrateFileLocations($_records)
    {
        foreach ($_records as $record) {
            $record->fileLocations = Felamimail_Controller_MessageFileLocation::getInstance()->getLocationsForMessage($record);
        }
    }
}
