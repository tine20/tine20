<?php
/**
 * spam suspicion strategy factory class for the felamimail
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching-En, Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2007-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * spam suspicion strategy factory class for the felamimail
 *
 * @package     Felamimail
 */
class Felamimail_Spam_SuspicionStrategy_Factory
{

    const SUBJECT = 'subject';

    /**
     * @return Felamimail_Spam_SuspicionStrategy_Subject
     * @throws Exception
     */
    public static function factory()
    {
        $felamimailConfig = Felamimail_Config::getInstance();
        $strategy = $felamimailConfig->{Felamimail_Config::SPAM_SUSPICION_STRATEGY};

        switch ($strategy) {
            case self::SUBJECT:

                return new Felamimail_Spam_SuspicionStrategy_Subject($felamimailConfig
                    ->{Felamimail_Config::SPAM_SUSPICION_STRATEGY_CONFIG});
                break;

            default:
                throw new Exception("strategy " . $strategy . " not supported");
                break;
        }
    }
}
