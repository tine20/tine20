<?php
/**
 * class to hold Tree Node Link data
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c)2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 *
 * @package     Tinebase
 * @subpackage  Model
 */
class Tinebase_Model_Tree_Node_Link extends Tinebase_Record_NewAbstract
{
    const FLD_RECORD_ID = 'record_id';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::FIELDS    => [
            self::FLD_RECORD_ID     => [
                self::TYPE              => self::TYPE_STRING,
            ],
        ],
    ];
}
