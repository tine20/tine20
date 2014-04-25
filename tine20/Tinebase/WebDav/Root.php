<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * root of tree for the WebDAV frontend
 *
 * This class handles the root of the WebDAV tree
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 */
class Tinebase_WebDav_Root extends \Sabre\DAV\SimpleCollection
{
    public function __construct()
    {
        parent::__construct('root', array(
            new \Sabre\DAV\SimpleCollection('principals', array(
                new Tinebase_WebDav_PrincipalCollection(new Tinebase_WebDav_PrincipalBackend(), Tinebase_WebDav_PrincipalBackend::PREFIX_USERS),
                new Tinebase_WebDav_PrincipalCollection(new Tinebase_WebDav_PrincipalBackend(), Tinebase_WebDav_PrincipalBackend::PREFIX_GROUPS)
            ))
        ));
        
        if (Tinebase_Core::getUser()->hasRight('Calendar', Tinebase_Acl_Rights::RUN)) {
            $this->addChild(
                new Calendar_Frontend_WebDAV(\Sabre\CalDAV\Plugin::CALENDAR_ROOT, true)
            );
        }
       
        if (Tinebase_Core::getUser()->hasRight('Tasks', Tinebase_Acl_Rights::RUN)) {
            $this->addChild(
                new Tasks_Frontend_WebDAV('tasks', true)
            );
        }

        if (Tinebase_Core::getUser()->hasRight('Addressbook', Tinebase_Acl_Rights::RUN)) {
            $this->addChild(
                new Addressbook_Frontend_WebDAV(\Sabre\CardDAV\Plugin::ADDRESSBOOK_ROOT, true)
            );
        }
        
        // main entry point for ownCloud 
        if (Tinebase_Core::getUser()->hasRight('Filemanager', Tinebase_Acl_Rights::RUN)) {
            $this->addChild(
                new \Sabre\DAV\SimpleCollection(
                    'remote.php', 
                    array(new Filemanager_Frontend_WebDAV('webdav'))
                )
            );
        }
        
        // webdav tree
        $webDAVCollection = new \Sabre\DAV\SimpleCollection('webdav');
        
        foreach (array('Addressbook', 'Calendar', 'Tasks', 'Felamimail', 'Filemanager') as $application) {
            $applicationClass = $application . '_Frontend_WebDAV';
            if (Tinebase_Core::getUser()->hasRight($application, Tinebase_Acl_Rights::RUN) && class_exists($applicationClass)) {
                $webDAVCollection->addChild(new $applicationClass($application));
            }
        }
        
        $this->addChild($webDAVCollection);
    }
}
