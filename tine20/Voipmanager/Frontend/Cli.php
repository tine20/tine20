<?php
/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * cli server for Voipmanager
 *
 * This class handles cli requests for the Voipmanager
 *
 * @package     Voipmanager
 * @subpackage  Frontend
 */
class Voipmanager_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     * 
     * @var string
     */
    protected $_applicationName = 'Voipmanager';
}
