<?php

/**
 * this classes provides access to the sql table egw_addressbook2list
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: DbTable.php 4246 2007-03-27 22:35:56Z ralph $
 *
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
