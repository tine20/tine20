<?php
/**
 * backend factory class for the Setup
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 
 * @package     Setup
 */
class Setup_Backend_Schema_Table_Factory
{
  /**
     * factory function to return a selected 
     *
     * @param string $type
     * @return object
     */
    static public function factory($_type, $_definition)
    {
        // legacy for old setup scripts
        if (ucfirst($_type) == 'String') {
            $_type = 'Xml';
        }
        $className = 'Setup_Backend_Schema_Table_' . ucfirst($_type);
        $instance = new $className($_definition);
                      
        return $instance;
    }

    /**
     * returns default simple record table
     *
     * @param $tablename
     * @return object
     */
    static public function getSimpleRecordTable($tablename)
    {
        return self::factory('String', '
         <table>
            <name>' . $tablename . '</name>
            <engine>InnoDB</engine>
            <charset>utf8</charset>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>name</name>
                    <type>text</type>
                    <length>256</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>description</name>
                    <type>text</type>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>created_by</name>
                    <type>text</type>
                    <length>40</length>
                </field>
                <field>
                    <name>creation_time</name>
                    <type>datetime</type>
                </field>
                <field>
                    <name>last_modified_by</name>
                    <type>text</type>
                    <length>40</length>
                </field>
                <field>
                    <name>last_modified_time</name>
                    <type>datetime</type>
                </field>
                <field>
                    <name>is_deleted</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>
                <field>
                    <name>deleted_by</name>
                    <type>text</type>
                    <length>40</length>
                </field>
                <field>
                    <name>deleted_time</name>
                    <type>datetime</type>
                </field>
                <field>
                    <name>seq</name>
                    <type>integer</type>
                    <notnull>true</notnull>
                    <default>0</default>
                </field>
            </declaration>
        </table>
        ');
    }
}    
