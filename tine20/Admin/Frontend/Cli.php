<?php
/**
 * Tine 2.0
 * @package     Admin
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * cli server for Admin
 *
 * This class handles cli requests for the Admin
 *
 * @package     Admin
 * @subpackage  Frontend
 */
class Admin_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = 'Admin';
    
    /**
     * help array with function names and param descriptions
     */
    protected $_help = array(
        'importUser' => array(
            'description'   => 'Import new users into the Admin.',
            'params'        => array(
                'filenames'   => 'Filename(s) of import file(s) [required]',
                'definition'  => 'Name of the import definition or filename [required] -> for example admin_user_import_csv(.xml)',
            )
        ),
    );

    /**
     * create system groups for addressbook lists that don't have a system group
     *
     * @param Zend_Console_Getopt $_opts
     */
    public function createSystemGroupsForAddressbookLists(Zend_Console_Getopt $_opts)
    {
        $_filter = new Addressbook_Model_ListFilter();

        $iterator = new Tinebase_Record_Iterator(array(
            'iteratable' => $this,
            'controller' => Addressbook_Controller_List::getInstance(),
            'filter' => $_filter,
            'options' => array('getRelations' => false),
            'function' => 'iterateAddressbookLists',
        ));
        $result = $iterator->iterate();

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            if (false === $result) {
                $result['totalcount'] = 0;
            }
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Worked on ' . $result['totalcount'] . ' lists');
        }
    }

    /**
     * iterate adb lists
     *
     * @param Tinebase_Record_RecordSet $records
     */
    public function iterateAddressbookLists(Tinebase_Record_RecordSet $records)
    {
        $addContactController = Addressbook_Controller_Contact::getInstance();
        $admGroupController = Admin_Controller_Group::getInstance();
        $admUserController = Admin_Controller_User::getInstance();
        $userContactIds = array();
        foreach ($records as $list) {
            if ($list->type == 'group') {
                echo "Skipping list " . $list->name ."\n";
            }

            /**
             * @var Addressbook_Model_List $list
             */
            if (!empty($list->group_id)) {
                continue;
            }

            $group = new Tinebase_Model_Group(array(
                'container_id'  => $list->container_id,
                'list_id'       => $list->getId(),
                'name'          => $list->name,
                'description'   => $list->description,
                'email'         => $list->email,
            ));

            $allMembers = array();
            $members = $addContactController->getMultiple($list->members);
            foreach ($members as $member) {

                if ($member->type == Addressbook_Model_Contact::CONTACTTYPE_CONTACT && ! in_array($member->getId(), $userContactIds)) {
                    $pwd = Tinebase_Record_Abstract::generateUID();
                    $user = new Tinebase_Model_FullUser(array(
                        'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getDefaultGroup()->getId(),
                        'contact_id'            => $member->getId(),
                        'accountDisplayName'    => $member->n_fileas ? $member->n_fileas : $member->n_fn,
                        'accountLastName'       => $member->n_family ? $member->n_family : $member->n_fn,
                        'accountFullName'       => $member->n_fn,
                        'accountFirstName'      => $member->n_given ? $member->n_given : '',
                        'accountEmailAddress'   => $member->email,
                    ), true);

                    echo 'Creating user ' . $user->accountLoginName . "...\n";
                    $user = $admUserController->create($user, $pwd, $pwd);
                    $member->account_id = $user->getId();
                    $userContactIds[] = $member->getId();
                }

                $allMembers[] = $member->account_id;
            }

            $group->members = $allMembers;

            echo 'Creating group ' . $group->name . "...\n";

            try {
                $admGroupController->create($group);
            } catch (Exception $e) {
                echo $e->getMessage() . "\n";
            }
        }
    }

    /**
     * import users
     *
     * @param Zend_Console_Getopt $_opts
     */
    public function importUser($_opts)
    {
        parent::_import($_opts);
    }
    
    /**
     * import groups
     *
     * @param Zend_Console_Getopt $_opts
     */
    public function importGroups($_opts)
    {
        parent::_import($_opts);
    }

    /**
     * overwrite Samba options for users
     *
     */
    public function repairUserSambaoptions($_opts)
    {
        $args = $_opts->getRemainingArgs();
        if ($_opts->d) {
            array_push($args, '--dry');
        }
        $_opts->setArguments($args);
        $blacklist = array(); // List of Loginnames
        $count = 0;
        $tinebaseUser  = Tinebase_User::getInstance();
        $users = $tinebaseUser->getUsers();
        
        foreach ($users as $id) {
            $user = $tinebaseUser->getFullUserById($id->getId());
            
            if (isset($user['sambaSAM']) && empty($user['sambaSAM']['homeDrive']) && !in_array($user->accountLoginName, $blacklist)) {
                echo($user->getId() . ' : ' . $user->accountLoginName);
                echo("\n");
                
                //This must be adjusted
                $samUser = new Tinebase_Model_SAMUser(array(
                    'homePath'    => '\\\\fileserver\\' . $user->accountLoginName,
                    'homeDrive'   => 'H:',
                    'logonScript' => 'script.cmd',
                    'profilePath' => '\\\\fileserver\\profiles\\' . $user->accountLoginName
                ));
                $user->sambaSAM = $samUser;
                
                if ($_opts->d) {
                    print_r($user);
                } else {
                    $tinebaseUser->updateUser($user);
                }
                $count++;
            };
        }
        echo('Found ' . $count . ' users!');
        echo("\n");
    }

    public function deleteAccount($_opts)
    {
        $args = $this->_parseArgs($_opts);
        if (!isset($args['accountName'])) exit('accountName required');

        // user deletion need the confirmation header
        Admin_Controller_User::getInstance()->setRequestContext(['confirm' => true]);
        Admin_Controller_User::getInstance()->delete([Tinebase_User::getInstance()
            ->getUserByLoginName($args['accountName'])->getId()]);

        echo 'deleted account ' . $args['accountName'] . PHP_EOL;
    }

    /**
     * shorten loginnmes to fit ad samaccountname
     *
     */
    public function shortenLoginnames($_opts)
    {
        $count = 0;
        $tinebaseUser  = Tinebase_User::getInstance();
        $users = $tinebaseUser->getUsers();
        $length = 20;
        
        foreach ($users as $id) {
            $user = $tinebaseUser->getFullUserById($id->getId());
            if (strlen($user->accountLoginName) > $length) {
                $newAccountLoginName = substr($user->accountLoginName, 0, $length);
                
                echo($user->getId() . ' : ' . $user->accountLoginName . ' > ' . $newAccountLoginName);
                echo("\n");
                
                $samUser = new Tinebase_Model_SAMUser(array(
                        'homePath'    => str_replace($user->accountLoginName, $newAccountLoginName, $user->sambaSAM->homePath),
                        'homeDrive'   => $user->sambaSAM->homeDrive,
                        'logonScript' => $user->sambaSAM->logonScript,
                        'profilePath' => $user->sambaSAM->profilePath
                ));
                $user->sambaSAM = $samUser;
                
                $user->accountLoginName = $newAccountLoginName;
                
                if ($_opts->d) {
                    var_dump($user);
                } else {
                    $tinebaseUser->updateUser($user);
                }
                $count++;
            };
        }
        echo('Found ' . $count . ' users!');
        echo("\n");
    }

    /**
     * usage: method=Admin.synchronizeGroupAndListMembers [-d]
     *
     * @param Zend_Console_Getopt $opts
     */
    public function synchronizeGroupAndListMembers(Zend_Console_Getopt $opts)
    {
        $this->_checkAdminRight();

        $groupUpdateCount = Admin_Controller_Group::getInstance()->synchronizeGroupAndListMembers($opts->d);
        if ($opts->d) {
            echo "--DRY RUN--\n";
        }
        echo "Repaired " . $groupUpdateCount . " groups and or lists\n";
    }

    /**
     * usage: method=Admin.getSetEmailAliasesAndForwards [-d] [-v] [aliases_forwards.csv] [-- pwlist=pws.csv]
     *
     * @param Zend_Console_Getopt $opts
     */
    public function getSetEmailAliasesAndForwards(Zend_Console_Getopt $opts)
    {
        $args = $this->_parseArgs($opts, array(), 'aliases_forwards_csv');

        $tinebaseUser = Tinebase_User::getInstance();

        if (! isset($args['aliases_forwards_csv'])) {
            foreach ($tinebaseUser->getUsers() as $user) {
                if (! empty($user->accountEmailAddress)) {
                    $fullUser = Tinebase_User::getInstance()->getFullUserById($user);
                    $aliases = [];
                    if (is_array($fullUser->emailUser->emailAliases)) {
                        foreach ($fullUser->emailUser->emailAliases as $alias) {
                            $aliases[] = is_array($alias) ? $alias['email'] : $alias;
                        }
                    }
                    $aliases = implode($aliases, ',');
                    $forwards = is_array($fullUser->emailUser->emailForwards) ? implode($fullUser->emailUser->emailForwards, ',') : '';
                    echo $fullUser->accountLoginName . ';' . $aliases . ';' . $forwards . "\n";
                }
            }
        } else {
            $pw = null;
            if (isset($args['pwlist'])) {
                $pw = $this->_readCsv($args['pwlist'], true);
                if ($pw && $opts->v) {
                    echo "using pwlist file " . $args['pwlist'] . "\n";
                }
            }

            foreach ($args['aliases_forwards_csv'] as $csv) {
                $users = $this->_readCsv($csv);
                if (!$users) {
                    echo "no users found in file";
                    break;
                }
                foreach ($users as $userdata) {
                    // 0=loginname, 1=aliases, 2=forwards
                    if ($opts->v) {
                        print_r($userdata);
                    }

                    $password = null;
                    if ($pw) {
                        if (! isset($pw[$userdata[0]])) {
                            echo "user " . $userdata[0] . " not in pwlist - skipping\n";
                            continue;
                        } else {
                            $password = $pw[$userdata[0]];
                            if ($opts->v) {
                                echo "setting pw " . $password . " for user " . $userdata[0] . "\n";
                            }
                        }
                    }

                    try {
                        $user = Tinebase_User::getInstance()->getFullUserByLoginName($userdata[0]);
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        echo $tenf->getMessage() . "\n";
                        break;
                    }
                    // @todo fix in 2020.11 - we now have aliases/forwards models
                    $user->smtpUser = new Tinebase_Model_EmailUser(array(
                        'emailAddress' => $user->accountEmailAddress,
                        'emailAliases' => !empty($userdata[1]) ? explode(',', $userdata[1]) : [],
                        'emailForwards' => !empty($userdata[2]) ? explode(',', $userdata[2]) : [],
                    ));
                    if (!$opts->d) {
                        Admin_Controller_User::getInstance()->update($user, $password, $password);
                    }
                }
            }
        }
    }

    /**
     * @param string $filename
     * @return false|string
     */
    protected function _checkSanitizeFilename(string $filename)
    {
        if (!file_exists($filename)) {
            $filename = getcwd() . DIRECTORY_SEPARATOR . 'csv';
            if (!file_exists($filename)) {
                echo "file not found: " . $filename . "\n";
                return false;
            }
        }

        return $filename;
    }

    /**
     * usage: method=Admin.setPasswords [-d] [-v] userlist.csv [-- pw=password sendmail=1 pwlist=pws.csv]
     *
     * @param Zend_Console_Getopt $opts
     * @return integer
     *
     * @todo allow to define separator / mapping
     */
    public function setPasswords(Zend_Console_Getopt $opts)
    {
        $args = $this->_parseArgs($opts, array(), 'userlist_csv');

        // input csv/user list
        if (! isset($args['userlist_csv'])) {
            echo "userlist file param required or file not found. usage: method=Admin.setRandomPasswords [-d] userlist.csv\n";
            return 2;
        }

        if (isset($args['pwlist'])) {
            $pw = $this->_readCsv($args['pwlist'], true);
            if ($pw && $opts->v) {
                echo "using pwlist file " . $args['pwlist'] . "\n";
            }
        } else {
            $pw = $args['pw'] ?? null;
        }

        foreach ($args['userlist_csv'] as $csv) {
            $users = $this->_readCsv($csv);
            if (! $users) {
                echo "no users found in file\n";
                break;
            }

            if ($opts->v) {
                print_r($args);
                print_r($users);
            }

            $sendmail = isset($args['sendmail']) && (bool) $args['sendmail'];
            $this->_setPasswordsForUsers($opts, $users, $pw, $sendmail);
        }

        return 0;
    }

    /**
     * @param string $csv filename
     * @param boolean $firstColIsKey
     * @return array|false
     */
    protected function _readCsv($csv, $firstColIsKey = false)
    {
        $csv = $this->_checkSanitizeFilename($csv);
        if (! $csv) {
            return false;
        }

        $stream = fopen($csv, 'r');
        if (!$stream) {
            echo "file could not be opened: " . $csv . "\n";
            return false;
        }
        $users = [];
        while ($line = fgetcsv($stream, 0, ';')) {
            if ($firstColIsKey) {
                $users[$line[0]] = $line[1];
            } else {
                $users[] = $line;
            }
        }
        fclose($stream);
        return $users;
    }

    /**
     * set random pws for array with userdata
     *
     * @param Zend_Console_Getopt $opts
     * @param array $users
     * @param string|array $pw
     * @param boolean $sendmail
     *
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    protected function _setPasswordsForUsers(Zend_Console_Getopt $opts, $users, $pw = null, $sendmail = false)
    {
        $pwCsv = '';

        foreach ($users as $userdata) {
            if (empty($userdata[0])) {
                continue;
            }

            // get user by email or @todo accountname
            // @todo allow to define columns with username/email/...
            try {
                $user = Tinebase_User::getInstance()->getUserByProperty('accountEmailAddress', $userdata[0]);
                $fullUser = Tinebase_User::getInstance()->getFullUserById($user);
            } catch (Tinebase_Exception_NotFound $tenf) {
                echo $tenf->getMessage() . "\n";
                continue;
            }

            if (is_array($pw) && isset($pw[$fullUser->accountLoginName])) {
                // list of user pws
                $newPw = $pw[$fullUser->accountLoginName];
            } else {
                $newPw = $pw ?? Tinebase_User::generateRandomPassword(8);
            }

            if (! $opts->d) {
                Tinebase_User::getInstance()->setPassword($user, $newPw);
                if ($sendmail && ! empty($userdata[1])) {
                    echo "sending mail to " . $userdata[1] . "\n";
                    Tinebase_User::getInstance()->sendPasswordChangeMail($fullUser, $newPw, $userdata[1]);
                }
            } else {
                echo "--DRYRUN-- setting pw for user " . $userdata[0] . "\n";
                if ($sendmail && ! empty($userdata[1])) {
                    echo "--DRYRUN-- sending mail to " . $userdata[1] . "\n";
                } else {
                    echo "no email for: " . $userdata[0] . ";" . $newPw . "\n";
                }
            }

            // @todo create csv export for this
            if ($opts->v) {
                // echo $user->accountEmailAddress . ';' . $newPw . "\n";
                $pwCsv .= $fullUser->accountLoginName . ';' . $newPw . "\n";
            }
        }

        echo "\nNEW PASSWORDS:\n\n";
        echo $pwCsv;
    }

    /**
     * enabled sieve_notification_move for all system accounts
     *
     * usage: method=Admin.enableAutoMoveNotificationsinSystemEmailAccounts [-d] -- [folder=Benachrichtigungen]
     *
     * @param Zend_Console_Getopt $opts
     * @return int
     */
    public function enableAutoMoveNotificationsinSystemEmailAccounts(Zend_Console_Getopt $opts)
    {
        $systemAccounts = Admin_Controller_EmailAccount::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Felamimail_Model_Account::class, [
                ['field' => 'type', 'operator' => 'equals', 'value' => Felamimail_Model_Account::TYPE_SYSTEM]
            ]
        ));
        if (count($systemAccounts) === 0) {
            // nothing to do
            return 0;
        }

        if ($opts->d) {
            echo "--DRY RUN--\n";
        }

        echo "Found " . count($systemAccounts) . " system email accounts to check\n";

        $args = $this->_parseArgs($opts, array());

        $accountsController = Admin_Controller_EmailAccount::getInstance();
        $translate = Tinebase_Translation::getTranslation('Felamimail');
        $folderName = isset($args['folder']) ? $args['folder'] : $translate->_('Notifications');
        $enabled = 0;
        foreach ($systemAccounts as $account) {
            /* @var Felamimail_Model_Account $account */
            if (! $account->sieve_notification_move) {
                if (! $opts->d) {
                    $account->sieve_notification_move = true;
                    $account->sieve_notification_move_folder = $folderName;
                    try {
                        $accountsController->update($account);
                        $enabled++;
                    } catch (Exception $e) {
                        echo "Could not activate sieve_notification_move for account " . $account->name . ". Error: "
                            . $e->getMessage() . "\n";
                    }
                } else {
                    $enabled++;
                }
            }
        }
        echo "Enabled auto-move notification script for " . $enabled . " email accounts\n";
        return 0;
    }

    /**
     * update notificationScript for all system accounts
     *
     * usage: method=Admin.updateNotificationScripts [-d]
     * @param Zend_Console_Getopt $opts
     * @return int
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_Validation
     */
    public function updateNotificationScripts(Zend_Console_Getopt $opts)
    {
        $backend = Admin_Controller_EmailAccount::getInstance();
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Account::class, [
            ['field' => 'sieve_notification_email', 'operator' => 'not', 'value' => NULL],
            ['field' => 'type', 'operator' => 'equals', 'value' => Tinebase_EmailUser_Model_Account::TYPE_SYSTEM]
        ]);
        $mailAccounts = $backend->search($filter);

        if (count($mailAccounts) === 0) {
            return 0;
        }
        if ($opts->d) {
            echo "--DRY RUN--\n";
        }
        echo "Found " . count($mailAccounts) . " system email accounts to update\n";

        $updated = 0;
        foreach ($mailAccounts as $record) {
            if (!$opts->d && Tinebase_EmailUser::sieveBackendSupportsMasterPassword($record)) {
                $raii = Tinebase_EmailUser::prepareAccountForSieveAdminAccess($record->getId());
                Felamimail_Controller_Sieve::getInstance()->setNotificationEmail($record->getId(),
                    $record->sieve_notification_email);
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::'
                    . __LINE__ . 'Sieve script updated from record: ' . $record->getId());
                Tinebase_EmailUser::removeSieveAdminAccess();
                unset($raii);
            }
            $updated++;
        }
        echo "Updated notification script for " . $updated . " email accounts\n";
        return 0;
    }
}
