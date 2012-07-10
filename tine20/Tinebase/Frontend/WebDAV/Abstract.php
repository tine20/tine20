<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to handle WebDAV tree
 *
 * @package     Tinebase
 * @subpackage  Frontend
 */
abstract class Tinebase_Frontend_WebDAV_Abstract extends Tinebase_WebDav_Collection_Abstract
{
    /**
    * application name
    *
    * @var string
    */
    protected $_applicationName = 'Tinebase';
    
    /**
     * Creates a new subdirectory
     *
     * @param  string  $name  name of the new subdirectory
     * @throws Sabre_DAV_Exception_Forbidden
     * @return Tinebase_Model_Container
     */
    public function createDirectory($name) 
    {
        $container = parent::createDirectory($name);
                
        $path = '/' . $this->_application->getId() . '/folders/' . $container->type . '/';
        
        if ($container->type == Tinebase_Model_Container::TYPE_PERSONAL) {
            $path .= Tinebase_Core::getUser()->accountId . '/';
        }
        
        $path .= $container->getId();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' create directory: ' . $path);
        
        Tinebase_FileSystem::getInstance()->mkdir($path);
    }
}
