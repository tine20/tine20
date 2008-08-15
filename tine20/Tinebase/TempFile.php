<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * class of persistant temp files
 * 
 * This class handles generation of tempfiles and registeres them in a tempFile table.
 * To access a tempFile, the session of the client must match
 * 
 * @todo check user instead of session?
 * @todo automatic garbage collection via cron
 * @todo implement :-) move stuff from Tinebase_Controller / Addressbook_Controller / Tinebase_Http
 *
 */
class Tinebase_TempFile
{
    public function getTempFile()
    {
        
    }
}
?>