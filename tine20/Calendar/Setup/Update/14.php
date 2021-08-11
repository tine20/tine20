<?php

/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2021.11 (ONLY!)
 */
class Calendar_Setup_Update_14 extends Setup_Update_Abstract
{
    const RELEASE014_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE014_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE014_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE014_UPDATE003 = __CLASS__ . '::update003';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE014_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE014_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE => [
            self::RELEASE014_UPDATE001 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update001',
            ],
            self::RELEASE014_UPDATE003 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update003',
            ],
        ]
    ];

    public function update000()
    {
        $this->addApplicationUpdate('Calendar', '14.0', self::RELEASE014_UPDATE000);
    }

    public function update001()
    {
        if (! $this->_backend->columnExists('color', 'cal_resources')) {
            $this->_backend->addCol('cal_resources', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>color</name>
                    <type>text</type>
                    <length>7</length>
                </field>'));
        }

        if ($this->getTableVersion('cal_resources') < 8) {
            $this->setTableVersion('cal_resources', 8);
        }

        $this->addApplicationUpdate('Calendar', '14.1', self::RELEASE014_UPDATE001);
    }
    
    public function update002()
    {
        $attendeeKeyField = Calendar_Config::getInstance()->{Calendar_Config::ATTENDEE_ROLES};
        
        $req = $attendeeKeyField->records->find('id', 'REQ');
        $req->system = true;
        $req->order = $req->order ?: 0;
        $req->color = $req->color ?: '#FF0000';

        $opt = $attendeeKeyField->records->find('id', 'OPT');
        $opt->system = true;
        $opt->order = $opt->order ?: 1;
        $opt->color = $opt->color ?: '#0000FF';

        $attendeeKeyField->records->sort('order', 'ASC', 'asort', SORT_NUMERIC);

        Calendar_Config::getInstance()->{Calendar_Config::ATTENDEE_ROLES} = $attendeeKeyField;
        
        $this->addApplicationUpdate('Calendar', '14.2', self::RELEASE014_UPDATE002);
    }

    public function update003()
    {
        if (! $this->_backend->columnExists('adr_lon', 'cal_events')) {
            $this->_backend->addCol('cal_events', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>adr_lon</name>
                    <type>float</type>
                    <notnull>false</notnull>
                    <default>null</default>
                </field>'));
        }

        if (! $this->_backend->columnExists('adr_lan', 'cal_events')) {
            $this->_backend->addCol('cal_events', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>adr_lan</name>
                    <type>float</type>
                    <notnull>false</notnull>
                    <default>null</default>
                </field>'));
        }

        if ($this->getTableVersion('cal_events') < 18) {
            $this->setTableVersion('cal_events', 18);
        }

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel('Tinebase_Model_Relation', [
            ['field' => 'own_model', 'operator' => 'equals', 'value' => 'Calendar_Model_Resource'],
            ['field' => 'related_model', 'operator' => 'equals', 'value' => 'Addressbook_Model_Contact'],
            ['field' => 'type', 'operator' => 'equals', 'value' => 'STANDORT'],
        ]);
        $oldRelations = Tinebase_Relations::getInstance()->search($filter);
        
        foreach ($oldRelations as $relation) {
            $relation->type = 'SITE';
            Tinebase_Relations::getInstance()->getBackend()->update($relation);
        }

        $this->addApplicationUpdate('Calendar', '14.3', self::RELEASE014_UPDATE003);
    }
}
