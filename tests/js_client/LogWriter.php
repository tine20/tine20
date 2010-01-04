<?php
require_once 'Zend/Log/Writer/Abstract.php';

class LogWriter extends Zend_Log_Writer_Abstract
{
    protected $_selenium;
    
    public function __construct($_selenium)
    {
        $this->_selenium = $_selenium;
    }
    
    /**
     * Write a message to the log.
     *
     * @param  array  $event  event data
     * @return void
     */
    protected function _write($event)
    {
        $this->_selenium->getEval("window.Ext.ux.MessageBox.msg('{$event['priorityName']}', '{$event['message']}')");
    }
}