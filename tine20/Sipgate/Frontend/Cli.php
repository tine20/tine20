<?php
/**
 * Tine 2.0
 * @package     Sipgate
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * cli server for Sipgate
 *
 * This class handles cli requests for the Sipgate
 *
 * @package     Sipgate
 */
class Sipgate_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = 'Sipgate';

    /**
     * import config filename
     *
     * @var string
     */
    protected $_configFilename = 'importconfig.inc.php';

    /**
     * help array with function names and param descriptions
     */
    protected $_help = array(
        'sync_connections' => array(
            'description'   => 'Synchronizes the connections.',
            'params'        => array(
                'line_id'   => 'Line-ID if just this line should be synced',
                'initial'   => 'If set, the last 3 months are synced',
                'shared'    => 'Sync shared accounts. If not set, the accounts created by the calling user are synced.',
                'verbose'   => 'Shows more information.'
            )
        ),
        'take_config' => array(
            'description' => 'Takes the config from config.inc.php and creates an account with the associated lines. An initial connection sync is also done.',
            'params'        => array(
                'shared'    => 'The created account is a shared one. Without this argument, a private account is created.'
                )
        ),
        'sync_contacts' => array(
            'description' => 'Synchronizes unassigned connections with contacts and clear old assignments (if contact is deleted).',
        )
    );
    
    /**
     * Takes the config from config.inc.php and creates an account with the associated lines
     * @param Zend_Console_Getopt $_opts 
     */
    public function take_config($_opts)
    {
        // resolve arguments
        $args = $this->_parseArgs($_opts, array());
        
        $type  = (in_array('shared',  $args['other'])) ? 'shared' : 'private';
        
        if (@isset(Tinebase_Core::getConfig()->sipgate)) {
            $conf = Tinebase_Core::getConfig()->sipgate;
            if(@isset($conf->api_username) && @isset($conf->api_password)) {
                echo 'Validate configuration from config.inc.php...' . PHP_EOL;
                $accountData = array('data' => array(
                    'accounttype' => (@isset($conf->api_url) && ($conf->api_url != 'https://samurai.sipgate.net/RPC2')) ? 'team' : 'plus',
                    'description' => 'Created by update',
                    'username' => $conf->api_username,
                    'password' => $conf->api_password,
                    'type' => $type,
                ));
                if(Sipgate_Controller_Account::getInstance()->validateAccount($accountData)) {
                    echo 'Data from config.inc.php could be validated, creating account...' . PHP_EOL;
                    try {
                        $account = Sipgate_Controller_Account::getInstance()->create(new Sipgate_Model_Account($accountData['data']));
                    } catch(Tinebase_Exception_Duplicate $e) {
                        echo 'An account with this credentials exists already! Did you use this script twice?' . PHP_EOL;
                        die();
                    }
                    
                    if($account) {
                        echo 'Account created. Trying to synchronize the lines..' . PHP_EOL;
                        if(Sipgate_Controller_Line::getInstance()->syncAccount($account->getId())) {
                            $opts = new Zend_Console_Getopt('abp:');
                            $args = array('initial', 'verbose');
                            
                            if($type == 'shared') {
                                $args[] = 'shared';
                            }
                            
                            $opts->setArguments($args);
                            
                            echo 'Lines have been synchronized. Now syncing connections from the last two months, day per day. This could take some time.' . PHP_EOL;
                            $this->sync_connections($opts);
                            echo 'Connections has been synchronized. Now assign users to the line(s) to allow them to use the line(s)' . PHP_EOL;
                            echo 'READY!' . PHP_EOL;
                            
                        } else {
                            echo 'The lines for the account could not be created!' . PHP_EOL;
                        }
                    } else {
                        echo 'The account could not be created!' . PHP_EOL;
                    }
                } else {
                    echo 'The credentials found in config.inc.php could not be validated!' . PHP_EOL;
                }
            } else {
                echo 'No username or password could be found in config.php.inc!' . PHP_EOL;
            }
        } else {
            echo 'No sipgate config could be found in config.php.inc!' . PHP_EOL;
        }
    }

    /**
     * syncs all connections for the current user
     * @param Zend_Console_Getopt $_opts
     */
    public function sync_connections($_opts)
    {

        // resolve arguments
        $args = $this->_parseArgs($_opts, array());
        
        $initial = (in_array('initial', $args['other'])) ? true : false;
        $shared  = (in_array('shared',  $args['other'])) ? true : false;
        $verbose  = (in_array('verbose',  $args['other'])) ? true : false;

        if($initial) {    // sync last 2 months, day per day
            // prepare dates
            $now = new Tinebase_DateTime();
            $now->setTime(0,0,0);

            $from = clone $now;
            $from->subDay(1);

            $to = clone $now;

            $firstDay = clone $now;
            $firstDay->subMonth(2);

            // sync every day
            while(! $firstDay->isLater($from)) {
                Sipgate_Controller_Connection::getInstance()->syncLines($from, $to, $shared, $verbose);
                $from->subDay(1);
                $to->subDay(1);
            }
        } else {
            Sipgate_Controller_Connection::getInstance()->syncLines(NULL, NULL, $shared);
        }
    }
    /**
     * assign contacts to calls without assignment, reassign if contact has changed
     */
    public function sync_contacts()
    {
        $connections = Sipgate_Controller_Connection::getInstance()->syncContacts();
    }
}