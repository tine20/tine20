<?php
/**
 * Tine 2.0
 *
 * @package     Courses
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */
class Courses_Setup_Update_Release8 extends Setup_Update_Abstract
{
    /**
     * update to 8.1
     * - update 256 char fields
     * 
     * @see 0008070: check index lengths
     */
    public function update_0()
    {
        $columns = array("courses" => array(
                    "name" => '',
                    "type" => ''
                    )
                );
        $this->truncateTextColumn($columns, 255);
        
        $this->setTableVersion('courses', 7);
        $this->setApplicationVersion('Courses', '8.1');
    }
}