<?php
/**
 * convert functions for records from/to json (array) format
 * 
 * @package     Addressbook
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for records from/to json (array) format
 *
 * @package     Addressbook
 * @subpackage  Convert
 */
class Addressbook_Convert_Contact_Json extends Tinebase_Convert_Json
{   
   /**
    * parent converts Tinebase_Record_RecordSet to external format
    * this resolves Image Paths
    * @TODO: Can be removed when "0000284: modlog of contact images / move images to vfs" is resolved.
    * @param  Tinebase_Record_RecordSet  $_records
    * @return mixed
    */
    public function fromTine20RecordSet(Tinebase_Record_RecordSet $_records)
    {
        if (count($_records) == 0) {
            return array();
        }
        
        Addressbook_Frontend_Json::resolveImages($_records);
                
        return parent::fromTine20RecordSet($_records);
    }
}
