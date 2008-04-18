<?php
/**
 * Tine 2.0 - this file starts the setup process
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
define ( 'DO_TABLE_SETUP', TRUE );
define ( 'IMPORT_EGW_14', FALSE );
define ( 'IMPORT_TINE_REV_949', FALSE );

require_once 'Zend/Loader.php';

Zend_Loader::registerAutoload();

$check = new Setup_ExtCheck('Setup/essentials.xml');
$output = $check->getOutput();

echo $output;
if (strpos($output, "FAILURE"))
{
	echo "Unsufficent server system.";
	exit;
}

$setup = new Setup_Tables();
    
if ( DO_TABLE_SETUP ) {
    $fileName = 'Tinebase/Setup/setup.xml';
    if(file_exists($fileName)) {
        echo "Processing tables definitions from <b>$fileName</b><br>";
        $setup->parseFile($fileName);
    }
    
    foreach ( new DirectoryIterator('./') as $item ) {
    	if($item->isDir() && $item->getFileName() != 'Tinebase') {
    		$fileName = $item->getFileName() . '/Setup/setup.xml';
    		if(file_exists($fileName)) {
    			echo "Processing tables definitions from <b>$fileName</b><br>";
    			$setup->parseFile($fileName);
    		}
    	}
    }
}


# either import data from eGroupWare 1.4 or tine 2.0 revision 949
if ( IMPORT_EGW_14 ) {
    $import = new Setup_Import_Egw14();
} elseif ( IMPORT_TINE_REV_949 ) {
    $import = new Setup_Import_TineRev949();
}
if ( isset($import) ) {
    $import->import();
    exit();
}

# or initialize the database ourself
//*
# add the admin group
$groupsBackend = Tinebase_Group_Factory::getBackend(Tinebase_Group_Factory::SQL);

$adminGroup = new Tinebase_Group_Model_Group(array(
    'name'          => 'Administrators',
    'description'   => 'Group of administrative accounts'
));
$adminGroup = $groupsBackend->addGroup($adminGroup);

# add the user group
$userGroup = new Tinebase_Group_Model_Group(array(
    'name'          => 'Users',
    'description'   => 'Group of user accounts'
));
$userGroup = $groupsBackend->addGroup($userGroup);

# add the admin account
$accountsBackend = Tinebase_Account_Factory::getBackend(Tinebase_Account_Factory::SQL);

$account = new Tinebase_Account_Model_FullAccount(array(
    'accountLoginName'      => 'tine20admin',
    'accountStatus'         => 'enabled',
    'accountPrimaryGroup'   => $userGroup->getId(),
    'accountLastName'       => 'Account',
    'accountDisplayName'    => 'Tine 2.0 Admin Account',
    'accountFirstName'      => 'Tine 2.0 Admin'
));

$accountsBackend->addAccount($account);

Zend_Registry::set('currentAccount', $account);

# set the password for the tine20admin account
Tinebase_Auth::getInstance()->setPassword('tine20admin', 'lars', 'lars');

# add the admin account to all groups
Tinebase_Group::getInstance()->addGroupMember($adminGroup, $account);
Tinebase_Group::getInstance()->addGroupMember($userGroup, $account);

# enable the applications for the user group
# give admin rights to the admin group for all applications
foreach(Tinebase_Application::getInstance()->getApplications() as $application) {
    if(strtolower($application->name) == 'admin') {
        $group = $adminGroup;
    } else {
        $group = $userGroup;
    }
    
    $right = new Tinebase_Acl_Model_Right(array(
        'application_id'    => $application,
        'account_id'        => $group,
        'account_type'      => 'group',
        'right'             => Tinebase_Acl_Rights::RUN
    ));
    Tinebase_Acl_Rights::getInstance()->addRight($right);

    $right = new Tinebase_Acl_Model_Right(array(
        'application_id'    => $application,
        'account_id'        => $adminGroup,
        'account_type'      => 'group',
        'right'             => Tinebase_Acl_Rights::ADMIN
    ));
    Tinebase_Acl_Rights::getInstance()->addRight($right);
}

# give Users group read rights to the internal addressbook
# give Adminstrators group read/write rights to the internal addressbook
$internalAddressbook = Tinebase_Container::getInstance()->getContainerByName('Addressbook', 'Internal Contacts', Tinebase_Container::TYPE_INTERNAL);
Tinebase_Container::getInstance()->addGrants($internalAddressbook, 'group', $userGroup, array(
    Tinebase_Container::GRANT_READ
), TRUE);
Tinebase_Container::getInstance()->addGrants($internalAddressbook, 'group', $adminGroup, array(
    Tinebase_Container::GRANT_READ,
    Tinebase_Container::GRANT_ADD,
    Tinebase_Container::GRANT_EDIT,
    Tinebase_Container::GRANT_DELETE,
    Tinebase_Container::GRANT_ADMIN
), TRUE);

//*/