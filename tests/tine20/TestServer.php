<?php
/**
 * Tine 2.0
 * 
 * @package     tests
 * @subpackage  test root
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 * 
 * @todo        extend Tinebase_Server_Abstract to init framework
 */

/**
 * helper class
 *
 */
class TestServer extends Tinebase_Server_Abstract
{
    /**
     * holdes the instance of the singleton
     *
     * @var TestServer
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
     * @return TestServer
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new TestServer;
        }
        
        return self::$instance;
    }

    /**
     * init the test framework
     *
     */
    public function initFramework()
    {
        $this->_initFramework();
    }
    
    /**
     * don't use that
     *
     */
    public function handle()
    {
        
    }
}
