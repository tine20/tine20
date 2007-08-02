<?php

interface Addressbook_Contacts_Interface
{
    public function deletePersonalContacts(array $contacts);

    public function getPersonalContacts($filter, $sort, $dir, $limit = NULL, $start = NULL);

    public function getPersonalCount();
    
    public function getInternalContacts($filter, $sort, $dir, $limit = NULL, $start = NULL);

    public function getInternalCount();
}
