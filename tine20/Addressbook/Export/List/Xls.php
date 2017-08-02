<?php
/**
 * Addressbook List Xls generation class
 *
 * @package     Addressbook
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Addressbook List Xls generation class
 *
 * @package     Addressbook
 * @subpackage  Export
 *
 */
class Addressbook_Export_List_Xls extends Tinebase_Export_Xls
{
    use Addressbook_Export_List_Trait;

    protected $_defaultExportname = 'adb_list_xls';
}
