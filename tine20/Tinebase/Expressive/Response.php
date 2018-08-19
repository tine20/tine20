<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Expressive
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use \Zend\Diactoros\Response;

/**
 * Tinebase Expressive Response class, the result object is stored here and awaits processing
 *
 * @package     Tinebase
 * @subpackage  Expressive
 */
class Tinebase_Expressive_Response extends Response
{
    /** @var Tinebase_Record_RecordSet|Tinebase_Record_Interface */
    public $resultObject = null;
}