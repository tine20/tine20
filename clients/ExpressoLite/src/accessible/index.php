<?php
/**
 * Expresso Lite Accessible
 * Instantiates the dispatcher.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

require_once (dirname(__FILE__) . '/bootstrap.php');

use Accessible\Dispatcher;

Dispatcher::processRawHttpRequest($_REQUEST);
