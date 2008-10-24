<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Application
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Interface for an Tine 2.0 application
 *
 * @package     Tinebase
 * @subpackage  Application
 */
interface Tinebase_Application_Frontend_Interface
{
    /**
     * Returns application name
     * 
     * @return string application name
     */
    public function getApplicationName();
}
