<?php
/**
 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class HumanResources_Export_Ods_Helper_DailyWTR extends HumanResources_Model_DailyWTReport
{
    /**
     * @param array $_definition
     */
    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);

        unset($_definition[self::VERSION]);
        unset($_definition[self::TABLE]);
        $_definition[self::FIELDS]['clock_times'] = [
            self::TYPE => self::TYPE_VIRTUAL,
        ];
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}