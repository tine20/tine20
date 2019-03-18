<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Christian Feitl <c.feitl@metaways.de>
 *
 */
class Calendar_Exception_ResourceAdminGrant extends Calendar_Exception
{
    /**
     * the title of the Exception (may be shown in a dialog)
     *
     * @var string
     */
    protected $_title = 'Failed'; // _('Failed')

    /**
     * @see SPL Exception
     */
    protected $message = 'The right Resource Admin must be set once.'; //_('The right Resource Admin must be set once.')

    /**
     * @see SPL Exception
     */
    protected $code = 912;
}