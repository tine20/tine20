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
define ( 'IMPORT_INITIALDATA', TRUE );
define ( 'IMPORT_EGW_14', FALSE );
define ( 'IMPORT_EGW_14_ADDRESSBOOK', FALSE );
define ( 'IMPORT_TINE_REV_949', FALSE );

/**
 * initialize autoloader
 */
require_once 'Zend/Loader.php';

Zend_Loader::registerAutoload();

/**
 * validate environemnt
 */
$check = new Setup_ExtCheck('Setup/essentials.xml');

$output = $check->getOutput();
echo $output;

if (strpos($output, "FAILURE")) {
    die("Unsufficent server system.");
}

/**
 * load central configuration once and put it in the registry
 */
try {
    Zend_Registry::set('configFile', new Zend_Config_Ini($_SERVER['DOCUMENT_ROOT'] . '/../config.ini'));
} catch (Zend_Config_Exception $e) {
    die ('central configuration file ' . $_SERVER['DOCUMENT_ROOT'] . '/../config.ini not found');
}

/**
 * start setup
 */
$setup = new Setup_Controller();

/**
 * update applications
 */
$setup->updateInstalledApplications();

/**
 * build empty Database 
 */ 
$setup->installNewApplications('Tinebase/Setup/setup.xml', '/Setup/setup.xml');

/**
 * fill with default or present values 
 */
if($setup->initialLoadRequired()) {

    # either import data from eGroupWare 1.4 or tine 2.0 revision 949    
    if ( IMPORT_INITIALDATA === TRUE ) {
        $import = new Setup_Import_TineInitial();
    } elseif ( IMPORT_EGW_14 === TRUE ) {
        $import = new Setup_Import_Egw14();
    } elseif ( IMPORT_TINE_REV_949 === TRUE ) {
        $import = new Setup_Import_TineRev949();
    }
    $import->import();
}

/**
 * import other data (from single tables)
 */
if ( IMPORT_EGW_14_ADDRESSBOOK === TRUE ) {
    $import = new Setup_Import_Egw14();
    $import->importAddressbook('egw_addressbook', FALSE);   
}

