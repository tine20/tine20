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
     * 
     * @throws Tinebase_Exception_NotFound
     */
    public function addWriterByConfig($loggerConfig)
    {
        $loggerConfig = ($loggerConfig instanceof Tinebase_Config_Struct || $loggerConfig instanceof Zend_Config)
            ? $loggerConfig : new Tinebase_Config_Struct($loggerConfig);
        
        if (empty($loggerConfig->filename)) {
            throw new Tinebase_Exception_NotFound('filename missing in logger config');
        }

        $filename = $loggerConfig->filename;
        $writer = new Zend_Log_Writer_Stream($filename);
        
        $formatter = new Tinebase_Log_Formatter();
        $formatter->setReplacements();
        $writer->setFormatter($formatter);

        $priority = (int)$loggerConfig->priority;
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
