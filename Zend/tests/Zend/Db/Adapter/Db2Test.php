<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Db
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @see Zend_Db_Adapter_TestCommon
 */
require_once 'Zend/Db/Adapter/TestCommon.php';

/**
 * @see Zend_Db_Adapter_Db2
 */
require_once 'Zend/Db/Adapter/Db2.php';

PHPUnit_Util_Filter::addFileToFilter(__FILE__);

class Zend_Db_Adapter_Db2Test extends Zend_Db_Adapter_TestCommon
{

    protected $_numericDataTypes = array(
        Zend_Db::INT_TYPE    => Zend_Db::INT_TYPE,
        Zend_Db::BIGINT_TYPE => Zend_Db::BIGINT_TYPE,
        Zend_Db::FLOAT_TYPE  => Zend_Db::FLOAT_TYPE,
        'INTEGER'            => Zend_Db::INT_TYPE,
        'SMALLINT'           => Zend_Db::INT_TYPE,
        'BIGINT'             => Zend_Db::BIGINT_TYPE,
        'DECIMAL'            => Zend_Db::FLOAT_TYPE,
        'NUMERIC'            => Zend_Db::FLOAT_TYPE
    );

    public function testAdapterDescribeTablePrimaryAuto()
    {
        $desc = $this->_db->describeTable('zfbugs');

        $this->assertTrue($desc['bug_id']['PRIMARY']);
        $this->assertEquals(1, $desc['bug_id']['PRIMARY_POSITION']);
        $this->assertTrue($desc['bug_id']['IDENTITY']);
    }

    public function testAdapterDescribeTableAttributeColumn()
    {
        $desc = $this->_db->describeTable('zfproducts');

        $this->assertEquals('zfproducts',        $desc['product_name']['TABLE_NAME'], 'Expected table name to be zfproducts');
        $this->assertEquals('product_name',      $desc['product_name']['COLUMN_NAME'], 'Expected column name to be product_name');
        $this->assertEquals(2,                   $desc['product_name']['COLUMN_POSITION'], 'Expected column position to be 2');
        $this->assertRegExp('/varchar/i',        $desc['product_name']['DATA_TYPE'], 'Expected data type to be VARCHAR');
        $this->assertEquals('',                  $desc['product_name']['DEFAULT'], 'Expected default to be empty string');
        $this->assertTrue(                       $desc['product_name']['NULLABLE'], 'Expected product_name to be nullable');
        $this->assertEquals(0,                   $desc['product_name']['SCALE'], 'Expected scale to be 0');
        $this->assertEquals(0,                   $desc['product_name']['PRECISION'], 'Expected precision to be 0');
        $this->assertFalse(                      $desc['product_name']['PRIMARY'], 'Expected product_name not to be a primary key');
        $this->assertNull(                       $desc['product_name']['PRIMARY_POSITION'], 'Expected product_name to return null for PRIMARY_POSITION');
        $this->assertFalse(                      $desc['product_name']['IDENTITY'], 'Expected product_name to return false for IDENTITY');
    }

    public function testAdapterDescribeTablePrimaryKeyColumn()
    {
        $desc = $this->_db->describeTable('zfproducts');

        $this->assertEquals('zfproducts',        $desc['product_id']['TABLE_NAME'], 'Expected table name to be zfproducts');
        $this->assertEquals('product_id',        $desc['product_id']['COLUMN_NAME'], 'Expected column name to be product_id');
        $this->assertEquals(1,                   $desc['product_id']['COLUMN_POSITION'], 'Expected column position to be 1');
        $this->assertEquals('',                  $desc['product_id']['DEFAULT'], 'Expected default to be empty string');
        $this->assertFalse(                      $desc['product_id']['NULLABLE'], 'Expected product_id not to be nullable');
        $this->assertEquals(0,                   $desc['product_id']['SCALE'], 'Expected scale to be 0');
        $this->assertEquals(0,                   $desc['product_id']['PRECISION'], 'Expected precision to be 0');
        $this->assertTrue(                       $desc['product_id']['PRIMARY'], 'Expected product_id to be a primary key');
        $this->assertEquals(1,                   $desc['product_id']['PRIMARY_POSITION']);
    }

    /**
     * Used by _testAdapterOptionCaseFoldingNatural()
     * DB2 and Oracle return identifiers in uppercase naturally,
     * so those test suites will override this method.
     */
    protected function _testAdapterOptionCaseFoldingNaturalIdentifier()
    {
        return 'CASE_FOLDED_IDENTIFIER';
    }

    public function testAdapterTransactionCommit()
    {
        $bugs = $this->_db->quoteIdentifier('zfbugs');
        $bug_id = $this->_db->quoteIdentifier('bug_id');

        // use our default connection as the Connection1
        $dbConnection1 = $this->_db;
        
        // create a second connection to the same database
        $dbConnection2 = Zend_Db::factory($this->getDriver(), $this->_util->getParams());
        $dbConnection2->getConnection();
        $dbConnection2->query('SET ISOLATION LEVEL = UR');
        
        // notice the number of rows in connection 2
        $count = $dbConnection2->fetchOne("SELECT COUNT(*) FROM $bugs");
        $this->assertEquals(4, $count, 'Expecting to see 4 rows in bugs table (step 1)');

        // start an explicit transaction in connection 1
        $dbConnection1->beginTransaction();

        // delete a row in connection 1
        $rowsAffected = $dbConnection1->delete(
            'zfbugs',
            "$bug_id = 1"
        );
        $this->assertEquals(1, $rowsAffected);

        // we should see one less row in connection 2
        // because it is doing an uncommitted read
        $count = $dbConnection2->fetchOne("SELECT COUNT(*) FROM $bugs");
        $this->assertEquals(3, $count, 'Expecting to see 3 rows in bugs table (step 2) because conn2 is doing an uncommitted read');

        // commit the DELETE
        $dbConnection1->commit();

        // now we should see one fewer rows in connection 2
        $count = $dbConnection2->fetchOne("SELECT COUNT(*) FROM $bugs");
        $this->assertEquals(3, $count, 'Expecting to see 3 rows in bugs table after DELETE (step 3)');

        // delete another row in connection 1
        $rowsAffected = $dbConnection1->delete(
            'zfbugs',
            "$bug_id = 2"
        );
        $this->assertEquals(1, $rowsAffected);

        // we should see results immediately, because
        // the db connection returns to auto-commit mode
        $count = $dbConnection2->fetchOne("SELECT COUNT(*) FROM $bugs");
        $this->assertEquals(2, $count);
    }

    public function testAdapterTransactionRollback()
    {
        $bugs = $this->_db->quoteIdentifier('zfbugs');
        $bug_id = $this->_db->quoteIdentifier('bug_id');

        // use our default connection as the Connection1
        $dbConnection1 = $this->_db;

        // create a second connection to the same database
        $dbConnection2 = Zend_Db::factory($this->getDriver(), $this->_util->getParams());
        $dbConnection2->getConnection();
        $dbConnection2->query('SET ISOLATION LEVEL = UR');
        
        // notice the number of rows in connection 2
        $count = $dbConnection2->fetchOne("SELECT COUNT(*) FROM $bugs");
        $this->assertEquals(4, $count, 'Expecting to see 4 rows in bugs table (step 1)');

        // start an explicit transaction in connection 1
        $dbConnection1->beginTransaction();

        // delete a row in connection 1
        $rowsAffected = $dbConnection1->delete(
            'zfbugs',
            "$bug_id = 1"
        );
        $this->assertEquals(1, $rowsAffected);

        // we should see one less row in connection 2
        // because it is doing an uncommitted read
        $count = $dbConnection2->fetchOne("SELECT COUNT(*) FROM $bugs");
        $this->assertEquals(3, $count, 'Expecting to see 3 rows in bugs table (step 2) because conn2 is doing an uncommitted read');

        // rollback the DELETE
        $dbConnection1->rollback();

        // now we should see the same number of rows
        // because the DELETE was rolled back
        $count = $dbConnection2->fetchOne("SELECT COUNT(*) FROM $bugs");
        $this->assertEquals(4, $count, 'Expecting to still see 4 rows in bugs table after DELETE is rolled back (step 3)');

        // delete another row in connection 1
        $rowsAffected = $dbConnection1->delete(
            'zfbugs',
            "$bug_id = 2"
        );
        $this->assertEquals(1, $rowsAffected);

        // we should see results immediately, because
        // the db connection returns to auto-commit mode
        $count = $dbConnection2->fetchOne("SELECT COUNT(*) FROM $bugs");
        $this->assertEquals(3, $count, 'Expecting to see 3 rows in bugs table after DELETE (step 4)');
    }

    public function getDriver()
    {
        return 'Db2';
    }

}
