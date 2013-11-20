<?php
/**
 * Tine 2.0
 * 
 * @package     setup tests
 * @subpackage  test root
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 */

/**
 * helper class
 *
 */
class Setup_TestServer
{
    /**
     * holdes the instance of the singleton
     *
     * @var Setup_TestServer
     */
    private static $instance = NULL;
    
    /**
     * the constructor
     *
     */
    public function __construct() 
    {
    }
    
    /**
     * the singleton pattern
     *
     * @return Setup_TestServer
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Setup_TestServer;
        }
        
        return self::$instance;
    }

    /**
     * init the test framework
     *
     */
    public function initFramework()
    {
        Setup_Core::initFramework();
    }
}
