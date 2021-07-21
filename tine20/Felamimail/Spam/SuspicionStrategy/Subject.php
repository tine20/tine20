<?php
/**
 * spam suspicion strategy subject class for the felamimail
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching-En, Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2007-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * spam suspicion strategy subject class for the felamimail
 *
 * @package     Felamimail
 */
class Felamimail_Spam_SuspicionStrategy_Subject implements Felamimail_Spam_SuspicionStrategy_Interface
{
    /**
     * strategy pattern content
     *
     * @var string
     */
    private $_pattern;

    /**
     * construct suspicion strategy subject
     * @param array $options
     * @throws Exception
     */
    public function __construct(array $options)
    {
        if (!isset($options['pattern'])) {
            throw new Exception("spam suspicion strategy config 'pattern' doesn't have content");
        }

        $this->_pattern = $options['pattern'];
    }

    /**
     * @param Felamimail_Model_Message $message
     * @return bool|mixed
     */
    public function apply(Felamimail_Model_Message $message)
    {
        if (preg_match($this->_pattern, $message->subject)) {
            return true;
        }

        return false;
    }
}
