<?php
/**
 * egroupware 2.0
 * 
 * @package     Addressbook
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * this classes provides access to the sql table egw_addressbook_lists
 * 
 * @package     Addressbook
 * @subpackage  Backend
 */
class Addressbook_Backend_Sql_Lists extends Zend_Db_Table_Abstract
{
    protected $_name = 'egw_addressbook_lists';
}
