<?php
/**
 * Addressbook Test for Doc generation with datasources
 *
 * @package     Addressbook
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Addressbook Test for Doc generation with datasources
 *
 * @package     Addressbook
 * @subpackage  Export
 *
 */
class Addressbook_Export_TestDocDataSource extends Addressbook_Export_Doc
{
    /**
     * export records
     */
    protected function _exportRecords()
    {
        $filterA = new Addressbook_Model_ContactFilter(array(
            array('field' => 'adr_one_street', 'operator' => 'contains', 'value' => 'Montgomery'),
            array('field' => 'n_given', 'operator' => 'notin', 'value' => array('John', 'James')),
        ));
        $filterB = new Addressbook_Model_ContactFilter(array(
            array('field' => 'adr_one_street', 'operator' => 'contains', 'value' => 'Montgomery'),
            array('field' => 'n_given', 'operator' => 'in', 'value' => array('John', 'James')),
        ));
        $pagination = new Tinebase_Model_Pagination(array('sort' => array('n_given', 'tel_work')));
        $this->_records = array(
            'A' => Addressbook_Controller_Contact::getInstance()->search($filterA, $pagination),
            'B' => Addressbook_Controller_Contact::getInstance()->search($filterB, $pagination)
        );
        parent::_exportRecords();
    }
}
