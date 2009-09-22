<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 *

/**
 * csv import class for the addressbook
 * 
 * @package     Addressbook
 * @subpackage  Import
 * 
 */
class Addressbook_Import_Csv extends Tinebase_Import_Csv_Abstract
{
    /**
     * add some more values (container id)
     *
     * @return array
     */
    protected function _addData()
    {
        $result = array();
        if (isset($this->_options['container_id'])) {
            $result['container_id'] = $this->_options['container_id'];
        }

        return $result;
    }
    
    /**
     * do conversions
     * -> sanitize account_id
     *
     * @param array $_data
     * @return array
     */
    protected function _doConversions($_data)
    {
        $result = parent::_doConversions($_data);
        
        // unset account id
        if (isset($result['account_id']) && empty($result['account_id'])) {
            unset($result['account_id']);
        }
        
        return $result;
    }    
}
