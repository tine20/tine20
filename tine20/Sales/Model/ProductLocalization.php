<?php declare(strict_types=1);

/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Product
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold localized product data
 *
 * @package     Sales
 * @subpackage  Product
 */
class Sales_Model_ProductLocalization extends Tinebase_Record_PropertyLocalization
{
    public const MODEL_NAME_PART = 'ProductLocalization';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
