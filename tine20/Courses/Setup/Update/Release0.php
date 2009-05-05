<?php
/**
 * Tine 2.0
 *
 * @package     Courses
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

class Courses_Setup_Update_Release0 extends Setup_Update_Abstract
{
    /**
     * update function 1
     * - add fileserver (access) to timeaccounts
     *
     */    
    public function update_1()
    {
        $field = '<field>
            <name>fileserver</name>
            <type>boolean</type>
            <default>false</default>
        </field>';
        
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->addCol('courses', $declaration);
        
        $this->setApplicationVersion('Courses', '0.2');
    }
}
