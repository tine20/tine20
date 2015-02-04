<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @todo        when more formats switched to Tinebase_Export_Abstract, change creation of object (new $exportClass($_additionalOptions))
 * @todo        add registry of export classes ?
 */

/**
 * Export Factory Class
 *
 * @package     Tinebase
 * @subpackage  Export
 */
class Tinebase_Export
{
    /**
     * get export object for given filter and format
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string|array $_options format (as string) or export definition id (array)
     * @param Tinebase_Controller_Record_Interface $_controller (optional)
     * @param array $_additionalOptions (optional)
     * @return Tinebase_Export_Abstract
     * @throws Tinebase_Exception_NotFound
     */
    public static function factory($_filter, $_options, $_controller = NULL, $_additionalOptions = array()) 
    {
        if (! is_array($_options)) {
            $_options = array(
                'format' => $_options
            );
        }
        
        // always merge options? this needs to be refactored!
        $_additionalOptions = array_merge($_additionalOptions, $_options);
        
        if ((isset($_options['definitionId']) || array_key_exists('definitionId', $_options))) {
            $definition = Tinebase_ImportExportDefinition::getInstance()->get($_options['definitionId']);
            $exportClass = $definition->plugin;
            
        } else if ((isset($_options['format']) || array_key_exists('format', $_options)) && ! empty($_options['format'])) {
            $appName = $_filter->getApplicationName();
            $model = $_filter->getModelName();
            
            $exportClass = $appName . '_Export_' . ucfirst(strtolower($_options['format']));
            
            // start output buffering to catch errors, append them to log and exception
            ob_start();
            
            if (! class_exists($exportClass)) {
                
                $ob = (ob_get_length() > 0) ? ob_get_clean() : '';
                
                // check for model specific export class
                list($a, $b, $modelPart) = explode('_', $model);
                $exportClass2 = $exportClass . '_' . $modelPart;
                
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                    Tinebase_Core::getLogger()->log(__METHOD__ . '::' . __LINE__ . ' Could not find class ' . $exportClass . ' trying ' . $exportClass2 . '. Output Buffer: ' . PHP_EOL . $ob, Zend_Log::NOTICE);
                }
                
                if (! class_exists($exportClass2)) {
                    
                    $ob = (ob_get_length() > 0) ? ob_get_clean() : NULL;
                    
                    ob_end_flush();
                    
                    throw new Tinebase_Exception_NotFound('No ' . $_options['format'] . ' export class found for ' . $appName . ' / ' . $model . '. ClassName: ' . $exportClass2 . ($ob ? 'Output: ' . $ob : ''));
                } else {
                    $exportClass = $exportClass2;
                }
            }
            
            ob_end_flush();
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Export options: ' . print_r($_options, TRUE));
            throw new Tinebase_Exception_InvalidArgument('Export definition ID or format required in options');
        }
        
        if (preg_match('/pdf/i', $exportClass)) {
            // legacy
            $result = new $exportClass($_additionalOptions);
        } else {
            $result = new $exportClass($_filter, $_controller, $_additionalOptions);
        }
        
        return $result;
    }
}
