<?php
/**
 * Tine 2.0
 * @package     Bookmarks
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * cli server for Bookmarks
 *
 * This class handles cli requests for the Bookmarks
 *
 * @package     Bookmarks
 * @subpackage  Frontend
 */
class Bookmarks_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = 'Bookmarks';


    /**
     * import bookmarks
     *
     * @param Zend_Console_Getopt $_opts
     */
    public function import($_opts)
    {
        parent::_import($_opts);
    }
}
