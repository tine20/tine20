<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        when more formats switched to Tinebase_Export_Abstract, change creation of object (new $exportClass($_additionalOptions))
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
     * @param string $_format
     * @param Tinebase_Controller_Record_Interface $_controller (optional)
     * @param array $_additionalOptions (optional)
     * @return Tinebase_Export_Abstract
     * @throws Tinebase_Exception_NotFound
     */
    public static function factory($_filter, $_format, $_controller = NULL, $_additionalOptions = array()) 
    {
        $appName = $_filter->getApplicationName();
        $model = $_filter->getModelName();
        
        $exportClass = $appName . '_Export_' . ucfirst(strtolower($_format));
        if (! @class_exists($exportClass)) {
            
            // check for model specific export class
            list($a, $b, $modelPart) = explode('_', $model);
            $exportClass = $exportClass . '_' . $modelPart;
            
            if (! class_exists($exportClass)) {
                throw new Tinebase_Exception_NotFound('No ' . $_format . ' export class found for ' . $appName . ' / ' . $model);
            }
        }
        if ($_format == 'ods' || $_format == 'xls') {
            $result = new $exportClass($_filter, $_controller, $_additionalOptions);
        } else {
            // legacy
            $result = new $exportClass($_additionalOptions);
        }
        
        return $result;
    }
}
