<?php

/**
 * Tine 2.0
 *
 * @package     DFCom
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2022.11 (ONLY!)
 */
class DFCom_Setup_Update_15 extends Setup_Update_Abstract
{
    const RELEASE015_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE015_UPDATE001 = __CLASS__ . '::update001';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE015_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE015_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate('DFCom', '15.0', self::RELEASE015_UPDATE000);
    }

    public function update001()
    {
        DFCom_Config::getInstance()->set(DFCom_Config::DEFAULT_DEVICE_LISTS, [
            'DFCom_device_list_employee',
            'DFCom_device_list_absenceReasons',
        ]);

        // add DFCom_device_list_absenceReasons to all devices
        foreach(DFCom_Controller_Device::getInstance()->getAll() as $device) {
            $exportDefinition = Tinebase_ImportExportDefinition::getInstance()->getByName('DFCom_device_list_absenceReasons');
            $options = Tinebase_ImportExportDefinition::getInstance()->getOptionsAsZendConfigXml($exportDefinition);
            DFCom_Controller_DeviceList::getInstance()->create(new DFCom_Model_DeviceList([
                'device_id' => $device->getId(),
                'name' => $options->deviceName,
                'export_definition_id' => $exportDefinition->getId(),
            ]));
        }

        Setup_SchemaTool::updateSchema([DFCom_Model_Device::class]);
        $this->addApplicationUpdate('DFCom', '15.1', self::RELEASE015_UPDATE001);
    }
}
