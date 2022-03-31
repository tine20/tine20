<?php
/**
 * Tine 2.0
 * 
 * @package     DFcom
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Christian Feitl <c.feitl@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * csv import class for the DFcom
 * 
 * @package     DFcom
 * @subpackage  Import
 *
 * @property DFCom_Controller_Device     $_controller    protected property!
 */
class DFCom_Import_Device_Csv extends Tinebase_Import_Csv_Abstract
{
    /**
     * additional config options
     * 
     * @var array
     */
    protected $_additionalOptions = array(
        'container_id'      => '',
    );

    //@todo check duplicate record Devices !

    protected function _doConversions($_data)
    {
        $result = parent::_doConversions($_data);
        if($result['authKey'] == '') $result['authKey'] = Tinebase_Record_Abstract::generateUID(20);
        if($result['fwVersion'] == '') $result['fwVersion'] = Tinebase_Record_Abstract::generateUID(10);
        if($result['setupVersion'] == '') $result['setupVersion'] = Tinebase_Record_Abstract::generateUID(20);

        try {
            $container = Tinebase_Container::getInstance()->getContainerByName(DFCom_Model_Device::class, 'Devices', Tinebase_Model_Container::TYPE_SHARED);
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' container "Demo Device" create' . $e);
            $container = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
                'name' => 'Demo Device',
                'type' => Tinebase_Model_Container::TYPE_SHARED,
                'owner_id' => null,
                'backend' => 'Sql',
                'application_id' => Tinebase_Application::getInstance()->getApplicationByName('DFCom')->getId(),
                'model' => 'DFCom_Model_Device',
            ), true));
        }

        $result['container_id'] = $container->getId();

        foreach ($result as $key => $value)
        {
            if($value == '')
            {
                $result[$key] = null;;
            }
        }
        return $result;
    }

}
