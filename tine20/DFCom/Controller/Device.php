<?php
/**
 * @package     DFCom
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Device controller class for DFCom application
 * 
 * @package     DFCom
 * @subpackage  Controller
 */
class DFCom_Controller_Device extends Tinebase_Controller_Record_Abstract
{
    /**
     * @var int time in seconds to sleep on un auth requests (mainly for unittests)
     */
    public static $unAuthSleepTime = 5;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_applicationName = 'DFCom';

        $this->_modelName = 'DFCom_Model_Device';
        $this->_purgeRecords = false;

        $this->_backend = new Tinebase_Backend_Sql([
            'modelName'     => $this->_modelName,
            'tableName'     => 'dfcom_device',
            'modlogActive'  => true
        ]);
    }
    
    /**
     * holds the instance of the singleton
     *
     * @var DFCom_Controller_Device
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return DFCom_Controller_Device
     */
    public static function getInstance()
    {
        if (static::$_instance === NULL) {
            static::$_instance = new self();
        }
        
        return static::$_instance;
    }

    public function dispatchRecord()
    {
        /** @var Tinebase_Http_Request $request */
        $request = Tinebase_Core::get(Tinebase_Core::REQUEST);
        $query = $request->getQuery();

        if ((int) $query->df_api !== 1) {
            $response = new \Zend\Diactoros\Response('php://memory', 406);
            $response->getBody()->write('Unsupported API version');
            return $response;
        }

        $deviceRecord = DFCom_Model_DeviceRecord::createFromDeviceQuery($query);
        $response = new DFCom_Model_DeviceResponse();
        $deviceListController = DFCom_Controller_DeviceList::getInstance();
        $deviceRecordController = DFCom_Controller_DeviceRecord::getInstance();

        // order of execution matters here, because of the many get/setUsers!
        // ATTENTION do not change order of these lines unless you understand why the order matters
        $assertACLUsageCallbacks = [
            $this->assertPublicUsage(),
            $deviceListController->assertPublicUsage(),
            $deviceRecordController->assertPublicUsage(),
        ];
        // end attention

        $transaction = Tinebase_RAII::getTransactionManagerRAII();
        $setupAuthKey = DFCom_Config::getInstance()->get(DFCom_Config::SETUP_AUTH_KEY);
        $setupStatus = $deviceRecord->xprops('data')['setupStatus'] ?? '1000';

        try {
            /** @var DFCom_Model_Device $device */
            $device = $this->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(DFCom_Model_Device::class, [
                ['field' => 'deviceString', 'operator' => 'equals', 'value' => $deviceRecord->xprops('data')['deviceString']],
                ['field' => 'serialNumber', 'operator' => 'equals', 'value' => $deviceRecord->xprops('data')['serialNumber']],
            ]))->getFirstRecord();

            if (!$device) {
                if ($setupAuthKey == $deviceRecord->xprops('data')['authKey']) {
                    // create new device
                    // @TODO: have new device in "pending" state so it can not produce
                    //        real data before it get's confirmed
                    $device = DFCom_Controller_Device::getInstance()->create(new DFCom_Model_Device(array_merge($deviceRecord->xprops('data'), [
                        'name' => Tinebase_Translation::getTranslation('DFCom')->translate('New Device'),
                        'authKey' => Tinebase_Record_Abstract::generateUID(20),
                        'container_id' => DFCom_Config::getInstance()->get(DFCom_Config::DEFAULT_DEVICE_CONTAINER),
                        'timezone' => Tinebase_Core::getUserTimezone(),
                    ])));

                    $deviceListController->createDefaultDeviceLists($device);
                    $deviceRecord->xprops('data')['authKey'] = $device->authKey;
                    $response->setDeviceVariable('authKey', $device->authKey);
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                        . " got wrong setup authKey: " . $deviceRecord->xprops('data')['authKey']);
                }
            }


            if ($device && $device->controlCommands) {
//                $response->setDeviceVariable('authKey', $device->authKey);
//                $response->setService('10.133.1.222', '18000');
                $comments = [];
                foreach(explode("\n", $device->controlCommands) as $controlComand) {
                    try {
                        if (preg_match('/^\/\/|#/', $controlComand)) {
                            $comments[] = $controlComand;
                        } else {
                            eval('$response->' . preg_replace('/;{0,1}$/', ';', trim($controlComand)));
                        }
                    } catch (Exception $e) {
                        $comments[] = "// {$controlComand} failed -> {$e->getMessage()}";
                    }
                }
                $device->controlCommands = implode("\n", $comments);
            } else { // why else here??? -> to send commands to unauthed!
                // process record
                if (!$device || $device->authKey !== $deviceRecord->xprops('data')['authKey']) {
                    if ($device && $deviceRecord->xprops('data')['authKey'] === $setupAuthKey) {
                        // authKey get's lost after setup updates
                        $deviceRecord->xprops('data')['authKey'] = $device->authKey;
                        $response->setDeviceVariable('authKey', $device->authKey);
                    } else {
                        sleep(DFCom_Controller_Device::$unAuthSleepTime);
                        if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                            . " got wrong authKey: " . $deviceRecord->xprops('data')['authKey']);
                        $response = new \Zend\Diactoros\Response('php://memory', 401);
                        $response->getBody()->write("Device authentication failed");
                        return $response;
                    }
                }

                $deviceRecord->device_id = $device->getId();

                $lists = $deviceListController->getDeviceLists($device);

                // writing setup deletes list on device
                if ($setupStatus[0] === '0') {
                    $lists->list_version = null;
                    $lists->list_status = null;
                    foreach($lists as $idx => $list) {
                        /** @var DFCom_Model_DeviceList $list */
                        $lists[$idx] = $deviceListController->update($list);
                    }
                    $setupStatus[0] = "1";
                    $deviceRecord->xprops('data')['setupStatus'] = $setupStatus;
                    $response->setDeviceVariable('setupStatus', $setupStatus);
                }

                array_push($assertACLUsageCallbacks, HumanResources_Controller_Employee::getInstance()->assertPublicUsage());
                // check if we have a list update
                foreach($lists as $list) {
                    /** @var DFCom_Model_DeviceList $list */
                    try {
                        if ($list->list_version != $deviceListController->getSyncToken($list)) {
                            $response->updateDeviceList($list, $device);
                            // device supports one list per request only
                            break;
                        }
                    } catch (Exception $e) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . ' cannot evaluate export definition ' . $e);
                    }
                }

                switch ($deviceRecord->device_table) {
                    case 'alive':
                            // noting special to do here
                        break;
                    case 'listFeedback':
                        $lists = $deviceListController->getDeviceLists($device);
                        /** @var DFCom_Model_DeviceList $list */
                        foreach($lists as $list) {
                            // NOTE: device can't signal for which list the feedback was
                            if ($list->list_status == -1) {
                                $list->list_status = $deviceRecord->xprops('data')['reason'];
                                $deviceListController->update($list);
                            }
                        }
                        break;
                    default:
//                  case ... user defined records:
                        $cancel = false;
                        $handlers = DFCom_Config::getInstance()->get(DFCom_Config::DEVICE_RECORD_HANDLERS);
                        $deviceRecord->{DFCom_Model_DeviceRecord::FLD_PROCESSED} = [];
                        if (array_key_exists($deviceRecord->device_table, $handlers)) {
                            $handlerClass = $handlers[$deviceRecord->device_table];
                            try {
                                $handler = new $handlerClass([
                                    'device' => $device,
                                    'deviceRecord' => $deviceRecord,
                                    'deviceResponse' => $response
                                ]);

                                /* once this is a loop do we want to && or || $cancle? one handler can prevent persitence? or do all handler need to agree on that? */
                                $cancel = $handler->handle();
                            } catch(Exception $e) {
                                Tinebase_Core::getLogger()->ERR(__METHOD__ . '::' . __LINE__ . " can't execute handler $handlerClass for record type {$deviceRecord->device_table}:\n$e");
                            }
                        }
                        if (! $cancel) {
                            $deviceRecordController->create($deviceRecord);
                        }
                        break;
                }
            }

            // update dateTime and maybe more (like flush)
            $device->mergeStatusData($deviceRecord);
            $device->lastSeen = Tinebase_DateTime::now();
            $this->update($device);

            $transaction->release();
        } finally {
            // order of execution matters here, because of the many get/setUsers!
            // we need to do it in reverse order of the initalization!
            foreach(array_reverse($assertACLUsageCallbacks) as $assertACLUsageCallback) {
                $assertACLUsageCallback();
            }
        }

        $response->setTime(Tinebase_DateTime::now()->setTimezone($device->timezone));
        return $response->getHTTPResponse();
    }
}
