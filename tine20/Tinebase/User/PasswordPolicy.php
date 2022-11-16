<?php declare(strict_types=1);

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * User Password Policies class
 *
 * @package     Tinebase
 * @subpackage  User
 */
class Tinebase_User_PasswordPolicy
{
    public static function generatePolicyConformPassword(array $policies = []): string
    {
        $chars = [];

        if (!array_key_exists(Tinebase_Config::PASSWORD_POLICY_MIN_LENGTH, $policies)) {
            $policies[Tinebase_Config::PASSWORD_POLICY_MIN_LENGTH] = Tinebase_Config::getInstance()
                ->{Tinebase_Config::USER_PASSWORD_POLICY}->{Tinebase_Config::PASSWORD_POLICY_MIN_LENGTH} ?: 6;
        }

        if (!array_key_exists(Tinebase_Config::PASSWORD_POLICY_MIN_UPPERCASE_CHARS, $policies)) {
            $policies[Tinebase_Config::PASSWORD_POLICY_MIN_UPPERCASE_CHARS] = Tinebase_Config::getInstance()
                ->{Tinebase_Config::USER_PASSWORD_POLICY}->{Tinebase_Config::PASSWORD_POLICY_MIN_UPPERCASE_CHARS};
        }
        static::_addChars($chars, 65, 90, (int)$policies[Tinebase_Config::PASSWORD_POLICY_MIN_UPPERCASE_CHARS]);

        if (!array_key_exists(Tinebase_Config::PASSWORD_POLICY_MIN_SPECIAL_CHARS, $policies)) {
            $policies[Tinebase_Config::PASSWORD_POLICY_MIN_SPECIAL_CHARS] = Tinebase_Config::getInstance()
                ->{Tinebase_Config::USER_PASSWORD_POLICY}->{Tinebase_Config::PASSWORD_POLICY_MIN_SPECIAL_CHARS};
        }
        static::_addChars($chars, 33, 46, (int)$policies[Tinebase_Config::PASSWORD_POLICY_MIN_SPECIAL_CHARS]);

        if (!array_key_exists(Tinebase_Config::PASSWORD_POLICY_MIN_NUMBERS, $policies)) {
            $policies[Tinebase_Config::PASSWORD_POLICY_MIN_NUMBERS] = Tinebase_Config::getInstance()
                ->{Tinebase_Config::USER_PASSWORD_POLICY}->{Tinebase_Config::PASSWORD_POLICY_MIN_NUMBERS};
        }
        static::_addChars($chars, 48, 57, (int)$policies[Tinebase_Config::PASSWORD_POLICY_MIN_NUMBERS]);

        if (!array_key_exists(Tinebase_Config::PASSWORD_POLICY_MIN_WORD_CHARS, $policies)) {
            $policies[Tinebase_Config::PASSWORD_POLICY_MIN_WORD_CHARS] = Tinebase_Config::getInstance()
                ->{Tinebase_Config::USER_PASSWORD_POLICY}->{Tinebase_Config::PASSWORD_POLICY_MIN_WORD_CHARS};
        }
        $length = $policies[Tinebase_Config::PASSWORD_POLICY_MIN_WORD_CHARS]
            + $policies[Tinebase_Config::PASSWORD_POLICY_MIN_UPPERCASE_CHARS]
            + $policies[Tinebase_Config::PASSWORD_POLICY_MIN_SPECIAL_CHARS]
            + $policies[Tinebase_Config::PASSWORD_POLICY_MIN_NUMBERS];
        if ($length < $policies[Tinebase_Config::PASSWORD_POLICY_MIN_LENGTH]) {
            $policies[Tinebase_Config::PASSWORD_POLICY_MIN_WORD_CHARS] +=
                $policies[Tinebase_Config::PASSWORD_POLICY_MIN_LENGTH] - $length;
        }
        static::_addChars($chars, 97, 122, (int)$policies[Tinebase_Config::PASSWORD_POLICY_MIN_WORD_CHARS]);

        shuffle($chars);
        return implode($chars);
    }

    protected static function _addChars(array &$array, int $min, int $max, int $count)
    {
        for ($i = 0; $i < $count; ++$i) {
            $array[] = chr(random_int($min, $max));
        }
    }

    /**
     * ensure password policy
     *
     * @param string $password
     * @param Tinebase_Model_FullUser $user
     * @throws Tinebase_Exception_PasswordPolicyViolation
     */
    public static function checkPasswordPolicy($password, Tinebase_Model_FullUser $user)
    {
        if (! Tinebase_Config::getInstance()->get(Tinebase_Config::USER_PASSWORD_POLICY)->{Tinebase_Config::PASSWORD_POLICY_ACTIVE}) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' No password policy enabled');
            return;
        }

        // we don't count underscores as word chars but as special chars (therefore we can't use \w and \W)
        // TODO make word char / special char regexes configurable?
        $policy = array(
            Tinebase_Config::PASSWORD_POLICY_ONLYASCII              => '/[^\x00-\x7F]/',
            Tinebase_Config::PASSWORD_POLICY_MIN_LENGTH             => null,
            Tinebase_Config::PASSWORD_POLICY_MIN_WORD_CHARS         => '/[^a-z]*/i',
            Tinebase_Config::PASSWORD_POLICY_MIN_UPPERCASE_CHARS    => '/[^A-Z]*/',
            Tinebase_Config::PASSWORD_POLICY_MIN_SPECIAL_CHARS      => '/[a-z0-9äüöß]*/i',
            Tinebase_Config::PASSWORD_POLICY_MIN_NUMBERS            => '/[^0-9]*/',
            Tinebase_Config::PASSWORD_POLICY_FORBID_USERNAME        => $user->accountLoginName,
        );

        $failedTests = array();
        foreach ($policy as $key => $regex) {
            $result = static::_testPolicy($password, $key, $regex);
            if ($result !== true) {
                $failedTests[$key] = $result;
            }
        }

        if (! empty($failedTests)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' ' . print_r($failedTests, true));

            $translation = Tinebase_Translation::getTranslation();
            $msg = $translation->_('Password failed to match the following policy requirements: ');
            foreach($failedTests as $key => $result) {
                $msg .= "\n- " . $result;
            }

            throw new Tinebase_Exception_PasswordPolicyViolation($msg);
        }
    }

    /**
     * test password policy
     *
     * @param string $password
     * @param string $configKey
     * @param string $regex
     * @return mixed
     */
    protected static function _testPolicy($password, $configKey, $regex = null)
    {
        $result = true;

        $configValue = Tinebase_Config::getInstance()->get(Tinebase_Config::USER_PASSWORD_POLICY)->{$configKey};

        $translation = Tinebase_Translation::getTranslation();
        $configDefinition = Tinebase_Config::getInstance()->getDefinition(Tinebase_Config::USER_PASSWORD_POLICY);
        $description = $translation->translate($configDefinition['content'][$configKey]['description']);
        $description = preg_replace("/\.$/", "", $description);

        switch ($configKey) {
            case Tinebase_Config::PASSWORD_POLICY_ONLYASCII:
                if ($configValue && $regex !== null) {
                    $nonAsciiFound = preg_match($regex, $password, $matches);

                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                        __METHOD__ . '::' . __LINE__ . ' ' . print_r($matches, true));

                    $result = ($nonAsciiFound) ? $description : true;
                }

                break;

            case Tinebase_Config::PASSWORD_POLICY_FORBID_USERNAME:
                if ($configValue) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                        __METHOD__ . '::' . __LINE__ . ' Testing if password is part of username "' . $regex . '"');

                    if (! empty($password)) {
                        $result = preg_match('/' . preg_quote($password, '/') . '/i', $regex) ?
                            $description :
                            true;
                    }
                }

                break;

            default:
                // check min length restriction
                $minLength = $configValue;
                if ($minLength > 0) {
                    $reduced = ($regex) ? preg_replace($regex, '', $password) : $password;
                    $charCount = strlen(utf8_decode((string)$reduced));
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . ' Found ' . $charCount . '/' . $minLength . ' chars for ' . $configKey /*. ': ' . $reduced */);

                    if ($charCount < $minLength) {
                        $result = $description . ': ' . $minLength;
                    }
                }

                break;
        }

        return $result;
    }
}
