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

    /**
     * @param Tinebase_Record_Abstract $record
     * @param array $propertyConfig
     * @param bool $setUserId
     * @return Tinebase_Model_FullUser
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    public static function getEmailUserFromRecord($record, $propertyConfig = [], $setUserId = true)
    {
        $emailUserProperties = [
            'email' => null,
            'password' => null,
        ];

        foreach ($emailUserProperties as $property => &$value) {
            $value = isset($propertyConfig[$property])
                ? ($record->has($propertyConfig[$property]) ? $record->{$propertyConfig[$property]} : null)
                : ($record->has($property) ? $record->{$property} : null);
        }

        if ($record instanceof Felamimail_Model_Account) {
            $user = new Tinebase_Model_FullUser([
                'accountLoginName' => $record->email_imap_user['emailUsername'] ?? $emailUserProperties['email'],
                'accountEmailAddress' => $emailUserProperties['email'],
            ], true);

            if (! $record->email_imap_user) {
                $record->email_imap_user = [];
            }
            $user->imapUser = $record->email_imap_user instanceof Tinebase_Model_EmailUser
                ? $record->email_imap_user
                : new Tinebase_Model_EmailUser($record->email_imap_user);

            if (! $record->email_smtp_user) {
                $record->email_smtp_user = [];
            }
            $user->smtpUser = $record->email_smtp_user instanceof Tinebase_Model_EmailUser
                ? $record->email_imap_user
                : new Tinebase_Model_EmailUser($record->email_smtp_user);

        } else {
            $user = new Tinebase_Model_FullUser([
                'accountLoginName' => $emailUserProperties['email'],
                'accountEmailAddress' => $emailUserProperties['email'],
            ], true);

            $user->imapUser = new Tinebase_Model_EmailUser([]);
            $user->smtpUser = new Tinebase_Model_EmailUser([]);
        }

        if ($emailUserProperties['password']) {
            $user->imapUser->emailPassword = $emailUserProperties['password'];
        }

        if ($setUserId) {
            if (Tinebase_Config::getInstance()->{Tinebase_Config::EMAIL_USER_ID_IN_XPROPS}) {
                self::setIdFromXprops($record, $user);
            } else {
                // property config might already contain the ID
                $user_id = isset($propertyConfig['user_id'])
                    ? ($record->has($propertyConfig['user_id']) ? $record->{$propertyConfig['user_id']} : $propertyConfig['user_id'])
                    : ($record->has('user_id') ? $record->user_id : $record->getId());
                $user->setId($user_id);
            }
        }

        return $user;
    }

    /**
     * @param Tinebase_Record_Interface $record
     * @param Tinebase_Model_FullUser $user
     * @param boolean $createIfEmpty
     * @param string $xprop
     */
    public static function setIdFromXprops($record, $user, $createIfEmpty = false, $xprop = self::XPROP_EMAIL_USERID_IMAP)
    {
        $user_id = self::getEmailUserId($record, $xprop, false);
        if ($user_id === null && $createIfEmpty) {
            $user_id = self::setXprops($record);
        }
        $user->setId($user_id);
    }

    public static function setXprops($record, $userId = null, $createIfEmpty = true)
    {
        if (! $userId && $createIfEmpty) {
            $userId = Tinebase_Record_Abstract::generateUID();
        }

        $record->xprops()[self::XPROP_EMAIL_USERID_IMAP] = $userId;
        $record->xprops()[self::XPROP_EMAIL_USERID_SMTP] = $userId;

        return $userId;
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

    /**
     * @param Tinebase_Record_Interface $record
     * @param string $xprop
     * @param boolean $userIdAsFallback
     * @return string
     */
    public static function getEmailUserId($record, $xprop = self::XPROP_EMAIL_USERID_IMAP, $userIdAsFallback = true)
    {
        if (Tinebase_Config::getInstance()->{Tinebase_Config::EMAIL_USER_ID_IN_XPROPS}) {
            $result = isset($record->xprops()[$xprop])
                ? $record->xprops()[$xprop]
                : ($userIdAsFallback ? $record->getId() : null);
        } else {
            // TODO support other id properties?
            $result = $record->getId();
        }

        return $result;
    }
}
