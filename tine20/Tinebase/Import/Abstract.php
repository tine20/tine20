<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * abstract Tinebase Import
 * 
 * @package Tinebase
 * @subpackage  Import
 * 
 */
abstract class Tinebase_Import_Abstract implements Tinebase_Import_Interface
{
    /**
     * possible configs with default values
     * 
     * @var array
     */
    protected $_config = array();
    
    /**
     * constructs a new importer from given config
     * 
     * @param array $_config
     */
    final public function __construct(array $_config = array())
    {
        foreach($_config as $key => $cfg) {
            if (array_key_exists($key, $this->_config)) {
                $this->_config[$key] = $cfg;
            }
        }
        
        $this->_init();
    }
    
    /**
     * import given filename
     * 
     * @param string $_filename
     * @return @see{Tinebase_Import_Interface::import}
     */
    public function importFile($_filename)
    {
        if (! file_exists($_filename)) {
            throw new Tinebase_Exception_NotFound("File $_filename not found.");
        }
        $resource = fopen($_filename, 'r');
        
        $retVal = $this->import($resource);
        fclose($resource);
        
        return $retVal;
    }
    
    /**
     * import from given data
     * 
     * @param string $_data
     * @return @see{Tinebase_Import_Interface::import}
     */
    public function importData($_data)
    {
        $resource = fopen('php://memory', 'w+');
        fwrite($resource, $_data);
        rewind($resource);
        
        $retVal = $this->import($resource);
        fclose($resource);
        
        return $retVal;
    }
    
    /**
     * template fn for init, cause constructor cannot be overwritten -> static late binding ... :-(
     */
    protected function _init()
    {
        
    }
}