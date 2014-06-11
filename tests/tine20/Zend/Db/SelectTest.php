<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Zend_Db_Select
 */
class Zend_Db_SelectTest extends TestCase
{
    /**
     * testOrderBySqlInjection
     * 
     * @see 0009982: fix Zend_Db_Expr handling in select order clause
     */
    public function testOrderBySqlInjection()
    {
        $select = Tinebase_Core::getDb()->select();
        
        if (Tinebase_Core::getDb() instanceof Zend_Db_Adapter_Pdo_Mysql) {
            $select->order('id; SLEEP(1)');
            $this->assertEquals(' ORDER BY `id; SLEEP(1)` ASC', $select->__toString());
        } else if (Tinebase_Core::getDb() instanceof Zend_Db_Adapter_Pdo_Pgsql) {
            $select->order('id; SELECT PG_SLEEP(5)');
            $this->assertEquals(' ORDER BY `id; SELECT PG_SLEEP(5)` ASC', $select->__toString());
        } else {
            $this->markTestSkipped('no test for this adapter yet');
        }
    }
}
