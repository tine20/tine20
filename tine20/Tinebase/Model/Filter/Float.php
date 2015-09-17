<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2015-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Tinebase_Model_Filter_Float
 * 
 * filters one float in one property
 * 
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Filter_Float extends Tinebase_Model_Filter_Int
{
    /**
     * @var integer value type to use in zend db where
     */
    protected $valueType = Zend_Db::FLOAT_TYPE;
}
