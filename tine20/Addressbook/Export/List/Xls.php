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

    protected function _extendTwigSetup()
    {
        $this->_twig->getEnvironment()->addFunction(new Twig_SimpleFunction('listType', function ($list, $locale = null) {
            if ($locale !== null) {
                $locale = Tinebase_Translation::getLocale($locale);
            }

            if ($list instanceof Addressbook_Model_List) {
                return $list->getType($locale);
            }
        }));
    }
}
