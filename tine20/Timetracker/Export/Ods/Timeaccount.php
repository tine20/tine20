<?php
/**
 * Timeaccount Ods generation class
 *
 * @package     Timetracker
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * Timetracker Ods generation class
 * 
 * @package     Timetracker
 * @subpackage  Export
 */
class Timetracker_Export_Ods_Timeaccount extends Tinebase_Export_Ods
{
    /**
     * default export definition name
     * 
     * @var string
     */
    protected $_defaultExportname = 'ta_default_ods';
        
    /**
     * @var string application of this export class
     */
    protected $_applicationName = 'Timetracker';
}
