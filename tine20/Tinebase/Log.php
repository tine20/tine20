<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Log
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * class Tinebase_Log
 * 
 * @package Tinebase
 * @subpackage Log
 */
class Tinebase_Log extends Zend_Log
{
    /**
     * add new log writer defined by a config object/array
     * 
     * @param Tinebase_Config_Struct|array $loggerConfig
     */
    public function addWriterByConfig($loggerConfig)
    {
        $loggerConfig = ($loggerConfig instanceof Tinebase_Config_Struct) ? $loggerConfig : new Tinebase_Config_Struct($loggerConfig);
        $filename = $loggerConfig->filename;
        $priority = (int)$loggerConfig->priority;

        $writer = new Zend_Log_Writer_Stream($filename);
        $formatter = new Tinebase_Log_Formatter();
        $formatter->setReplacements();
        $writer->setFormatter($formatter);

        $filter = new Zend_Log_Filter_Priority($priority);
        $writer->addFilter($filter);

        // add more filters here
        if (isset($loggerConfig->filter->user)) {
            $writer->addFilter(new Tinebase_Log_Filter_User($loggerConfig->filter->user));
        }
        if (isset($loggerConfig->filter->message)) {
            $writer->addFilter(new Zend_Log_Filter_Message($loggerConfig->filter->message));
        }
        
        $this->addWriter($writer);
    }
}
