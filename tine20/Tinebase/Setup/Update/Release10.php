<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Tinebase_Setup_Update_Release10 extends Setup_Update_Abstract
{
    /**
     * update to 10.1
     *
     * @see 0012162: create new MailFiler application
     */
    public function update_0()
    {
        $release9 = new Tinebase_Setup_Update_Release9($this->_backend);
        $release9->update_9();
        $this->setApplicationVersion('Tinebase', '10.1');
    }

    /**
     * update to 10.1
     *
     * @see 0012300: add container owner column
     */
    public function update_1()
    {
        $release9 = new Tinebase_Setup_Update_Release9($this->_backend);
        $release9->update_10();
        $this->setApplicationVersion('Tinebase', '10.2');
    }
}
