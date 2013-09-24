<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * updates for major release 7
 *
 * @package     ActiveSync
 * @subpackage  Setup
 */
class ActiveSync_Setup_Update_Release7 extends Setup_Update_Abstract
{
    /**
     * update to 7.1
     * 
     * @see 0007452: use json encoded array for saving of policy settings
     * 
     * @return void
     */
    public function update_0()
    {
        if ($this->getTableVersion('acsync_policy') != 3) {
            $release6 = new ActiveSync_Setup_Update_Release6($this->_backend);
            $release6->update_3();
        }
        
        $this->setApplicationVersion('ActiveSync', '7.1');
    }

    /**
     * update to 7.2
     * 
     * @see 0007942: reset device pingfolder in update script
     * 
     * @return void
     */
    public function update_1()
    {
        $release6 = new ActiveSync_Setup_Update_Release6($this->_backend);
        $release6->update_4();
        
        $this->setApplicationVersion('ActiveSync', '7.2');
    }

     /**
     * update to 7.3
     * 
     * @see 0008596: Redundant Ping commands running for long time
     * 
     * @return void
     */
    public function update_2()
    {
        $this->validateTableVersion('acsync_device', '5');

        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>lastping</name>
                <type>datetime</type>
            </field>
        ');
        $this->_backend->addCol('acsync_device', $declaration);

        $this->setTableVersion('acsync_device', '6');
        $this->setApplicationVersion('ActiveSync', '7.3');
    }
    
    /**
     * update to 8.0
     *
     * @return void
     */
    public function update_3()
    {
        $this->setApplicationVersion('ActiveSync', '8.0');
    }
}
