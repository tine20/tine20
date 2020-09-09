<?php
/**
 * Tine 2.0
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2016-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * cli server for Felamimail
 *
 * This class handles cli requests for the Felamimail
 *
 * @package     Felamimail
 */
class Felamimail_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = 'Felamimail';

    /**
     * help array with function names and param descriptions
     */
    protected $_help = array(
        'truncatecache' => array(
            'description'   => 'Truncate email cache',
            'params'        => array(
            )
        ),
    );
    
    /**
     * truncate email cache
     * 
     * @param Zend_Console_Getopt $opts
     * @return integer
     */
    public function truncatecache($opts)
    {
        $this->_checkAdminRight();
        Felamimail_Controller::getInstance()->truncateEmailCache();
        return 0;
    }
}
