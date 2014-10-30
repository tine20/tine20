<?php
/**
 * Tine 2.0
 * @package     Admin
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * import users
     *
     * @param Zend_Console_Getopt $_opts
     */
    public function importUser($_opts)
    {
        parent::_import($_opts);
    }
    
    /**
     * overwirte Samba options for users
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
