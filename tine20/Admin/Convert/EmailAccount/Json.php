<?php
/**
 * convert functions for records from/to json (array) format
 *
 * @package     Admin
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for records from/to json (array) format
 *
 * @package     Admin
 * @subpackage  Convert
 */
class Admin_Convert_EmailAccount_Json extends Felamimail_Convert_Account_Json
{
    /**
     * converts Tinebase_Record_Interface to external format
     *
     * @param  Tinebase_Record_Interface $_record
     * @return mixed
     */
    public function fromTine20Model(Tinebase_Record_Interface $_record)
    {
        Admin_Controller_EmailAccount::getInstance()->resolveAccountEmailUsers($_record);
        $result = parent::fromTine20Model($_record);

        return $result;
    }

}
