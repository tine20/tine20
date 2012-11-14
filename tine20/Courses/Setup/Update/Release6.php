<?php
/**
 * Tine 2.0
 *
 * @package     Courses
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class Courses_Setup_Update_Release6 extends Setup_Update_Abstract
{
    /**
     * update to 6.1 (rerun Courses_Setup_Update_Release5->update_1 if required)
     * @return void
     */
    public function update_0()
    {
        if ($this->getTableVersion('courses') != 5) {
            $release5 = new Courses_Setup_Update_Release5($this->_backend);
            $release5->update_1();
        }
        
        $this->setApplicationVersion('Courses', '6.1');
    }
    
    /**
     * update to 7.0
     * 
     * @return void
     */
    public function update_1()
    {
        $this->setApplicationVersion('Courses', '7.0');
    }
}
