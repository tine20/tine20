<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Transport
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * test mock to use Zend_Mail_Transport_Array
 *
 * @package     Felamimail
 * @subpackage  Transport
 */
class Felamimail_Transport_Array extends Zend_Mail_Transport_Array implements Felamimail_Transport_Interface
{
    use Felamimail_Transport_Trait;

    /**
     * Constructor.
     *
     * @param  string $host OPTIONAL (Default: 127.0.0.1)
     * @param  array|null $config OPTIONAL (Default: null)
     * @return void
     */
    public function __construct($host = '127.0.0.1', Array $config = array())
    {
    }
}
