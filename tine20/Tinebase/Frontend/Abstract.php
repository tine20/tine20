<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Application
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Abstract class for an Tine 2.0 application
 * 
 * @package     Tinebase
 * @subpackage  Application
 */
abstract class Tinebase_Frontend_Abstract implements Tinebase_Frontend_Interface
{
    /**
     * Application name
     *
     * @var string
     */
    protected $_applicationName;

    /**
     * returns function parameter as object, decode Json if needed
     *
     * Prepare function input to be an array. Input maybe already an array or (empty) text.
     * Starting PHP 7 Zend_Json::decode can't handle empty strings.
     *
     * @param  mixed $_dataAsArrayOrJson
     * @return array
     */
    protected function _prepareParameter($_dataAsArrayOrJson)
    {
        return Tinebase_Helper::jsonDecode($_dataAsArrayOrJson);
    }
}
