<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2019-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 * this ist 2020.11 (ONLY!)
 */
class Addressbook_Setup_Update_13 extends Setup_Update_Abstract
{
    const RELEASE013_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE013_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE013_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE013_UPDATE004 = __CLASS__ . '::update004';
    const RELEASE013_UPDATE005 = __CLASS__ . '::update005';
    const RELEASE013_UPDATE006 = __CLASS__ . '::update006';
    const RELEASE013_UPDATE007 = __CLASS__ . '::update007';

    static protected $_allUpdates = [
        self::PRIO_TINEBASE_STRUCTURE        => [
            self::RELEASE013_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE => [
            self::RELEASE013_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
            self::RELEASE013_UPDATE005          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update005',
            ],
            self::RELEASE013_UPDATE006          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update006',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE013_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE013_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE013_UPDATE007          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update007',
            ]
        ],
    ];

    public function update001()
    {
        $containerController = Tinebase_Container::getInstance();
        $userController = Tinebase_User::getInstance();
        $users = $userController->getUsers();
        foreach ($users as $user) {
            $personalContainers = $containerController->getPersonalContainer(
                $user,
                Addressbook_Model_Contact::class,
                $user, Tinebase_Model_Grants::GRANT_READ,
                true
            );
            foreach ($personalContainers as $personalContainer) {
                $allgrants = $containerController->getGrantsOfContainer($personalContainer, true);

                foreach ($allgrants as $grant) {
                    if ($grant->account_id == $personalContainer->owner_id) {
                        $grant->privateDataGrant = true;
                    }
                }

                $containerController->setGrants($personalContainer, $allgrants, TRUE);
            }
        }
        $this->addApplicationUpdate('Addressbook', '13.1', self::RELEASE013_UPDATE001);
    }

    public function update002()
    {
        $containerController = Tinebase_Container::getInstance();
        $userController = Tinebase_User::getInstance();
        $users = $userController->getUsers();
        foreach ($users as $user) {
            // set Grant for personal Container
            $personalContainers = $containerController->getPersonalContainer(
                $user,
                Addressbook_Model_List::class,
                $user, Tinebase_Model_Grants::GRANT_READ,
                true
            );
            foreach ($personalContainers as $personalContainer) {
                $allgrants = $containerController->getGrantsOfContainer($personalContainer, true);

                foreach ($allgrants as $grant) {
                    if ($grant->account_id == $personalContainer->owner_id) {
                        $grant->privateDataGrant = true;
                    }
                }

                $containerController->setGrants($personalContainer, $allgrants, TRUE);
            }

            $sharedContainers = $containerController->getSharedContainer(
                $user,
                Addressbook_Model_List::class,
                $user, Tinebase_Model_Grants::GRANT_READ,
                true
            );

            //shared Container
            foreach ($sharedContainers as $sharedContainer) {
                $allgrants = $containerController->getGrantsOfContainer($sharedContainer, true);

                foreach ($allgrants as $grant) {
                    $grant->privateDataGrant = true;
                }

                $containerController->setGrants($sharedContainer, $allgrants, TRUE);
            }

        }

        $this->addApplicationUpdate('Addressbook', '13.2', self::RELEASE013_UPDATE002);
    }

    public function update003()
    {
        $this->addApplicationUpdate('Addressbook', '13.3', self::RELEASE013_UPDATE003);
    }

    public function update004()
    {
        $this->addApplicationUpdate('Addressbook', '13.4', self::RELEASE013_UPDATE004);
    }

    public function update005()
    {
        $this->addApplicationUpdate('Addressbook', '13.5', self::RELEASE013_UPDATE005);
    }

    public function update006()
    {
        Setup_SchemaTool::updateSchema([Addressbook_Model_Contact::class]);
        $this->addApplicationUpdate('Addressbook', '13.6', self::RELEASE013_UPDATE006);
    }

    public function update007()
    {
        Tinebase_ImportExportDefinition::getInstance()->getExportDefinitionsForApplication(Tinebase_Application::getInstance()->getApplicationByName('Addressbook'));
        $this->addApplicationUpdate('Addressbook', '13.7', self::RELEASE013_UPDATE007);
    }
}
