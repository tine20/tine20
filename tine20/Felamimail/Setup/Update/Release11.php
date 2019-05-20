<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2018-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class Felamimail_Setup_Update_Release11 extends Setup_Update_Abstract
{
    /**
     * update to 11.1
     *
     * change default sieve notification template
     */
    public function update_0()
    {
        if (! Tinebase_Core::isFilesystemAvailable()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                . ' Skipping update of sieve notification template');
        } else {
            $basepath = Tinebase_FileSystem::getInstance()->getApplicationBasePath(
                'Felamimail',
                Tinebase_FileSystem::FOLDER_TYPE_SHARED
            );

            if (false === ($fh = Tinebase_FileSystem::getInstance()->fopen(
                    $basepath . '/Email Notification Templates/defaultForwarding.sieve', 'w'))) {
                if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) {
                    Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                        . ' Could not open defaultForwarding.sieve file');
                }
            } else {

                fwrite($fh, <<<'sieveFile'
require ["enotify", "variables", "copy", "body"];

if header :contains "Return-Path" "<>" {
    if body :raw :contains "X-Tine20-Type: Notification" {
        notify :message "there was a notification bounce"
              "mailto:ADMIN_BOUNCE_EMAIL";
    }
} elsif header :contains "X-Tine20-Type" "Notification" {
    redirect :copy "USER_EXTERNAL_EMAIL"; 
} else {
    if header :matches "Subject" "*" {
        set "subject" "${1}";
    }
    if header :matches "From" "*" {
        set "from" "${1}";
    }
    set :encodeurl "message" "TRANSLATE_SUBJECT${from}: ${subject}";
    
    notify :message "TRANSLATE_SUBJECT${from}: ${subject}"
              "mailto:USER_EXTERNAL_EMAIL?body=${message}";
}
sieveFile
                );

                if (true !== Tinebase_FileSystem::getInstance()->fclose($fh)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) {
                        Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                            . ' Could not close defaultForwarding.sieve file');
                    }
                }
            }
        }

        $this->setApplicationVersion('Felamimail', '11.1');
    }

    /**
     * update to 11.2
     *
     * ensure vacation template folder is present
     */
    public function update_1()
    {
        if (Tinebase_Core::isReplicationMaster()) {
            $basepath = Tinebase_FileSystem::getInstance()->getApplicationBasePath(
                'Felamimail',
                Tinebase_FileSystem::FOLDER_TYPE_SHARED
            );
            try {
                Tinebase_FileSystem::getInstance()->stat($basepath . '/Vacation Templates');
            } catch (Tinebase_Exception_NotFound $e) {
                Felamimail_Setup_Initialize::createVacationTemplatesFolder();
            }
        }

        $this->setApplicationVersion('Felamimail', '11.2');
    }

    /**
     * update to 11.3
     *
     * Make vacation reason a longtext field
     */
    public function update_2()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>reason</name>
                <!--Long text!-->
                <length>2147483647</length>
                <type>text</type>
            </field>
        ');

        $this->_backend->alterCol('felamimail_sieve_vacation', $declaration);

        $this->setTableVersion('felamimail_sieve_vacation', 4);
        $this->setApplicationVersion('Felamimail', '11.3');
    }
}
