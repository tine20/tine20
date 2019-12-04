<?php

/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */
class Admin_Setup_Update_12 extends Setup_Update_Abstract
{
    const RELEASE012_UPDATE001 = __CLASS__ . '::update001';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE012_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update001()
    {
        try {
            Admin_Controller_Group::getInstance()->synchronizeGroupAndListMembers();
        } catch (Exception $e) {
            // bail out on the first unhandled problem
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(
                __METHOD__ . '::' . __LINE__ . " " . $e);
        }
        $this->addApplicationUpdate('Admin', '12.1', self::RELEASE012_UPDATE001);
    }
}
