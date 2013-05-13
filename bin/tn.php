<?php
/**
 * Script for code generation
 *
 * @package     Tine_Tool
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$ 
 */

define('APPLICATION','Application');

main();

function main()
{
	prepareEnvironment();
	
	try {
		$opts = new Zend_Console_Getopt(array(
				'create application|a=s'=>'create application option with required string parameter',
				'help'=>'help option with no required parameter'
		)
		);
		$opts->parse();
	} catch (Zend_Console_Getopt_Exception $e) {
		echo $e->getUsageMessage();
		exit;
	}

	if ($applicationName = $opts->getOption('a'))
	{
		create(APPLICATION, array($applicationName));
		echo "Application $applicationName was created successfully into tine20 folder! \n";
		exit;
	}
	echo $e->getUsageMessage();
	exit;
}

/**
 * Sets the include path and loads autoloader classes
 */
function prepareEnvironment()
{
	$paths = array(
			realpath(dirname(__FILE__) . '/../tine20'),
			realpath(dirname(__FILE__) . '/../tine20/library'),
			get_include_path()
	);
	set_include_path(implode(PATH_SEPARATOR, $paths));

	require_once 'Zend/Loader/Autoloader.php';
	$autoloader = Zend_Loader_Autoloader::getInstance();
	$autoloader->setFallbackAutoloader(true);
	Tinebase_Autoloader::initialize($autoloader);
}

/**
 * Creates an instance of a class that knows how to build the requested structure
 * @param string $builder
 */
function create($builder, array $args)
{	
	// last argument is path of Tine 2.0
	$args[] = realpath(dirname(__FILE__) . '/../tine20');

	$className = 'Tool_CodeGenerator_' . $builder;
	$tcg = new $className();
	$tcg->build($args);
}