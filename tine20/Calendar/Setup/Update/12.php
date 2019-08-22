<?php

/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2018-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Calendar_Setup_Update_12 extends Setup_Update_Abstract
{
    const RELEASE012_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE012_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE012_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE012_UPDATE004 = __CLASS__ . '::update004';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE012_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE012_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE012_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
            self::RELEASE012_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
        ],
    ];

    public function update001()
    {
        $release11 = new Calendar_Setup_Update_Release11($this->_backend);
        $release11->update_13();
        $this->addApplicationUpdate('Calendar', '12.7', self::RELEASE012_UPDATE001);
    }

    public function update002()
    {
        $release11 = new Calendar_Setup_Update_Release11($this->_backend);
        $release11->update_14();
        $this->addApplicationUpdate('Calendar', '12.8', self::RELEASE012_UPDATE002);
    }

    public function update003()
    {
        $release10 = new Calendar_Setup_Update_Release10($this->_backend);
        $release10->update_11();
        $this->addApplicationUpdate('Calendar', '12.9', self::RELEASE012_UPDATE003);
    }

    public function update004()
    {
        $controller = Tinebase_Container::getInstance();
        $aclFilter = $controller->doSearchAclFilter(false);
        $all_container = $controller->search(new Tinebase_Model_ContainerFilter([
            ['field' => 'application_id', 'operator' => 'equals', 'value' =>
                Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId()],
            ['field' => 'model', 'operator' => 'equals', 'value' => 'Calendar_Model_']
            ]));
        $controller->doSearchAclFilter($aclFilter);

        foreach ($all_container as $container)
        {
                $container['model'] = 'Calendar_Model_Event';
                $controller->update($container);
        }

        $this->addApplicationUpdate('Calendar', '12.10', self::RELEASE012_UPDATE004);
    }
}
