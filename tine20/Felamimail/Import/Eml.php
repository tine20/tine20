<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Christian Feitl <c.feitl@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Import class for the Felamimail
 *
 * @package     Felamimail
 * @subpackage  Import
 */
class Felamimail_Import_Eml
{
    public function importEmlEmail($password = null)
    {
        $importDir = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR
            . 'Felamimail' . DIRECTORY_SEPARATOR . 'Setup' . DIRECTORY_SEPARATOR . 'DemoData' . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'Message';

        $result = $this->_dirToArray($importDir);
        $importUserCsv = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'Admin' . DIRECTORY_SEPARATOR . 'Setup'
            . DIRECTORY_SEPARATOR . 'DemoData' . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'User';
        $files = array_diff(scandir($importUserCsv), array('..', '.'));
        $rows = [];
        foreach ($files as $file) {
            $open = fopen($importUserCsv . DIRECTORY_SEPARATOR . $file, 'r');
            while (($row = fgetcsv($open)) !== false) {
                $rows[$row[2]]= $row[3];
            }
        }

        foreach ($result as $userName => $folders) {
            // get User
            try {
                $user = Tinebase_User::getInstance()->getFullUserByLoginName($userName);
                $account = Admin_Controller_EmailAccount::getInstance()->getSystemAccount($user->getId());

                $emailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);
                $emailUser = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord($user);
                // always set defined username
                $account->user = $emailUserBackend->getLoginName($emailUser->getId(), $account->email, $account->email);


                //load user password
                if($password) {
                    $account->password = $password;
                } else {
                    $account->password = array_key_exists($userName, $rows)? $rows[$userName]: '';
                }
                foreach ($folders as $folder => $files) {
                    // create/get folder
                    try {
                        $mailFolder = Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName($account, $folder);
                    } catch (Exception $e) {
                        //log folder not exist
                        $mailFolder = Felamimail_Controller_Folder::getInstance()->create($account, $folder);
                    }
                    $mailFolder->account_id = $account;
                    foreach ($files as $file) {
                        // import all emls (before twig)
                        $message = fopen($importDir . DIRECTORY_SEPARATOR . $userName . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $file, 'r');
                        Felamimail_Controller_Message::getInstance()->appendMessage($mailFolder, $message);
                    }
                }
            } catch (Exception $e) {
                //user not found
            }
        }
    }

    /**
     * @param string $_dir
     * @return array
     */
    protected function _dirToArray(string $_dir)
    {
        $result = array();
        $cdir = scandir($_dir);
        foreach ($cdir as $key => $value) {
            if (!in_array($value, array(".", ".."))) {
                if (is_dir($_dir . DIRECTORY_SEPARATOR . $value)) {
                    $result[$value] = $this->_dirToArray($_dir . DIRECTORY_SEPARATOR . $value);
                } else {
                    $result[] = $value;
                }
            }
        }
        return $result;
    }
}
