<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

class Sales_Setup_Update_Release2 extends Setup_Update_Abstract
{
    /**
     * renamed erp to sales
     * 
     */
    public function update_0()
    {
        $this->renameTable('erp_numbers', 'sales_numbers');
        $this->renameTable('erp_contracts', 'sales_contracts');
        
        $this->setApplicationVersion('Sales', '2.1');
    }
}
