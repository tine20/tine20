<?php
/**
 * Tine 2.0
 *
 * @package     Sipgate
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */

/**
 * Sipgate updates for version 2.1
 *
 * @package     Sipgate
 * @subpackage  Setup
 */
class Sipgate_Setup_Update_Release2 extends Setup_Update_Abstract
{

    /**
     * update to 8.2
     * - update 256 char fields
     * 
     * @see 0008070: check index lengths
     */
    public function update_0()
    {
        $columns = array("sipgate_line" => array(
                        "uri_alias" => "",
                        "sip_uri" => "",
                        "e164_out" => "",
                        "e164_in" => ""
                    ),
                    "sipgate_connection" => array(
                        "tarif" => "",
                        "local_uri" => "true",
                        "remote_uri" => "true"
                    ),
                );
        
        $this->truncateTextColumn($columns, 255);
        $this->setTableVersion('sipgate_line', 1);
        $this->setTableVersion('sipgate_connection', 1);
        $this->setApplicationVersion('Sipgate', '2.1');
    }
}
