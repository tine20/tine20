<?php
/**
 * Tine 2.0
 * class for initial Tine 2.0 data with LDAP backend
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        move this to Tinebase_Setup_Import ?
 */

/**
 * class to handle data migration
 * 
 * @package     Setup
 */
class Setup_Import_TineInitialLdap extends Setup_Import_TineInitial
{
    /**
     * import main function
     *
     */
    public function import()
    {
        /***************** initial config/preference settings ************************/
        
        $this->_setDefaultGroups('Default', 'Admins');

        $this->_importLDAPAccounts();
        
        $this->initialLoad();
    }    
    
    protected function _importLDAPAccounts()
    {
        // import groups
        Tinebase_Group::getInstance()->importGroups();
        
        // import users
        Tinebase_User::getInstance()->importUsers();
        
        // import group memberships
        Tinebase_Group::getInstance()->importGroupMembers();
    }
}