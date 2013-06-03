<?php
/**
 * Tine 2.0
 *
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Phone demo data
 *
 * @package Setup
 */
class Phone_Setup_DemoData extends Tinebase_Setup_DemoData_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var Phone_Setup_DemoData
     */
    private static $_instance = NULL;

    /**
     * the application name to work on
     * 
     * @var string
     */
    protected $_appName         = 'Phone';
    
    /**
     * models to work on
     * 
     * @var array
     */
    protected $_models = array();
    
}
