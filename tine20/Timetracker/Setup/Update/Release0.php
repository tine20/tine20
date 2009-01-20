<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

class Timetracker_Setup_Update_Release0 extends Setup_Update_Abstract
{
    /**
     * update function 1
     * - add is_billable to timeaccounts
     *
     */    
    public function update_1()
    {
        $field = '<field>
                    <name>is_billable</name>
                    <type>boolean</type>
                    <default>true</default>
                </field>';
        
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->addCol('timetracker_timeaccount', $declaration);
        
        $this->setApplicationVersion('Timetracker', '0.2');
    }
}
