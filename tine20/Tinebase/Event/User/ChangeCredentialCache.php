<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Event
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * event class for change credential cache
 *
 * @package     Tinebase
 * @subpackage  Event
 */
class Tinebase_Event_User_ChangeCredentialCache extends Tinebase_Event_Abstract
{
    /**
     * the old credential cache
     *
     * @var Tinebase_Model_CredentialCache
     */
    public $oldCredentialCache;
    
    /**
     * the constructor
     * 
     * @param Tinebase_Model_CredentialCache $_credentialCache
     */
    public function __construct(Tinebase_Model_CredentialCache $_oldCredentialCache = NULL)
    {
        if ($_oldCredentialCache !== NULL) {
            $this->oldCredentialCache = $_oldCredentialCache;
        }
    }
}
