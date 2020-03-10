<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Christian Feitl<c.feitl@metaways.de>
 */
class Calendar_Import_EventTest extends TestCase
{
    /**
     * @var Tinebase_Model_Container
     */
    protected $_importContainer = null;

    public function testImportDemoData()
    {
        $this->_importContainer = $this->_importDemoData('Calendar', 'Calendar_Model_Event', 'cal_import_event_csv');

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel('Calendar_Model_Event', [
            ['field' => 'container_id', 'operator' => 'equals', 'value' => $this->_importContainer->getId()]
        ]);
        $result = Calendar_Controller_Event::getInstance()->search($filter);
        self::assertEquals(4, count($result));
    }
}