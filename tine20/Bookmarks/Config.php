<?php
/**
 * Tine 2.0
 *
 * @package     Bookmarks
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2017-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Bookmarks config class
 *
 * @package     Bookmarks
 * @subpackage  Config
 *
 */
class Bookmarks_Config extends Tinebase_Config_Abstract
{
    use Tinebase_Controller_SingletonTrait;
    const APP_NAME = 'Bookmarks';
    protected $_appName = self::APP_NAME;
    
    const OPEN_BOOKMARK_HOOKS = 'openBookmarkHooks';
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::OPEN_BOOKMARK_HOOKS => [
            //_('Live time of token subscriptions')
            self::LABEL                 => 'Open Bookmark Hooks',
            //_('Live time of token subscriptions')
            self::DESCRIPTION           => 'Hooks to be executed when bookmark is opened',
            self::TYPE                  => self::TYPE_ARRAY,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => false,
        ],
    );

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::getProperties()
     */
    public static function getProperties()
    {
        return self::$_properties;
    }
}
