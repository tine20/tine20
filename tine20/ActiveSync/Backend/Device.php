<?php
class ActiveSync_Backend_Device extends Tinebase_Application_Backend_Sql_Abstract 
{
    /**
     * the constructor
     *
     */
    public function __construct ()
    {
        parent::__construct(SQL_TABLE_PREFIX . 'acsync_device', 'ActiveSync_Model_Device');
    }
}