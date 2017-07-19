<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Tinebase_Auth_SecondFactor_Tine20 extends Tinebase_Auth_SecondFactor_Abstract
{
    /**
     * validate second factor
     *
     * @param string $username
     * @param string $password
     * @param boolean $allowEmpty
     * @return integer (Tinebase_Auth::FAILURE|Tinebase_Auth::SUCCESS)
     */
    public function validate($username, $password, $allowEmpty = false)
    {
        if (! $allowEmpty && empty($password)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' Empty password given');
            return Tinebase_Auth::FAILURE;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' Options: ' . print_r($this->_options, true));

        $instance = new Tinebase_Auth_Sql(
            Tinebase_Core::getDb(),
            SQL_TABLE_PREFIX . 'accounts',
            'login_name',
            'pin'
        );
        $instance->setIdentity($username);
        $instance->setCredential($password);

        $result = $instance->authenticate();
        if ($result->isValid()) {
            return Tinebase_Auth::SUCCESS;
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' Auth failure! ' . print_r($result->getMessages(), true));
            return Tinebase_Auth::FAILURE;
        }
    }
}
