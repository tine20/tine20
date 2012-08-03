<?php
/**
 * TimeZoneConvert
 *
 * @package     TimeZoneConvert
 * @subpackage  Tests
 * @license     MIT, BSD, and GPL
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 */

$paths = array(
    realpath(__DIR__),
    realpath(__DIR__ . '/../lib'),
    get_include_path()
);
set_include_path(implode(PATH_SEPARATOR, $paths));

spl_autoload_register(function($class) {
    require_once str_replace('_', '/', $class) . '.php';
});