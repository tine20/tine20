<?php
/**
 * Tine 2.0
 *
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

class Tasks_Setup_Update_Release2 extends Setup_Update_Abstract
{
    /**
     * update to 3.0
     * 
     * @return void
     */
    public function update_0()
    {
        $this->setApplicationVersion('Tasks', '3.0');
    }
}
