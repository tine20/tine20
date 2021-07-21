<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Timetracker
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Timetracker_JsonTest
 */
class Timetracker_DoctrineModelTest extends TestCase
{

    protected function setUp(): void
{
    }

    public function testTimesheetTimaccountForeignKey()
    {
        $em = Setup_SchemaTool::getEntityManager();

        // check if association is set up correctly
        $tsMetadata = $em->getClassMetadata('Timetracker_Model_Timesheet');
        self::assertEquals('Doctrine\ORM\Mapping\ClassMetadata', get_class($tsMetadata));
        self::assertTrue($tsMetadata->hasAssociation('timeaccount_id'), 'association missing: ' . var_export($tsMetadata->getAssociationMappings(), true));
        $mapping = $tsMetadata->getAssociationMapping('timeaccount_id');
        self::assertEquals(\Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE, $mapping['type']);

        // check mysql schema
        $tool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $classes = Setup_SchemaTool::getMetadata(array('Timetracker_Model_Timeaccount', 'Timetracker_Model_Timesheet'));
        $schema = $tool->getSchemaFromMetadata($classes);
        $sql = $schema->toSql($em->getConnection()->getDatabasePlatform());
        self::assertEquals(3, count($sql));
        self::assertStringContainsString('CREATE TABLE `tine20_timetracker_timeaccount`', $sql[0], print_r($sql, true));
        self::assertStringContainsString('CREATE TABLE `tine20_timetracker_timesheet`', $sql[1], print_r($sql, true));
        self::assertStringContainsString('ALTER TABLE `tine20_timetracker_timesheet` ADD CONSTRAINT', $sql[2], print_r($sql, true));
        self::assertStringContainsString('FOREIGN KEY (`timeaccount_id`) REFERENCES `tine20_timetracker_timeaccount` (`id`)', $sql[2], print_r($sql, true));
    }
}
