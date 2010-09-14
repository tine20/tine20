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
class Admin_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    /**
     * the application name
     *
     * @var string
     */
    protected $_applicationName = 'Admin';
    
    /**
     * overwrite getJsFilesToInclude from abstract class to add groups js file
     *
     * @return array with js filenames
     */
    public function getJsFilesToInclude() {
        return array(
            'Admin/js/Admin.js',
            'Admin/js/Models.js',
            'Admin/js/Applications.js',
            'Admin/js/user/Users.js',
            'Admin/js/user/GridPanel.js',
            'Admin/js/user/EditDialog.js',
            'Admin/js/Groups.js',
            'Admin/js/AccessLog.js',
            'Admin/js/SambaMachineModel.js',
            'Admin/js/SambaMachineGrid.js',
            'Admin/js/SambaMachineEditDialog.js',
            'Admin/js/Tags.js',
            'Admin/js/Roles.js',
            'Admin/js/RoleEditDialog.js',
        );
    }
}
