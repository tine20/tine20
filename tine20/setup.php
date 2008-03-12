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

#$import = new Setup_Import_Egw14();
#$import->import();