<?php
/**
 * Crm xls generation class
 *
 * @package     Crm
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Ods.php 10912 2009-10-12 14:40:25Z p.schuele@metaways.de $
 * 
 * @todo        add relations, notes/history, formulas (?)
 * @todo        use template
 */

/**
 * Crm xls generation class
 * 
 * @package     Crm
 * @subpackage  Export
 */
class Crm_Export_Xls extends Tinebase_Export_Xls
{
    /**
     * @var string $_applicationName
     */
    protected $_applicationName = 'Crm';
    
    /**
     * export records to Xls file
     *
     * @param Crm_Model_LeadFilter $_filter
     * @return PHPExcel
     */
    public function generate(Crm_Model_LeadFilter $_filter)
    {
        return $this->_generate($_filter, Crm_Controller_Lead::getInstance());
    }
    
    /**
     * get default export config
     * 
     * @return array
     */
    protected function _getDefaultConfig()
    {
        return array(
            'fields' => array(
                'lead_name' => array(
                    'header'    => $this->_translate->_('Lead Name'),
                    'type'      => 'string', 
                    'width'     => '5cm',
                ),
                'description' => array(
                    'header'    => $this->_translate->_('Description'),
                    'type'      => 'string', 
                    'width'     => '10cm'
                ),
                'turnover' => array(
                    'header'    => $this->_translate->_('Turnover'),
                    'type'      => 'string', 
                    'width'     => '2cm'
                ),
                'probability' => array(
                    'header'    => $this->_translate->_('Probability'),
                    'type'      => 'string', 
                    'width'     => '2cm'
                ),
                'start' => array(
                    'header'    => $this->_translate->_('Date Start'),
                    'type'      => 'datetime', 
                    'width'     => '2,5cm'
                ),
                'end' => array(
                    'header'    => $this->_translate->_('Date End'),
                    'type'      => 'datetime', 
                    'width'     => '2,5cm'
                ),
                'end_scheduled' => array(
                    'header'    => $this->_translate->_('Date End Scheduled'),
                    'type'      => 'datetime', 
                    'width'     => '2,5cm'
                ),
                /*
                'created_by' => array(
                    'header'    => $this->_translate->_('Created By'),
                    'type'      => 'created_by', 
                    'field'     => 'accountDisplayName', 
                    'width'     => '4cm'
                ),
                */
            )
        );
    }
}
