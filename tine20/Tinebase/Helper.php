<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */


/**
 * returns one value of an array, indentified by its key
 *
 * @param mixed $_key
 * @param array $_array
 * @return mixed
 */
function array_value($_key, array $_array)
{
    return array_key_exists($_key, $_array) ? $_array[$_key] : NULL;
}

