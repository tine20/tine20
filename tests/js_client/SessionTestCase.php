<?php
/**
 * Tine 2.0
 * 
 * @package     tests
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * test case for shared Selenium RC Session
 *
 */
class SessionTestCase extends PHPUnit_Extensions_SeleniumTestCase
{
    /**
     * @var bool no autostopping per default
     */
    public $autoStop = FALSE;
    
    protected static $driver = NULL;
    
    /*
    public function setDriver($driver) {
        if (! $driver instanceof PHPUnit_Extensions_SeleniumTestCase_Driver) {
            throw new RuntimeException(
              'driver must be of instance PHPUnit_Extensions_SeleniumTestCase_Driver.'
            );
        }
        $this->drivers[0] = $driver;
        $this->drivers[0]->setTestCase($this);
    }
    */
    
    /**
     * @param  array $browser
     * @return PHPUnit_Extensions_SeleniumTestCase_Driver
     * @since  Method available since Release 3.3.0
     */
    protected function getDriver(array $browser)
    {
        if (!empty(self::$browsers)) {
            //...
        } else {
            if (self::driverInitialized()) {
                $this->drivers[0] = self::$driver;
                self::$driver->setTestCase($this);
                
            } else {
                self::$driver = parent::getDriver($browser);
            }
            
            return self::$driver;
        }
    }
    
    public static function destroySession()
    {
        if (self::driverInitialized()) {
            self::$driver->stop();
            
            self::$driver = NULL;
        }
    }
    
    public static function driverInitialized()
    {
        return self::$driver instanceof PHPUnit_Extensions_SeleniumTestCase_Driver;
    }
}