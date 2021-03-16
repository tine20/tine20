<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */
class Tinebase_Setup_Update_14 extends Setup_Update_Abstract
{
    const RELEASE014_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE014_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE014_UPDATE003 = __CLASS__ . '::update003';

    static protected $_allUpdates = [
        self::PRIO_TINEBASE_STRUCTURE   => [
            self::RELEASE014_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE014_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE014_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
        ],
        self::PRIO_TINEBASE_UPDATE      => [
        ]
    ];

    public function update001()
    {
        try {
            Setup_SchemaTool::updateSchema([
                Tinebase_Model_Tree_FileObject::class,
                Tinebase_Model_Tree_Node::class
            ]);
        } catch (Exception $e) {
            // sometimes this fails with: "PDOException: SQLSTATE[42000]: Syntax error or access violation:
            //                            1091 Can't DROP FOREIGN KEY `main_tree_nodes::parent_id--tree_nodes::id`;
            //                            check that it exists"
            // -> maybe some doctrine problem?
            // -> we just try it again
            Tinebase_Exception::log($e);
            Setup_SchemaTool::updateSchema([
                Tinebase_Model_Tree_FileObject::class,
                Tinebase_Model_Tree_Node::class
            ]);
        }

        $this->addApplicationUpdate('Tinebase', '14.1', self::RELEASE014_UPDATE001);
    }

    public function update002()
    {
        Setup_SchemaTool::updateSchema([
            Tinebase_Model_AuthToken::class,
        ]);

        $this->addApplicationUpdate('Tinebase', '14.2', self::RELEASE014_UPDATE002);
    }

    public function update003()
    {
        if (!$this->_backend->columnExists('mfa_configs', 'accounts')) {
            $this->_backend->addCol('accounts', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>mfa_configs</name>
                    <type>text</type>
                    <length>65535</length>
                </field>'));
        }
        if ($this->_backend->columnExists('pin', 'accounts')) {
            $mfas = Tinebase_Config::getInstance()->{Tinebase_Config::MFA};
            if ($mfas && $mfas->records && ($pinMfa = $mfas->records
                    ->find(Tinebase_Model_MFA_Config::FLD_PROVIDER_CLASS, Tinebase_Auth_MFA_PinAdapter::class))) {
                $userCtrl = Tinebase_User::getInstance();
                foreach ($this->getDb()
                         ->query('select `id`, `pin` from ' . SQL_TABLE_PREFIX . 'accounts WHERE LENGTH(`pin`) > 0')
                         ->fetchAll(Zend_Db::FETCH_ASSOC) as $row) {
                    try {
                        $user = $userCtrl->getUserById($row['id'], Tinebase_Model_FullUser::class);
                    } catch (Exception $e) {
                        continue;
                    }
                    $user->mfa_configs = new Tinebase_Record_RecordSet(Tinebase_Model_MFA_UserConfig::class, [[
                        Tinebase_Model_MFA_UserConfig::FLD_ID => 'pin',
                        Tinebase_Model_MFA_UserConfig::FLD_NOTE => 'pin',
                        Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID => $pinMfa->{Tinebase_Model_MFA_Config::FLD_ID},
                        Tinebase_Model_MFA_UserConfig::FLD_CONFIG => new Tinebase_Model_MFA_PinUserConfig(),
                        Tinebase_Model_MFA_UserConfig::FLD_CONFIG_CLASS => Tinebase_Model_MFA_PinUserConfig::class,
                    ]]);
                    $user->mfa_configs->getFirstRecord()->{Tinebase_Model_MFA_UserConfig::FLD_CONFIG}
                        ->{Tinebase_Model_MFA_PinUserConfig::FLD_HASHED_PIN} = $row['pin'];
                    $userCtrl->updateUser($user);

                }
            }
            $this->_backend->dropCol('accounts', 'pin');
        }

        $this->getDb()->query('DELETE FROM ' . SQL_TABLE_PREFIX . 'config WHERE application_id = ' .
            $this->getDb()->quote(Tinebase_Core::getTinebaseId()) . ' AND name = "areaLocks"');

        if ($this->getTableVersion('accounts') < 17) {
            $this->setTableVersion('accounts', 17);
        }

        $this->addApplicationUpdate('Tinebase', '14.3', self::RELEASE014_UPDATE003);
    }
}
