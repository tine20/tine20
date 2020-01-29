<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2018-2019 Metaways Infosystems GmbH (http://www.metaways.de)
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
        $this->update_11();

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
        $this->update_11();

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
        $this->update_11();

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
        $this->update_11();

        $release11 = new Tinebase_Setup_Update_Release11($this->_backend);
        $release11->update_30();
        $this->setApplicationVersion('Tinebase', '12.4');
    }

    /**
     * delete some obsolete export definitions
     */
    public function update_4()
    {
        $this->update_11();

        $release11 = new Tinebase_Setup_Update_Release11($this->_backend);
        $release11->update_31();
        $this->setApplicationVersion('Tinebase', '12.5');
    }

    /**
     * increase temp_file size column to bigint
     */
    public function update_5()
    {
        $this->update_11();

        $release11 = new Tinebase_Setup_Update_Release11($this->_backend);
        $release11->update_32();
        $this->setApplicationVersion('Tinebase', '12.6');
    }

    /**
     * change unique key parent_id - name - deleted_time so that it really works
     */
    public function update_6()
    {
        $this->update_11();

        $release10 = new Tinebase_Setup_Update_Release10($this->_backend);
        $release10->update_57();
        $this->setApplicationVersion('Tinebase', '12.7');
    }

    /**
     * container xprops need to be [] not NULL
     */
    public function update_7()
    {
        $release11 = new Tinebase_Setup_Update_Release11($this->_backend);
        $release11->update_34();
        $this->setApplicationVersion('Tinebase', '12.8');
    }

    /**
     * create filterSyncToken table and add clean up job
     */
    public function update_8()
    {
        $this->update_11();

        $release11 = new Tinebase_Setup_Update_Release11($this->_backend);
        $release11->update_35();
        $this->setApplicationVersion('Tinebase', '12.9');
    }

    /**
     * update temp file cleanup task
     */
    public function update_9()
    {
        $this->update_11();

        $release11 = new Tinebase_Setup_Update_Release11($this->_backend);
        $release11->update_36();
        $this->setApplicationVersion('Tinebase', '12.10');
    }

    /**
     * update to 12.11
     *
     * update filterSyncToken table
     */
    public function update_10()
    {
        $this->update_11();

        $release11 = new Tinebase_Setup_Update_Release11($this->_backend);
        $release11->update_39();
        $this->setApplicationVersion('Tinebase', '12.11');
    }

    /**
     * update to 12.12
     *
     * add is_system column to customfield_config
     */
    public function update_11()
    {
        $release11 = new Tinebase_Setup_Update_Release11($this->_backend);
        $release11->update_38();
        $this->setApplicationVersion('Tinebase', '12.12');
    }

    /**
     * update to 12.13
     *
     * mcv2 schemas will be updated automatically, leave this empty!
     */
    public function update_12()
    {
        $this->setApplicationVersion('Tinebase', '12.13');
    }

    /**
     * update note icons
     */
    public function update_13()
    {
        $release11 = new Tinebase_Setup_Update_Release11($this->_backend);
        $release11->update_40();
        $this->setApplicationVersion('Tinebase', '12.14');
    }

    /**
     * ensure tree_filerevision exist for all fileobjects
     */
    public function update_14()
    {
        $release11 = new Tinebase_Setup_Update_Release11($this->_backend);
        $release11->update_41();
        $this->setApplicationVersion('Tinebase', '12.15');
    }

    /**
     * update to 12.16
     *
     * fix role name unique key
     */
    public function update_15()
    {
        $release10 = new Tinebase_Setup_Update_Release10($this->_backend);
        $release10->update_58();
        $this->setApplicationVersion('Tinebase', '12.16');
    }

    /**
     * update to 12.17
     *
     * just an empty update - the migration is done in the Setup_Controller
     */
    public function update_16()
    {
        $this->setApplicationVersion('Tinebase', '12.17');
    }

    /**
     * update to 12.18
     *
     * reimport templates
     */
    public function update_17()
    {
        $release11 = new Tinebase_Setup_Update_Release11($this->_backend);
        $release11->update_44();
        $this->setApplicationVersion('Tinebase', '12.18');
    }
}
