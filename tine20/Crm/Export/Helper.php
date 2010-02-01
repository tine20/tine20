<?php
/**
 * Crm 
 *
 * @package     Crm
 * @subpackage	Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * Crm Ods generation class
 * 
 * @package     Crm
 * @subpackage	Export
 * 
 */
class Crm_Export_Helper
{
    /**
     * get special field value
     *
     * @param Tinebase_Record_Interface $_record
     * @param array $_param
     * @param string $_key
     * @param string $_cellType
     * @return string
     */
    public static function getSpecialFieldValue(Tinebase_Record_Interface $_record, $_param, $_key = NULL, &$_cellType = NULL)
    {
        if (is_null($_key)) {
            throw new Tinebase_Exception_InvalidArgument('Missing required parameter $key');
        }
        
        switch($_param['type']) {
            case 'status':
                $value = $_record->getLeadStatus();
                break;
            case 'source':
                $settings = Crm_Controller::getInstance()->getSettings();
                $source = $settings->getOptionById($_record->leadsource_id, 'leadsources');
                $value = $source['leadsource'];
                break;
            case 'type':
                $settings = Crm_Controller::getInstance()->getSettings();
                $type = $settings->getOptionById($_record->leadtype_id, 'leadtypes');
                $value = $type['leadtype'];                
                break;
            default:
                $value = '';
        }

        return $value;
    }
}
