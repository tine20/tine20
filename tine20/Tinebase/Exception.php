<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * Addressbook exception
 * 
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception extends Exception
{
    /**
     * the name of the application, this exception belongs to
     * 
     * @var string
     */
    protected $_appName = NULL;
    
    /**
     * the constructor
     * 
     * @param message[optional]
     * @param code[optional]
     * @param previous[optional]
     */
    public function __construct($message = null, $code = null, $previous = null)
    {
        if (! $this->_appName) {
            $c = explode('_', get_class($this));
            $this->_appName = $c[0];
        }
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * returns the name of the application, this exception belongs to
     * 
     * @return string
     */
    public function getAppName()
    {
        return $this->_appName;
    }
}
