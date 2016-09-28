<?php
/**
 * Tine 2.0
 *
 * @package     MailFiler
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 *
 */

/**
 * Copy or move into same folder
 *
 * @package     MailFiler
 * @subpackage  Exception
 */
class MailFiler_Exception_DestinationIsSameNode extends MailFiler_Exception
{
    /**
     * the title of the Exception (may be shown in a dialog)
     *
     * @var string
     */
    protected $_title = 'Destination has the same Path'; // _('Destination has the same Path')
    
    /**
     * @see SPL Exception
     */
    protected $message = 'The destination has the same path the source has. No action has been performed.'; // _('The destination has the same path the source has. No action has been performed.')
    
    /**
     * @see SPL Exception
    */
    protected $code = 903;
}
