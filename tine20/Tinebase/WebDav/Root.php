<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * root of tree for the WebDAV frontend
 *
 * This class handles the root of the WebDAV tree
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 */
class Tinebase_WebDav_Root extends Sabre_DAV_SimpleCollection
{
    public function __construct()
    {
        $caldavChildren  = array();
        $carddavChildren = array();
        $webdavChildren  = array();
        
        if(Tinebase_Core::getUser()->hasRight('Calendar', Tinebase_Acl_Rights::RUN)) {
            $caldavChildren[] = new Calendar_Frontend_CalDAV();
        }

        if(Tinebase_Core::getUser()->hasRight('Addressbook', Tinebase_Acl_Rights::RUN)) {
            $carddavChildren[] = new Addressbook_Frontend_CardDAV();
        }
        
        foreach(array('Addressbook', 'Calendar', 'Filemanager') as $application) {
            if(Tinebase_Core::getUser()->hasRight($application, Tinebase_Acl_Rights::RUN)) {
                $applicationClass = $application . '_Frontend_WebDAV';
                $webdavChildren[] = new $applicationClass($application);
            }
        }
        
        parent::__construct('root', array(
            new Sabre_DAV_SimpleCollection(Sabre_CardDAV_Plugin::ADDRESSBOOK_ROOT, $carddavChildren),
            new Sabre_DAV_SimpleCollection(Sabre_CalDAV_Plugin::CALENDAR_ROOT, $caldavChildren),
            new Sabre_DAV_SimpleCollection('webdav', 
                $webdavChildren
            ),
            new Sabre_DAV_SimpleCollection('principals', array(
                new Sabre_DAVACL_PrincipalCollection(new Tinebase_WebDav_PrincipalBackend(), 'principals/users'),
                new Sabre_DAVACL_PrincipalCollection(new Tinebase_WebDav_PrincipalBackend(), 'principals/groups')
            ))
        ));
    }
}
