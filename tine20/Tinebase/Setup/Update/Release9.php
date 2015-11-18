<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Tinebase_Setup_Update_Release9 extends Setup_Update_Abstract
{
    /**
     * update to 9.1
     * 
     * @see 0011178: allow to lock preferences for individual users
     */
    public function update_0()
    {
        $update8 = new Tinebase_Setup_Update_Release8($this->_backend);
        $update8->update_11();
        $this->setApplicationVersion('Tinebase', '9.1');
    }

    /**
     * update to 9.2
     *
     * adds index to relations
     */
    public function update_1()
    {
        $update8 = new Tinebase_Setup_Update_Release8($this->_backend);
        $update8->update_12();
        $this->setApplicationVersion('Tinebase', '9.2');
    }

    /**
     * update to 9.3
     *
     * adds ondelete cascade to some indices (tags + roles)
     */
    public function update_2()
    {
        $update8 = new Tinebase_Setup_Update_Release8($this->_backend);
        $update8->update_13();
        $this->setApplicationVersion('Tinebase', '9.3');
    }

    /**
     * update to 9.4
     *
     * move keyFieldConfig defaults to config files
     */
    public function update_3()
    {
        $update8 = new Tinebase_Setup_Update_Release8($this->_backend);
        $update8->update_14();
        $this->setApplicationVersion('Tinebase', '9.4');
    }
}
