<?php
/**
 * Tine 2.0
 *
 * @package     Tasks
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2017-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

echo __FILE__ . ' must not be used or autoloaded or required etc.' . PHP_EOL;
exit(1);


class Tasks_Setup_Update_Release12 extends Setup_Update_Abstract
{
    /**
     * update to 12.1
     *
     * - update keyfield icons
     */
    public function update_0()
    {
        $release11 = new Tasks_Setup_Update_Release11($this->_backend);
        $release11->update_0();
        $this->setApplicationVersion('Tasks', '12.1');
    }
}
