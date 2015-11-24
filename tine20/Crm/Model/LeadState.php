<?php
/**
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * lead state record
 * 
 * @package     Crm
 */
class Crm_Model_LeadState extends Tinebase_Config_KeyFieldRecord
{
    protected $_additionalValidators = array(
        'probability'             => array('allowEmpty' => true         ),
        'endslead'                => array('allowEmpty' => true         ),
    );
}