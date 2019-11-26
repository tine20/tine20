<?php

/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2014-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

echo __FILE__ . ' must not be used or autoloaded or required etc.' . PHP_EOL;
exit(1);

class Addressbook_Setup_Update_Release12 extends Setup_Update_Abstract
{
    /**
     * @return void
     */
    public function update_0()
    {
        $release11 = new Addressbook_Setup_Update_Release11($this->_backend);
        $release11->update_10();

        $this->setApplicationVersion('Addressbook', '12.1');
    }

    /**
     * @return void
     */
    public function update_1()
    {
        $release11 = new Addressbook_Setup_Update_Release11($this->_backend);
        $release11->update_11();

        $this->setApplicationVersion('Addressbook', '12.2');
    }

    /**
     * @return void
     */
    public function update_2()
    {
        $release11 = new Addressbook_Setup_Update_Release11($this->_backend);
        $release11->update_12();

        $this->setApplicationVersion('Addressbook', '12.3');
    }

    /**
     * @return void
     */
    public function update_3()
    {
        $release11 = new Addressbook_Setup_Update_Release11($this->_backend);
        $release11->update_13();

        $this->setApplicationVersion('Addressbook', '12.4');
    }
}
