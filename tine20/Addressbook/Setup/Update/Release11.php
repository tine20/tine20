<?php

/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2014-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Addressbook_Setup_Update_Release11 extends Setup_Update_Abstract
{
    /**
     * @return void
     */
    public function update_0()
    {
        $release10 = new Addressbook_Setup_Update_Release10($this->_backend);
        $release10->update_6();

        $this->setApplicationVersion('Addressbook', '11.1');
    }
}
