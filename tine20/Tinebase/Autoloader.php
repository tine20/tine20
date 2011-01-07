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
    public function autoload($class)
    {
        if (($pos = strpos($class, '_')) !== false) {
            $topLevelDirectory = substr($class, 0, $pos);
        } else {
            $topLevelDirectory = $class;
        }
        
        Zend_Loader::loadClass($class, dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . $topLevelDirectory);
    }
    
    public static function initialize(Zend_Loader_Autoloader $_autoloader)
    {
        $_autoloader->unshiftAutoloader(new self(), array('Rediska', 'HTMLPurifier'));
    }
}