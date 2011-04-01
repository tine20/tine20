<?php
/**
 * The following are convenience functions I use to quickly dump variables
 * and eventually I'm sure there will be other utilities in here too
 */
function pr($var) {

	if (is_string($var)) {
		$length = strlen($var);
	} else {
		$length = count($var);
	}
    ob_start();
    echo "<pre>";
    echo "Size: " . $length . "\n";
    echo "Value: \n";
    var_dump($var);
    echo "</pre>";
    echo ob_get_clean();

}

function pre($var) {

    pr($var);
    exit;

}
/**
 * Define the autoload function so that classes get loaded automatically in testing
 * I may eventually try to work out some type of autoload solution in the library but im not sure
 * of the implications as of yet.

function __autoload($className) {
	$paths = explode(PATH_SEPARATOR, get_include_path());
	foreach ($paths as $path) {
		$fileName = str_replace("_", DIRECTORY_SEPARATOR, $className);
	    if($exists = !class_exists($className) && file_exists($class = $path.DIRECTORY_SEPARATOR.$fileName.'.php')) {
	        require_once $class;
	    } elseif(!$exists) {
	        //eval('class '.$className.' extends Exception {}');
	        //throw new $className('[__autoload] this file doesn\'t exist: '.$class);
	    }
    }
}
 */