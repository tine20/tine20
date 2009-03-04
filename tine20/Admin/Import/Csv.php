<?php
/**
 * Tine 2.0
 * 
 * @package     Admin
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Admin csv import class
 * 
 * @package     Admin
 * @subpackage  Import
 * 
 */
class Admin_Import_Csv extends Tinebase_Import_Csv_Abstract
{
    /**
     * add some more values
     *
     * @return array
     */
    protected function _addData()
    {
        if ($this->_modelName == 'Tinebase_Model_FullUser') {
            $result = array(
                'accountPrimaryGroup'   => Tinebase_Config::getInstance()->getConfig('Default User Group')
            );
        } else {
            $result = parent::_addData();
        }
        
        return $result;
    }
}
