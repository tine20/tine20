<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * Tine 2.0 autoloader for classes located in ../library/Packagename
 *
 * @package     Tinebase
 */
class Tinebase_Autoloader implements Zend_Loader_Autoloader_Interface
{
    /**
     * (non-PHPdoc)
     * @see Zend_Loader_Autoloader_Interface::autoload()
     */
    public function autoload($class)
    {
        if (class_exists($class, false) || interface_exists($class, false)) {
            return;
        }
        
        $topLevelDirectory = (($pos = strpos($class, '_')) !== false) ? substr($class, 0, $pos) : $class;
        
        $file = $topLevelDirectory . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
        
        /**
         * Security check
         */
        if (preg_match('/[^a-z0-9\\/\\\\_.:-]/i', $file)) {
            require_once 'Zend/Exception.php';
            throw new Zend_Exception('Security check: Illegal character in filename');
        }
        
        $fullPath = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . $file;
        
        include_once $fullPath;
        
        if (!class_exists($class, false) && !interface_exists($class, false)) {
            throw new Zend_Exception("File \"$fullPath\" does not exist or class \"$class\" was not found in the file");
        }
    }
    
    /**
     * initialize Tine 2.0 autoloader for different prefixes
     * 
     * @param Zend_Loader_Autoloader $_autoloader
     */
    public static function initialize(Zend_Loader_Autoloader $_autoloader)
    {
        $_autoloader->unshiftAutoloader(new self(), array('Rediska', 'HTMLPurifier'));
    }
}