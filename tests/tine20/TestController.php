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
class TestController extends Tinebase_Controller 
{
    /**
     * holdes the instance of the singleton
     *
     * @var TestController
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
     * @return Tinebase_Controller
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new TestController;
        }
        
        parent::getInstance();
        return self::$instance;
    }

    public function initFramework()
    {
        $this->_initFramework();
    }
}
