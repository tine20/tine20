<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */
class Tinebase_Setup_Update_Release12 extends Setup_Update_Abstract
{
    /**
     * update to 12.1
     *
     * check for container without a model
     */
    public function update_0()
    {
        $release11 = new Tinebase_Setup_Update_Release11($this->_backend);
        $release11->update_27();
        $this->setApplicationVersion('Tinebase', '12.1');
    }

    /**
     * update to 12.2
     *
     * add hierarchy column to container
     */
    public function update_1()
    {
        $release11 = new Tinebase_Setup_Update_Release11($this->_backend);
        $release11->update_28();
        $this->setApplicationVersion('Tinebase', '12.2');
    }

    /**
     * update to 12.3
     *
     * create replication user
     */
    public function update_2()
    {
        $release11 = new Tinebase_Setup_Update_Release11($this->_backend);
        $release11->update_29();
        $this->setApplicationVersion('Tinebase', '12.3');
    }

    /**
     * update to 12.4
     *
     * create replication user
     */
    public function update_3()
    {
        $release11 = new Tinebase_Setup_Update_Release11($this->_backend);
        $release11->update_30();
        $this->setApplicationVersion('Tinebase', '12.4');
    }

    /**
     * delete some obsolete export definitions
     */
    public function update_4()
    {
        $release11 = new Tinebase_Setup_Update_Release11($this->_backend);
        $release11->update_31();
        $this->setApplicationVersion('Tinebase', '12.5');
    }

    /**
     * increase temp_file size column to bigint
     */
    public function update_5()
    {
        $release11 = new Tinebase_Setup_Update_Release11($this->_backend);
        $release11->update_32();
        $this->setApplicationVersion('Tinebase', '12.6');
    }

    /**
     * increase temp_file size column to bigint
     */
    public function update_6()
    {
        $release10 = new Tinebase_Setup_Update_Release10($this->_backend);
        $release10->update_57();
        $this->setApplicationVersion('Tinebase', '12.7');
    }
}
