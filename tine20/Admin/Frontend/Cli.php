<?php
/**
 * Tine 2.0
 * @package     Admin
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2018 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * @param $_opts
     */
    public function createSystemGroupsForAddressbookLists($_opts)
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
                    $user->accountLoginName = Tinebase_User::getInstance()->generateUserName($user);

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
     * repair groups
     * 
     * * add missing lists
     * * checks if list container has been deleted (and hides groups if that's the case)
     * 
     * @see 0010401: add repair script for groups without list_ids
     */
    public function repairGroups()
    {
        echo 'use Tinebase.sanitizeGroupListSync instead';
        /*
        $count = 0;
        $be = new Tinebase_Group_Sql();
        $listBackend = new Addressbook_Backend_List();
        
        $groups = $be->getGroups();
        
        foreach ($groups as $group) {
            if ($group->list_id == null) {
                $list = Addressbook_Controller_List::getInstance()->createByGroup($group);
                $group->list_id = $list->getId();
                $group->visibility = Tinebase_Model_Group::VISIBILITY_DISPLAYED;
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . ' Add missing list for group ' . $group->name);
                $be->updateGroupInSqlBackend($group);
                $count++;
            } else if ($group->visibility === Tinebase_Model_Group::VISIBILITY_DISPLAYED) {
                try {
                    $list = $listBackend->get($group->list_id);
                    $listContainer = Tinebase_Container::getInstance()->get($list->container_id);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                            . ' Hide group '  . $group->name . ' without list / list container.');
                    $group->visibility = Tinebase_Model_Group::VISIBILITY_HIDDEN;
                    $be->updateGroupInSqlBackend($group);
                    $count++;
                }
            }
        }
        echo $count . " groups repaired!\n";*/
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
}
