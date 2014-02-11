<?php

use Sabre\DAV;
use Sabre\DAVACL;
use Sabre\CardDAV;
use Sabre\CalDAV;

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * root of tree for the WebDAV frontend
 *
 * This class handles the root of the WebDAV tree
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 * 
 * @todo this should look for webdav frontend in installed apps
 */
class Tinebase_WebDav_Root extends DAV\SimpleCollection
{
    public function __construct()
    {
        $caldavCalendarChildren = array();
        $caldavTasksChildren    = array();
        $carddavChildren        = array();
        $webdavChildren         = array();
        $ownCloudChildren       = array();
        
        if(Tinebase_Core::getUser()->hasRight('Calendar', Tinebase_Acl_Rights::RUN)) {
            $caldavCalendarChildren[] = new Calendar_Frontend_CalDAV();
        }
       
        if(Tinebase_Core::getUser()->hasRight('Tasks', Tinebase_Acl_Rights::RUN)) {
            $caldavTasksChildren[]    = new Tasks_Frontend_CalDAV();
        }

        if(Tinebase_Core::getUser()->hasRight('Addressbook', Tinebase_Acl_Rights::RUN)) {
            $carddavChildren[] = new Addressbook_Frontend_CardDAV();
        }
        
        if(Tinebase_Core::getUser()->hasRight('Filemanager', Tinebase_Acl_Rights::RUN)) {
            $ownCloudChildren[] = new Filemanager_Frontend_WebDAV('webdav');
        }
        
        foreach (array('Addressbook', 'Calendar', 'Felamimail', 'Filemanager', 'Tasks') as $application) {
            $applicationClass = $application . '_Frontend_WebDAV';
            if (Tinebase_Core::getUser()->hasRight($application, Tinebase_Acl_Rights::RUN) && class_exists($applicationClass)) {
                $webdavChildren[] = new $applicationClass($application);
            }
        }
        
        parent::__construct('root', array(
            new DAV\SimpleCollection(CardDAV\Plugin::ADDRESSBOOK_ROOT, $carddavChildren),
            new DAV\SimpleCollection(CalDAV\Plugin::CALENDAR_ROOT,     $caldavCalendarChildren),
            new DAV\SimpleCollection('tasks',                          $caldavTasksChildren),
            new DAV\SimpleCollection('webdav',                         $webdavChildren),
            new DAV\SimpleCollection('principals', array(
                new Tinebase_WebDav_PrincipalCollection(new Tinebase_WebDav_PrincipalBackend(), Tinebase_WebDav_PrincipalBackend::PREFIX_USERS),
                new Tinebase_WebDav_PrincipalCollection(new Tinebase_WebDav_PrincipalBackend(), Tinebase_WebDav_PrincipalBackend::PREFIX_GROUPS)
            )),
            // main entry point for ownCloud 
            new DAV\SimpleCollection('remote.php',                     $ownCloudChildren)
        ));
    }
}
