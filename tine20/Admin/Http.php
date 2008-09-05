<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * This class handles all Http requests for the admin application
 *
 * @package     Admin
 */
class Admin_Http extends Tinebase_Application_Http_Abstract
{
    /**
     * the application name
     *
     * @var string
     */
    protected $_appname = 'Admin';
    
    /**
     * display edit tag dialog
     *
     * @param   integer     tag id
     * 
     */
    public function editTag($tagId)
    {
        if (empty($tagId)) {
            $encodedTag = Zend_Json::encode(array());
        } else {
            $tag = Admin_Controller::getInstance()->getTag($tagId);

            $tag->rights = $tag->rights->toArray();
            
            $tag->rights = Admin_Json::resolveAccountName($tag->rights, true);
            $encodedTag = Zend_Json::encode($tag->toArray());
        }

        $view = new Zend_View();
        $view->setScriptPath('Tinebase/views');
        
        $appList = Zend_Json::encode(Tinebase_Application::getInstance()->getApplications('%')->toArray());
        $view->jsExecute = "Tine.Admin.Tags.EditDialog.display($encodedTag, $appList);";

        $view->title="edit tag";

        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }
    
    /**
     * overwrite getJsFilesToInclude from abstract class to add groups js file
     *
     * @return array with js filenames
     */
    public function getJsFilesToInclude() {
        return array(
            'Admin/js/Admin.js',
            'Admin/js/Users.js',
            'Admin/js/Groups.js',
            'Admin/js/Tags.js',
            'Admin/js/Roles.js'
        );
    }
}