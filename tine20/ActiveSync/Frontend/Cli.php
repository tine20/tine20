<?php
/**
 * Tine 2.0
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2014-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Cli frontend for ActiveSync
 *
 * This class handles cli requests for the ActiveSync
 *
 * @package     ActiveSync
 */
class ActiveSync_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     * 
     * @var string
     */
    protected $_applicationName = 'ActiveSync';
    
    /**
     * reset sync
     * 
     * @param Zend_Console_Getopt $opts
     */
    public function resetSync(Zend_Console_Getopt $opts)
    {
        $this->_addOutputLogWriter(6);
        
        $args = $this->_parseArgs($opts, array('user'));
        
        if ($args['user'] != Tinebase_Core::getUser()->accountLoginName && ! $this->_checkAdminRight(false)) {
            return 1;
        }
        
        $possibleClasses = array('Calendar', 'Contacts', 'Tasks', 'Email');
        $classesToReset = (isset($args['collection']) && in_array($args['collection'], $possibleClasses))
            ? array($args['collection'])
            : $possibleClasses;
        
        $result = ActiveSync_Controller::getInstance()->resetSyncForUser($args['user'], $classesToReset);
        
        return ($result) ? 0 : 1;
    }


    /**
     *
     * remoteDeviceReset
     * set remoteWipe flag for device (id)
     * php tine20.php --method=ActiveSync.remoteDeviceReset id=12345
     * @param Zend_Console_Getopt $opts
     * @return int
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function remoteDeviceReset(Zend_Console_Getopt $opts)
    {
        $args = $this->_parseArgs($opts, ['id']);
        if (! $args['id'] || ! $this->_checkAdminRight(false)) {
            return 1;
        }

        ActiveSync_Controller_SyncDevices::getInstance()->remoteResetDevices([ActiveSync_Controller_Device::getInstance()->get($args['id'])]);
        return  0;
    }
}
