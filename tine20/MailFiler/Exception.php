<?php
/**
 * Tine 2.0
 * 
 * @package     MailFiler
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

/**
 * MailFiler exception
 * 
 * @package     MailFiler
 * @subpackage  Exception
 */
class MailFiler_Exception extends Tinebase_Exception
{
    /**
     * the name of the application, this exception belongs to
     *
     * @var string
     */
    protected $_appName = 'MailFiler';
}
