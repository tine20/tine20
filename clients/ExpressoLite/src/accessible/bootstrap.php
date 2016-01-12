<?php
/**
 * Bootstrap to be added in accessible module. Adds everything
 * included in the API bootstrap plus an extra class loader for
 * the accessible module classes.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

require (dirname(__FILE__) . '/../api/bootstrap.php');

$loader = new SplClassLoader('Accessible', dirname(__FILE__));
$loader->register();
