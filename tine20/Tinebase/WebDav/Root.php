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
    /**
     * Tinebase_WebDav_Root constructor.
     */
    public function __construct()
    {
        $applications = is_object(Tinebase_Core::getUser())
            ? Tinebase_Core::getUser()->getApplications()
            : new Tinebase_Record_RecordSet('Tinebase_Model_Application');
        
        parent::__construct('root', array(
            new \Sabre\DAV\SimpleCollection('principals', array(
                new Tinebase_WebDav_PrincipalCollection(new Tinebase_WebDav_PrincipalBackend(), Tinebase_WebDav_PrincipalBackend::PREFIX_USERS),
                new Tinebase_WebDav_PrincipalCollection(new Tinebase_WebDav_PrincipalBackend(), Tinebase_WebDav_PrincipalBackend::PREFIX_GROUPS),
                new Tinebase_WebDav_PrincipalCollection(new Tinebase_WebDav_PrincipalBackend(), Tinebase_WebDav_PrincipalBackend::PREFIX_INTELLIGROUPS)
            ))
        ));
        
        if ($applications->find('name', 'Calendar')) {
            $this->addChild(
                new Calendar_Frontend_WebDAV(\Sabre\CalDAV\Plugin::CALENDAR_ROOT, array(
                    'useIdAsName' => true,
                ))
            );
        }
       
        if ($applications->find('name', 'Tasks')) {
            $this->addChild(
                new Tasks_Frontend_WebDAV('tasks', array(
                    'useIdAsName' => true,
                ))
            );
        }

        if ($applications->find('name', 'Addressbook')) {
            $this->addChild(
                new Addressbook_Frontend_WebDAV(\Sabre\CardDAV\Plugin::ADDRESSBOOK_ROOT, array(
                    'useIdAsName' => true,
                ))
            );
        }
        
        // main entry point for ownCloud 
        if ($applications->find('name', 'Filemanager')) {
            $this->addChild(
                new \Sabre\DAV\SimpleCollection('remote.php', [
                    // for owncloud v2
                    new Filemanager_Frontend_WebDAV('webdav'),
                    // for owncloud v3 , they have the same "path" => 'webdav',add the path rewrite as option there.
                    new \Sabre\DAV\SimpleCollection('dav', [
                        new \Sabre\DAV\SimpleCollection('files', [
                            new Filemanager_Frontend_WebDAV('webdav', [
                                Tinebase_WebDav_Collection_AbstractContainerTree::NAME_NOT_IN_PATH => Tinebase_Core::getUser()->accountLoginName,
                            ]),
                        ])
                    ]),
                ])
            );
        }
        
        // webdav tree
        $webDAVCollection = new \Sabre\DAV\SimpleCollection('webdav');
        
        foreach ($applications as $application) {
            $applicationClass = $application->name . '_Frontend_WebDAV';
            if (@class_exists($applicationClass)) {
                $webDAVCollection->addChild(new $applicationClass($application->name));
            }
        }
        
        $this->addChild($webDAVCollection);
    }
}
