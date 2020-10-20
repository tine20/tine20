<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Inventory
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 */

use Doctrine\DBAL\Schema\Comparator;

/**
 * Test class for Inventory_JsonTest
 */
class Inventory_DoctrineModelTest extends Inventory_TestCase
{

    protected function setUp(): void
{
    }

    public function testGetMetadataOfInventoryModel()
    {
        $em = Setup_SchemaTool::getEntityManager('Inventory', array('Inventory_Model_InventoryItem'));

        $invItemMetadata = $em->getClassMetadata('Inventory_Model_InventoryItem');

        $this->assertEquals('Doctrine\ORM\Mapping\ClassMetadata', get_class($invItemMetadata));
        $this->assertTrue($invItemMetadata->hasField('name'));

        $mapping = $invItemMetadata->getFieldMapping('name');
        $this->assertEquals('string', $mapping['type']);
    }

    public function testExplicitRenameProblemExists()
    {
        $em = Setup_SchemaTool::getEntityManager('Inventory');
        $sm = $em->getConnection()->getSchemaManager();

        // NOTE: the DBAL schema is stateless and 'just' describes a schema in a plattform independend way
        //       thus, all schema upgrade is based on schema comparisim
        $fromSchema = $sm->createSchema();
        $toSchema = clone $fromSchema;

        $table = $toSchema->getTable('tine20_inventory_item');

        $this->expectException('Doctrine\DBAL\DBALException');
        $table->renameColumn('id', 'ident');
    }

    public function testExplicitRename()
    {
        $this->markTestSkipped('evaluate concept for explicit field rename with doctrine2 schema tool');
        $em = Setup_SchemaTool::getEntityManager('Inventory');
        $sm = $em->getConnection()->getSchemaManager();

        // NOTE: the DBAL schema is stateless and 'just' describes a schema in a plattform independend way
        //       thus, all schema upgrade is based on schema comparisim

        $fromSchema = $sm->createSchema();
        $toSchema = clone $fromSchema;

        $table = $toSchema->getTable('tine20_inventory_item');


        // workaround -> might have problems?!
        $col = $table->getColumn('id');
        $table->dropColumn('id');
        $table->addColumn('ident', $col->getType()->getName(), $col->toArray());

        // better create, copy, delete?
        // @TODO ask some insider
        //  ? Schema tool can't rename cols, but schema diff with compare (at least with mysql plattform) alters table name correctly when col is renamed in annotations

        // non rename updates are a lot more easy
        $table->changeColumn('name', array(
            'length' => 200,
        ));

        $comparator = new Comparator();
        $schemaDiff = $comparator->compare($fromSchema, $toSchema);

        $updateSql = $schemaDiff->toSql($em->getConnection()->getDatabasePlatform());
//        print_r($updateSql);
    }
}
