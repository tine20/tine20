<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Transport
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * mail transport for Felamimail
 * - extended Zend_Mail_Transport_Smtp, added getBody/getHeaders and use these for appendMessage / sendMessage
 *
 * @package     Felamimail
 * @subpackage  Transport
 */
class Felamimail_Transport extends Zend_Mail_Transport_Smtp implements Felamimail_Transport_Interface
{
    use Felamimail_Transport_Trait;

    /**
     * @var Zend_Mail_Transport_Abstract
     */
    protected static $_testTransport = null;

    /**
     * @param Zend_Mail_Transport_Abstract|null $transport
     * @return Zend_Mail_Transport_Abstract
     */
    public static function setTestTransport(Zend_Mail_Transport_Abstract $transport = null)
    {
        $oldTransport = static::$_testTransport;
        static::$_testTransport = $transport;

        return $oldTransport;
    }

    /**
     * @param $host
     * @param $config
     * @return Felamimail_Transport|Zend_Mail_Transport_Abstract
     */
    public static function getNewInstance($host, $config)
    {
        if (null !== static::$_testTransport) {
            return static::$_testTransport;
        }

        return new self($host, $config);
    }

    /**
     * Constructor.
     *
     * @param  string $host OPTIONAL (Default: 127.0.0.1)
     * @param  array|null $config OPTIONAL (Default: null)
     * @return void
     */
    public function __construct($host = '127.0.0.1', Array $config = array())
    {
        // remove empty helo dns name to force default (localhost)
        if (empty($config['name'])) {
            unset($config['name']);
        }

        $config['connectionOptions'] = Tinebase_Mail::getConnectionOptions();
        parent::__construct($host, $config);
    }
}
