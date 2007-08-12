<?php

/**
 * interface for contacs class
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/license/gpl GPL
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
interface Addressbook_Contacts_Interface
{
    public function deletePersonalContacts(array $contacts);

    public function getPersonalContacts($filter, $sort, $dir, $limit = NULL, $start = NULL);

    public function getPersonalCount();
    
    public function getInternalContacts($filter, $sort, $dir, $limit = NULL, $start = NULL);

    public function getInternalCount();
}
