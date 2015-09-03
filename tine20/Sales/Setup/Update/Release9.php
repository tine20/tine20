<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Sales_Setup_Update_Release9 extends Setup_Update_Abstract
{
    /**
     * update to 9.1
     * - Add price_gross2 and price_total to purchase invoice
     */
    public function update_0()
    {
        if ($this->getTableVersion('sales_purchase_invoices') < 3) {
            $release8 = new Sales_Setup_Update_Release8($this->_backend);
            $release8->update_30();
        }
        $this->setApplicationVersion('Sales', '9.1');
    }
}
