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
        $webdavApps = array();
        
        foreach(array('Addressbook', 'Calendar'/*, 'Filemanager'*/) as $application) {
            if(Tinebase_Core::getUser()->hasRight($application, Tinebase_Acl_Rights::RUN)) {
                $applicationClass = $application . '_Frontend_WebDAV';
                $webdavApps[] = new $applicationClass($application);
            }
        }
        
        parent::__construct('root', array(
            new Sabre_DAV_SimpleCollection(Sabre_CardDAV_Plugin::ADDRESSBOOK_ROOT, array(
                new Addressbook_Frontend_CardDAV()
            )),
            new Sabre_DAV_SimpleCollection(Sabre_CalDAV_Plugin::CALENDAR_ROOT, array(
                new Calendar_Frontend_CalDAV()
            )),
            new Sabre_DAV_SimpleCollection('webdav', 
                $webdavApps
            ),
            new Sabre_DAV_SimpleCollection('principals', array(
                new Sabre_DAVACL_PrincipalCollection(new Tinebase_WebDav_PrincipalBackend(), 'principals/users'),
                new Sabre_DAVACL_PrincipalCollection(new Tinebase_WebDav_PrincipalBackend(), 'principals/groups')
            )),
            
        ));
    }
}
