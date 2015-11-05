<?php
/*!
 * Expresso Lite
 * This page handles all AJAX requests at the server side
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */
require_once (dirname(__FILE__).'/bootstrap.php');

use ExpressoLite\Backend\AjaxProcessor;

$processor = new AjaxProcessor();
$processor->processHttpRequest($_REQUEST);
