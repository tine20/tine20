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
     * get the filter group object
     *
     * @param $filterModel
     * @return Tinebase_Model_Filter_FilterGroup
     */
    protected function _getFilterObject($filterModel)
    {
        if (! class_exists($filterModel)) {
            $configuredModel = preg_replace('/Filter$/', '', $filterModel);

            // TODO check if model class exists?
            //if (class_exists($configuredModel))

            // use generic filter model
            $filter = new Tinebase_Model_Filter_FilterGroup();
            $filter->setConfiguredModel($configuredModel);
        } else {
            $filter = new $filterModel();
        }

        return $filter;
    }

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
