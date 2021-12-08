<?php
/**
 * Tine 2.0
 *
 * @package     SSO
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 * this is 2021.11 (ONLY!)
 */
class SSO_Setup_Update_14 extends Setup_Update_Abstract
{
    const RELEASE014_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE014_UPDATE002 = __CLASS__ . '::update002';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE => [
            self::RELEASE014_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE014_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
        ],
    ];

    public function update001()
    {
        Setup_SchemaTool::updateSchema([SSO_Model_RelyingParty::class]);
        $this->addApplicationUpdate(SSO_Config::APP_NAME, '14.00', self::RELEASE014_UPDATE001);
    }

    public function update002()
    {
        Setup_SchemaTool::updateSchema([SSO_Model_RelyingParty::class]);
        $this->addApplicationUpdate(SSO_Config::APP_NAME, '14.01', self::RELEASE014_UPDATE002);
    }
}
