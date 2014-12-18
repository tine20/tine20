<?php
/**
 * Tine 2.0
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
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
        
        if ($args['user'] != Tinebase_Core::getUser()->accountLoginName && ! $this->_checkAdminRight()) {
            return 1;
        }
        
        $possibleClasses = array('Calendar', 'Contacts', 'Tasks', 'Email');
        $classesToReset = (isset($args['collection']) && in_array($args['collection'], $possibleClasses))
            ? array($args['collection'])
            : $possibleClasses;
        
        $result = ActiveSync_Controller::getInstance()->resetSyncForUser($args['user'], $classesToReset);
        
        return ($result) ? 0 : 1;
    }
}
