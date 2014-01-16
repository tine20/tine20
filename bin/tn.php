<?php
/**
 * Script for code generation
 *
 * @package     Tine_Tool
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
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
        echo create(APPLICATION, array($applicationName));
        exit;
    }
    echo "UNEXPECTED ERROR: missing argument. Type tn -help to see parameters \n";
    exit;
}

/**
 * Sets the include path and loads autoloader classes
 */
function prepareEnvironment()
{
    $paths = array(
            realpath(__DIR__ . '/../tine20'),
            realpath(__DIR__ . '/../tine20/library'),
            get_include_path()
    );
    set_include_path(implode(PATH_SEPARATOR, $paths));

    require_once realpath(__DIR__ . '/../tine20') . '/bootstrap.php';
}

/**
 * Creates an instance of a class that knows how to build the requested structure
 * Command can executed from everywhere, because the path is determined by __DIR__
 * @param string $builder
 * @param array $args
 * @return string message
 */
function create($builder, array $args)
{
    // last argument is path of Tine 2.0
    $args[] = realpath(__DIR__ . '/../tine20');

    $className = 'Tool_CodeGenerator_' . $builder;
    $tcg = new $className();
    return $tcg->build($args);
}
