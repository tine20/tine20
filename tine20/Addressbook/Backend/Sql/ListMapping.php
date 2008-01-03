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
 * this classes provides access to the sql table egw_addressbook2list
 * 
 * @package     Addressbook
 * @subpackage  Backend
 */
class Addressbook_Backend_Sql_ListMapping extends Zend_Db_Table_Abstract
{
    protected $_name = 'egw_addressbook2list';
    
    protected $_referenceMap    = array(
        'Contact' => array(
            'columns'       => array('contact_id'),
            'refTableClass' => 'Addressbook_Backend_Sql_Contacts',
            'refColumns'    => array('contact_id'),
        ),
        'List' => array(
            'columns'       => array('list_id'),
            'refTableClass' => 'Addressbook_Backend_Sql_Lists',
            'refColumns'    => array('list_id'),
        )
    );
    
}
