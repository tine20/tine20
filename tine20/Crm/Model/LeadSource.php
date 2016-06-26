<?php
/**
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * lead source record
 * 
 * @package     Crm
 */
class Crm_Model_LeadSource extends Tinebase_Config_KeyFieldRecord
{
    protected $_additionalValidators = array(
        'archived'                => array('allowEmpty' => true         ),
    );
}