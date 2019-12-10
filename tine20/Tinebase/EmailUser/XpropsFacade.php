<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  EmailUser
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle
 * */

/**
 * xprops facade for accessing email user plugins via records with email userid xprops
 * 
 * @package    Tinebase
 * @subpackage EmailUser
 */
class Tinebase_EmailUser_XpropsFacade
{
    /**
     * record xprops for email user ids (for example in dovecot/postfix sql)
     */
    const XPROP_EMAIL_USERID_IMAP = 'emailUserIdImap';
    const XPROP_EMAIL_USERID_SMTP = 'emailUserIdSmtp';

    public static function getEmailUserFromRecord($record, $propertyConfig = [], $setUserId = true)
    {
        $emailUserProperties = [
            'email' => null,
            'password' => null,
            'user_id' => null,
        ];

        foreach ($emailUserProperties as $property => &$value) {
            if ($property === 'user_id' && Tinebase_Config::getInstance()->{Tinebase_Config::EMAIL_USER_ID_IN_XPROPS}) {
                $value = isset($record->xprops()[self::XPROP_EMAIL_USERID_IMAP])
                    ? $record->xprops()[self::XPROP_EMAIL_USERID_IMAP]
                    : null;
            } else {
                $value = isset($propertyConfig[$property])
                    ? ($record->has($propertyConfig[$property]) ? $record->{$propertyConfig[$property]} : null)
                    : ($record->has($property) ? $record->{$property} : null);
            }
        }

        $user = new Tinebase_Model_FullUser([
            'accountLoginName' => $emailUserProperties['email'],
            'accountEmailAddress' => $emailUserProperties['email'],
        ], true);

        $emailData = $emailUserProperties['password'] ? [
            'emailPassword' => $emailUserProperties['password']
        ] : [];
        $user->imapUser = new Tinebase_Model_EmailUser($emailData);
        $user->smtpUser = new Tinebase_Model_EmailUser($emailData);

        if ($setUserId) {
            $user->setId($emailUserProperties['user_id']);
        }
        return $user;
    }

    public static function setXprops($record, $userId = null)
    {
        if (! $userId) {
            $userId = Tinebase_Record_Abstract::generateUID();
        }

        $record->xprops()[Felamimail_Model_Account::XPROP_EMAIL_USERID_IMAP] = $userId;
        $record->xprops()[Felamimail_Model_Account::XPROP_EMAIL_USERID_SMTP] = $userId;
    }

    public static function deleteEmailUsers($record)
    {
        $user = self::getEmailUserFromRecord($record);
        Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP)->inspectDeleteUser($user);
        Tinebase_EmailUser::getInstance(Tinebase_Config::SMTP)->inspectDeleteUser($user);
    }

    public static function updateEmailUsers($record)
    {
        $user = self::getEmailUserFromRecord($record);
        Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP)->inspectUpdateUser($user, $user);
        Tinebase_EmailUser::getInstance(Tinebase_Config::SMTP)->inspectUpdateUser($user, $user);
    }
}
