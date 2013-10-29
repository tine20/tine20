<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Tinebase_Setup_Update_Release8 extends Setup_Update_Abstract
{
    /**
     * update to 8.1
     * 
     * @see 0009152: saving of record fails because of too many relations
     */
    public function update_0()
    {
        $valueFields = array('old_value', 'new_value');
        foreach ($valueFields as $field) {
            
            // check schema, only change if type == text
            $typeMapping = $this->_backend->getTypeMapping('text');
            $schema = Tinebase_Db_Table::getTableDescriptionFromCache(SQL_TABLE_PREFIX . 'timemachine_modlog', $this->_backend->getDb());
            
            if ($schema[$field]['DATA_TYPE'] === $typeMapping['defaultType']) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Old column type (' . $schema[$field]['DATA_TYPE'] . ') is going to be altered to clob');
                
                $declaration = new Setup_Backend_Schema_Field_Xml('
                    <field>
                        <name>' . $field . '</name>
                        <type>clob</type>
                    </field>
                ');
            
                $this->_backend->alterCol('timemachine_modlog', $declaration);
            }
        }
        $this->setTableVersion('timemachine_modlog', '3');
        $this->setApplicationVersion('Tinebase', '8.1');
    }
}
