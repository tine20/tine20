<?php
/**
 * Tine 2.0
 *
 * @package     Projects
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class Projects_Setup_Update_Release5 extends Setup_Update_Abstract
{
    /**
    * update to 6.0
    *
    * @return void
    */
    public function update_0()
    {
        $this->setApplicationVersion('Projects', '6.0');
    }
}
