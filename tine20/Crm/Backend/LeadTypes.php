<?php
/**
 * Tine 2.0
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */


/**
 * interface for lead types class
 *
 * @package     Crm
 */
class Crm_Backend_LeadTypes extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'metacrm_leadtype';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Crm_Model_Leadtype';
}
