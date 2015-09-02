<?php
/**
 * Tine 2.0
 *
 * @package     Expressomail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * Expressomail updates for version 0.x
 *
 * @package     Expressomail
 * @subpackage  Setup
 */
class Expressomail_Setup_Update_Release0 extends Setup_Update_Abstract
{
    /**
     * update to 0.2
     * add Expressomail config parameter IMAPSEARCHMAXRESULTS
     *
     * @return void
     */
    public function update_1()
    {
        $settings = Expressomail_Config::getInstance()->get(Expressomail_Config::EXPRESSOMAIL_SETTINGS);
        if (! array_key_exists(Expressomail_Config::IMAPSEARCHMAXRESULTS, $settings)) {
            try {
                $properties = Expressomail_Config::getProperties();
                $property = $properties[Expressomail_Config::IMAPSEARCHMAXRESULTS];
                $default_value = $property['default'];
                $config = array(Expressomail_Config::IMAPSEARCHMAXRESULTS => $default_value);
                Expressomail_Controller::getInstance()->saveConfigSettings($config);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // do nothing
            }
        }

        $this->setApplicationVersion('Expressomail', '0.2');
    }

    /**
     * update to 0.3
     *
     * change index name conflicting with Expressomail
     */
    public function update_2()
    {
        try {
            $this->_backend->dropForeignKey('expressomail_account', 'account::credentials_id--credentials::id');
            $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>emaccount::credentials_id--credentials::id</name>
                <field>
                    <name>credentials_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>credential_cache</table>
                    <field>id</field>
                </reference>
            </index>');

            $this->_backend->addForeignKey('expressomail_account', $declaration);

            $this->setApplicationVersion('Expressomail', '0.3');
        } catch (Exception $e) {
            // do nothing
        }
    }

    /**
     * update to 0.4
     * add Expressomail config parameter AUTOSAVEDRAFTSINTERVAL
     *
     * @return void
     */
    public function update_3()
    {
        $settings = Expressomail_Config::getInstance()->get(Expressomail_Config::EXPRESSOMAIL_SETTINGS);
        if (! array_key_exists(Expressomail_Config::AUTOSAVEDRAFTSINTERVAL, $settings)) {
            try {
                $properties = Expressomail_Config::getProperties();
                $property = $properties[Expressomail_Config::AUTOSAVEDRAFTSINTERVAL];
                $default_value = $property['default'];
                $settings[Expressomail_Config::AUTOSAVEDRAFTSINTERVAL] = $default_value;
                Expressomail_Controller::getInstance()->saveConfigSettings($settings);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // do nothing
            }
        }

        $this->setApplicationVersion('Expressomail', '0.4');
    }

    /**
     * update to 0.5
     * add Expressomail config parameter AUTOSAVEDRAFTSINTERVAL
     *
     * @return void
     */
    public function update_4()
    {
        $settings = Expressomail_Config::getInstance()->get(Expressomail_Config::EXPRESSOMAIL_SETTINGS);
        if (! array_key_exists(Expressomail_Config::REPORTPHISHINGEMAIL, $settings)) {
            try {
                $properties = Expressomail_Config::getProperties();
                $property = $properties[Expressomail_Config::REPORTPHISHINGEMAIL];
                $default_value = $property['default'];
                $settings[Expressomail_Config::REPORTPHISHINGEMAIL] = $default_value;
                Expressomail_Controller::getInstance()->saveConfigSettings($settings);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // do nothing
            }
        }

        $this->setApplicationVersion('Expressomail', '0.5');
    }
}