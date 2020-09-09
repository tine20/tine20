<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Log
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

class Tinebase_Log_Writer_Db extends Zend_Log_Writer_Db
{
    /**
     * Write a message to the log.
     *
     * @param  array  $event  event data
     * @return void
     * @throws Zend_Log_Exception
     */
    protected function _write($event)
    {
        $eventData = $this->_formatter->format($event);
        parent::_write($eventData);
    }

    /**
     * Set a new formatter for this writer
     *
     * @param  Zend_Log_Formatter_Interface $formatter
     * @return Zend_Log_Writer_Abstract
     */
    public function setFormatter(Zend_Log_Formatter_Interface $formatter)
    {
        $this->_formatter = $formatter;
        return $this;
    }
}
