<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * Tinebase_Model_Filter_Id
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * 
 * @todo why don't we extend the textfilter? / what for do we need a seperate idfilter?
 * 
 * filters one or more ids
 */
class Tinebase_Model_Filter_ContainerOwnerId extends Tinebase_Model_Filter_Id
{
    /**
     * returns quoted column name for sql backend
     *
     * @param  Tinebase_Backend_Sql_Interface $_backend
     * @return string
     * 
     * @todo to be removed once we split filter model / backend
     */
    protected function _getQuotedFieldName($_backend) {
        return $_backend->getAdapter()->quoteIdentifier(
            'owner.' . $this->_field
        );
    }
}
