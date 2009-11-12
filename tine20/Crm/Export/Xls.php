<?php
/**
 * Crm xls generation class
 *
 * @package     Crm
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Ods.php 10912 2009-10-12 14:40:25Z p.schuele@metaways.de $
 * 
 * @todo        make it work
 */

/**
 * Crm xls generation class
 * 
 * @package     Crm
 * @subpackage  Export
 */
class Crm_Export_Xls extends Tinebase_Export_Xls
{
    /**
     * @var string $_applicationName
     */
    protected $_applicationName = 'Crm';
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
    }
    
    /**
     * export records to Xls file
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return void
     */
    public function generate(Tinebase_Model_Filter_FilterGroup $_filter) {
    }
}
