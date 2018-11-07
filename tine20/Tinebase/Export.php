<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2018 Metaways Infosystems GmbH (http://www.metaways.de)
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
    protected static $_pdfLegacyHandling = true;

    /**
     * @param bool $bool
     * @return bool
     */
    public static function doPdfLegacyHandling($bool = true)
    {
        $oldValue = static::$_pdfLegacyHandling;
        if (null !== $bool) {
            static::$_pdfLegacyHandling = (bool)$bool;
        }
        return $oldValue;
    }

    /**
     * get export object for given filter and format
     * 
     * @param Tinebase_Model_Filter_FilterGroup|null $_filter
     * @param string|array $_options format (as string) or export definition id (array)
     * @param Tinebase_Controller_Record_Interface $_controller (optional)
     * @param array $_additionalOptions (optional)
     * @return Tinebase_Export_Abstract
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_InvalidArgument
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
        
        if (isset($_options['definitionId'])) {
            $definition = Tinebase_ImportExportDefinition::getInstance()->get($_options['definitionId']);
            $exportClass = $definition->plugin;
            
        } else if (isset($_options['format']) && ! empty($_options['format'])) {
            $appName = $_filter->getApplicationName();
            $model = $_filter->getModelName();
            $exportClass = self::_getExportClass($appName, $model, $_options['format']);
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Export options: ' . print_r($_options, TRUE));
            throw new Tinebase_Exception_InvalidArgument('Export definition ID or format required in options');
        }
        
        if (preg_match('/pdf/i', $exportClass) && true === static::$_pdfLegacyHandling) {
            // legacy
            $result = new $exportClass($_additionalOptions);
        } else if (class_exists($exportClass)) {
            $result = new $exportClass($_filter, $_controller, $_additionalOptions);
        } else {
            throw new Tinebase_Exception_NotFound('class ' . $exportClass . ' not found');
        }
        
        return $result;
    }

    /**
     * getExportClass
     *
     * @param $appName
     * @param $model
     * @param $format
     * @return mixed
     * @throws Tinebase_Exception_NotFound
     */
    protected static function _getExportClass($appName, $model, $format)
    {
        $format = ucfirst(strtolower($format));
        $simpleModel = Tinebase_Record_Abstract::getSimpleModelName($appName, $model);
        $mainAppExport = $appName . '_Export_' . $format;
        $modelSpecificExport = $mainAppExport . '_' . $simpleModel;

        $exportClassesToTry = array(
            $modelSpecificExport,
            $mainAppExport,
            // generic tinebase export
            'Tinebase_Export_' . $format,
        );

        foreach ($exportClassesToTry as $class) {
            if (@class_exists($class)) {
                return $class;
            }
        }

        throw new Tinebase_Exception_NotFound('No ' . $format . ' export class found for ' . $appName . ' / ' . $model
            . '. Tried: ' . implode(',', $exportClassesToTry));
    }
}
