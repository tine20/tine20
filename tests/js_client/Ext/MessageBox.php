<?php
/**
 * PHP ExtJS Selenium Proxy
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * proxy for Ext.MessageBox
 */
class Ext_MessageBox
{
    const BUTTON_OK     = 'OK';
    const BUTTON_YES    = 'YES';
    const BUTTON_NO     = 'NO';
    const BUTTON_CANCEL = 'CANCEL';
    
    protected $_selenium = NULL;
    
    /**
     * @var array map button names to extjs internal button array indicees
     */
    protected $_buttonIndexMap = array(
        self::BUTTON_OK     => 0,
        self::BUTTON_YES    => 1,
        self::BUTTON_NO     => 2,
        self::BUTTON_CANCEL => 3,
    );
    
    /**
     * holods instances of self
     * 
     * @var array
     */
    private static $instances = array();
    
    /**
     * construct the message box proxy
     * 
     * @param $_selenium
     * @return void
     */
    private function __construct($_selenium)
    {
        $this->_selenium = $_selenium;
    }
    
    /**
     * get message box proxy
     * 
     * @param $_selenium
     * @return Ext_MessageBox
     */
    public static function getInstance($_selenium)
    {
        $idx = array_search($_selenium, self::$instances, TRUE);
        if ($idx !== FALSE) {
            return self::$instances[$idx];
        } else {
            self::$instances[] = $instance = new Ext_MessageBox($_selenium);
            return $instance;
        }
    }
    
    /**
     * press given button
     * 
     * @param  string $_which
     * @return void
     */
    public function pressButton($_which)
    {
        $idx = $this->_buttonIndexMap[$_which];
        $buttonId = $this->_selenium->getEval("window.Ext.MessageBox.getDialog().buttons[$idx].id");
        
        $this->_selenium->waitForVisible($buttonId);
        $this->_selenium->click($buttonId);
    }
    
    /**
     * press cancel button
     * 
     * @return void
     */
    public function pressCancel()
    {
        $this->pressButton(self::BUTTON_CANCEL);
    }
    
    /**
     * press no button
     * 
     * @return void
     */
    public function pressNo()
    {
        $this->pressButton(self::BUTTON_NO);
    }
    
    /**
     * press OK button
     * 
     * @return void
     */
    public function pressOK()
    {
        $this->pressButton(self::BUTTON_OK);
    }
    
    /**
     * press yes button
     * 
     * @return void
     */
    public function pressYes()
    {
        $this->pressButton(self::BUTTON_YES);
    }
}