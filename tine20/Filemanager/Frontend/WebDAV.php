<?php
/**
 * Tine 2.0
 *
 * @package     Filemanager
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to handle WebDAV tree
 *
 * @package     Filemanager
 * @subpackage  Frontend
 */
class Filemanager_Frontend_WebDAV extends Tinebase_WebDav_Collection_Abstract
{
    protected $_applicationName = 'Filemanager';
    
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
