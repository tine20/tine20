<?php declare(strict_types=1);
/**
 * @package     SSO
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * SSO config class
 * 
 * @package     SSO
 * @subpackage  Config
 */
class SSO_Config extends Tinebase_Config_Abstract
{
    public const APP_NAME = 'SSO';

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = [];

    static function getProperties()
    {
        return self::$_properties;
    }
}
