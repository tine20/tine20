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
        
        if (array_key_exists('definitionId', $_options)) {
            $definition = Tinebase_ImportExportDefinition::getInstance()->get($_options['definitionId']);
            $exportClass = $definition->plugin;
            // export plugin needs the definition id
            $_additionalOptions = array_merge($_additionalOptions, $_options);
            
        } else if (array_key_exists('format', $_options) && ! empty($_options['format'])) {
            $appName = $_filter->getApplicationName();
            $model = $_filter->getModelName();
            
            $exportClass = $appName . '_Export_' . ucfirst(strtolower($_options['format']));
            if (! @class_exists($exportClass)) {
                
                // check for model specific export class
                list($a, $b, $modelPart) = explode('_', $model);
                $exportClass = $exportClass . '_' . $modelPart;
                
                if (! @class_exists($exportClass)) {
                    throw new Tinebase_Exception_NotFound('No ' . $_options['format'] . ' export class found for ' . $appName . ' / ' . $model);
                }
            }
        } else {
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
