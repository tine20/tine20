<?php

/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2014-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class Addressbook_Setup_Update_Release10 extends Setup_Update_Abstract
{
    /**
     * update to 10.1:
     * - add multiple sync backends / ldap implementation
     * - add addressbook_industry table and column
     *
     * @return void
     */
    public function update_0()
    {
        $release9 = new Addressbook_Setup_Update_Release9($this->_backend);
        $release9->update_9();
        $release9->update_10();

        $this->setApplicationVersion('Addressbook', '10.1');
    }

    /**
     * fixes adb table version (versions got mixed up in previous update scripts)
     *
     * @return void
     */
    public function update_1()
    {
        $this->setTableVersion('addressbook', 22);
        $this->setApplicationVersion('Addressbook', '10.2');
    }

    /**
     * Adds a flag to toggle between private and business as preferred address
     *
     * @return void
     */
    public function update_2()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>preferred_address</name>
                <type>integer</type>
                <notnull>false</notnull>
            </field>');

        $this->_backend->addCol('addressbook', $declaration);

        $this->setTableVersion('addressbook', 23);
        $this->setApplicationVersion('Addressbook', '10.3');
    }


    /**
     * update to 10.4
     *
     * Add fulltext index for note field
     */
    public function update_3()
    {
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>note</name>
                <fulltext>true</fulltext>
                <field>
                    <name>note</name>
                </field>
            </index>
        ');

        try {
            $this->_backend->addIndex('addressbook', $declaration);
        } catch (Exception $e) {
            // might have already been added by \Setup_Controller::upgradeMysql564
            Tinebase_Exception::log($e);
        }

        $this->setTableVersion('addressbook', 24);
        $this->setApplicationVersion('Addressbook', '10.4');
    }

    /**
     * update to 10.5
     *
     * import export definitions
     */
    public function update_4()
    {
        $addressbookApplication = Tinebase_Application::getInstance()->getApplicationByName('Addressbook');
        $definitionDirectory = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'Export' . DIRECTORY_SEPARATOR . 'definitions' . DIRECTORY_SEPARATOR;

        $dir = new DirectoryIterator($definitionDirectory);
        foreach ($dir as $fileinfo) {
            if ($fileinfo->isFile()) {
                Tinebase_ImportExportDefinition::getInstance()->updateOrCreateFromFilename(
                    $fileinfo->getPath() . '/' . $fileinfo->getFilename(),
                    $addressbookApplication
                );
            }
        }

        $this->setApplicationVersion('Addressbook', '10.5');
    }

    public function update_5()
    {
        if ($this->getTableVersion('addressbook') < 25) {
            $this->setTableVersion('addressbook', 25);
        }
        if ($this->getTableVersion('addressbook_lists') < 6) {
            $this->setTableVersion('addressbook_lists', 6);
        }
        $this->setApplicationVersion('Addressbook', '10.6');
    }

    public function update_6()
    {
        if ($this->getTableVersion('addressbook_lists') == 25) {
            $this->setTableVersion('addressbook_lists', 6);
        }
        $this->setApplicationVersion('Addressbook', '10.7');
    }

    public function update_7()
    {
        $this->setApplicationVersion('Addressbook', '11.0');
    }
}
