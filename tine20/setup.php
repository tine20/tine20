<?php
/**
 * Tine 2.0 - this file starts the setup process
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$ 
 *
 */
require_once 'Zend/Loader.php';

Zend_Loader::registerAutoload();

$setup = new Setup_Tables();

$fileName = 'Tinebase/setup.xml';
if(file_exists($fileName)) {
    echo "Processing tables definitions from <b>$fileName</b><br>";
    $setup->parseFile($fileName);
}


foreach ( new DirectoryIterator('./') as $item ) {
	if($item->isDir() && $item->getFileName() != 'Tinebase') {
		$fileName = $item->getFileName() . '/setup.xml';
		if(file_exists($fileName)) {
			echo "Processing tables definitions from <b>$fileName</b><br>";
			$setup->parseFile($fileName);
		}
	}
}

# either import data from eGroupWare 1.4
#$import = new Setup_Import_Egw14();
#$import->import();

# or initialize the database ourself

# add the admin group
$adminGroup = new Tinebase_Group_Model_Group(array(
    'name'          => 'Adminstrators',
    'description'   => 'Group of administrative accounts'
));
Tinebase_Group_Sql::getInstance()->addGroup($adminGroup);

# add the user group
$userGroup = new Tinebase_Group_Model_Group(array(
    'name'          => 'Users',
    'description'   => 'Group of user accounts'
));
Tinebase_Group_Sql::getInstance()->addGroup($userGroup);

# add the admin account
$account = new Tinebase_Account_Model_FullAccount(array(
    'accountLoginName'      => 'tine20admin',
    'accountStatus'         => 'enabled',
    'accountPrimaryGroup'   => $userGroup->id,
    'accountLastName'       => 'Account',
    'accountFirstName'      => 'Tine 2.0 Admin'
));
Tinebase_Account_Sql::getInstance()->addAccount($account);

# set the password for the tine20admin account
Tinebase_Auth::getInstance()->setPassword('tine20admin', 'lars', 'lars');

Tinebase_Group::getInstance()->addGroupMember($adminGroup, $account);
Tinebase_Group::getInstance()->addGroupMember($userGroup, $account);

foreach(Tinebase_Application::getInstance()->getApplications() as $application) {
    $right = new Tinebase_Acl_Model_Right(array(
        'application_id'    => $application,
        'account_id'        => $userGroup,
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
