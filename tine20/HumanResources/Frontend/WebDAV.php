<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to handle WebDAV tree
 *
 * @package     HumanResources
 * @subpackage  Frontend
 */
class HumanResources_Frontend_WebDAV extends Tinebase_Frontend_WebDAV_Abstract
{
    /**
     * application name
     * 
     * @var string
     */
    protected $_applicationName = 'HumanResources';
    
    /**
    * app has personal folders
    *
    * @var string
    */
    protected $_hasPersonalFolders = FALSE;
}
