<?php
/**
 * Tine 2.0
 * @package     MailFiler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Cli frontend for MailFiler
 *
 * This class handles cli requests for the MailFiler
 *
 * @package     MailFiler
 */
class MailFiler_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     * 
     * @var string
     */
    protected $_applicationName = 'MailFiler';
}
