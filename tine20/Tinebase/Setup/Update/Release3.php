<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id: Release2.php 12076 2009-12-28 12:05:25Z p.schuele@metaways.de $
 */

class Tinebase_Setup_Update_Release3 extends Setup_Update_Abstract
{    
    /**
     * update to 3.1
     * - add value_search option field to customfield_config
     */
    public function update_0()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>value_search</name>
                <type>boolean</type>
            </field>');
        $this->_backend->addCol('customfield_config', $declaration);
        
        $this->setTableVersion('customfield_config', '4');
        $this->setApplicationVersion('Tinebase', '3.1');
    }    
}
