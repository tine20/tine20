<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2017-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */


/**
 * Dummy Controller, to be used for example by queue with action like 'Tinebase_FOO_DummyController.someMethod'
 */
class Tinebase_DummyController
{
    public static function getInstance()
    {
        return new self();
    }

    public function sleepNSec($n)
    {
        /*if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' start sleeping...');*/

        sleep($n);
        file_put_contents('/var/run/tine20/DummyController.txt', 'success ' . $n);

        /*if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' done');*/

        return true;
    }
}