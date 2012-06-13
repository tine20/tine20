<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to handle WebDAV tree
 *
 * @package     Felamimail
 * @subpackage  Frontend
 */
class Felamimail_Frontend_WebDAV extends Tinebase_Frontend_WebDAV_Abstract
{
    /**
     * application name
     * 
     * @var string
     */
    protected $_applicationName = 'Felamimail';
    
    /**
    * app has personal folders
    *
    * @var string
    */
    protected $_hasPersonalFolders = FALSE;
}
