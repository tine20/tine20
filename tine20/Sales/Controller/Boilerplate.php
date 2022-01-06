<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * controller for Boilerplate
 *
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_Boilerplate extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     *
     * @throws Tinebase_Exception_Backend_Database
     */
    protected function __construct()
    {
        $this->_doContainerACLChecks = false;
        $this->_applicationName = Sales_Config::APP_NAME;
        $this->_modelName = Sales_Model_Boilerplate::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::TABLE_NAME        => Sales_Model_Boilerplate::TABLE_NAME,
            Tinebase_Backend_Sql::MODEL_NAME        => Sales_Model_Boilerplate::class,
            Tinebase_Backend_Sql::MODLOG_ACTIVE     => true,
        ]);
    }

    /**
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        /** @var Sales_Model_Boilerplate $_record */
        $this->_checkUniqueAndDateOverlaps($_record);

        parent::_inspectBeforeCreate($_record);
    }

    /**
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        /** @var Sales_Model_Boilerplate $_record */
        $this->_checkUniqueAndDateOverlaps($_record);

        parent::_inspectBeforeUpdate($_record, $_oldRecord);
    }

    /**
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _checkUniqueAndDateOverlaps(Sales_Model_Boilerplate $boilerplate): void
    {
        $filter = [
            ['field' => Sales_Model_Boilerplate::FLD_NAME, 'operator' => 'equals', 'value' => $boilerplate->{Sales_Model_Boilerplate::FLD_NAME}],
            ['field' => Sales_Model_Boilerplate::FLD_DOCUMENT_CATEGORY, 'operator' => 'equals', 'value' => $boilerplate->{Sales_Model_Boilerplate::FLD_DOCUMENT_CATEGORY}],
            ['field' => Sales_Model_Boilerplate::FLD_CUSTOMER, 'operator' => 'equals', 'value' => $boilerplate->{Sales_Model_Boilerplate::FLD_CUSTOMER}],
            ['field' => Sales_Model_Boilerplate::FLD_MODEL, 'operator' => 'equals', 'value' => $boilerplate->{Sales_Model_Boilerplate::FLD_MODEL}],
            ['field' => Sales_Model_Boilerplate::FLD_LANGUAGE, 'operator' => 'equals', 'value' => $boilerplate->{Sales_Model_Boilerplate::FLD_LANGUAGE}],
        ];

        if ($boilerplate->getId()) {
            $filter[] = ['field' => 'id', 'operator' => 'not', 'value' => $boilerplate->getId()];
        }

        if (!$boilerplate->{Sales_Model_Boilerplate::FLD_FROM} && !$boilerplate->{Sales_Model_Boilerplate::FLD_UNTIL}) {
            $filter[] = ['field' => Sales_Model_Boilerplate::FLD_FROM, 'operator' => 'isnull', 'value' => true];
            $filter[] = ['field' => Sales_Model_Boilerplate::FLD_UNTIL, 'operator' => 'isnull', 'value' => true];
        } else {
            if ($boilerplate->{Sales_Model_Boilerplate::FLD_FROM}) {
                $filter[] = ['field' => Sales_Model_Boilerplate::FLD_UNTIL, 'operator' => 'after_or_equals',
                    'value' => $boilerplate->{Sales_Model_Boilerplate::FLD_FROM}];
                if (!$boilerplate->{Sales_Model_Boilerplate::FLD_UNTIL}) {
                    $filter[] =
                        ['field' => Sales_Model_Boilerplate::FLD_UNTIL, 'operator' => 'notnull', 'value' => true];
                }
            }
            if ($boilerplate->{Sales_Model_Boilerplate::FLD_UNTIL}) {
                $filter[] = ['field' => Sales_Model_Boilerplate::FLD_FROM, 'operator' => 'before_or_equals',
                    'value' => $boilerplate->{Sales_Model_Boilerplate::FLD_UNTIL}];
                if (!$boilerplate->{Sales_Model_Boilerplate::FLD_FROM}) {
                    $filter[] =
                        ['field' => Sales_Model_Boilerplate::FLD_FROM, 'operator' => 'notnull', 'value' => true];
                }
            }
            if ($boilerplate->{Sales_Model_Boilerplate::FLD_FROM} && $boilerplate->{Sales_Model_Boilerplate::FLD_UNTIL}) {
                $filter[] = [
                    'condition' => Tinebase_Model_Filter_FilterGroup::CONDITION_OR,
                    'filters' => [
                        ['field' => Sales_Model_Boilerplate::FLD_FROM, 'operator' => 'notnull', 'value' => true],
                        ['field' => Sales_Model_Boilerplate::FLD_UNTIL, 'operator' => 'notnull', 'value' => true],
                    ]
                ];
            }
        }

        if ($overlap = $this->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                Sales_Model_Boilerplate::class, $filter))->getFirstRecord()) {
            if (!$boilerplate->{Sales_Model_Boilerplate::FLD_FROM} &&
                    !$boilerplate->{Sales_Model_Boilerplate::FLD_UNTIL}) {
                throw new Tinebase_Exception_Record_Validation('Name needs to be unique.');
            } else {
                throw new Tinebase_Exception_Record_Validation('Dates "' .
                    $boilerplate->{Sales_Model_Boilerplate::FLD_FROM} . ' - ' .
                    $boilerplate->{Sales_Model_Boilerplate::FLD_UNTIL} . '" overlap with existing records dates "' .
                    $overlap->{Sales_Model_Boilerplate::FLD_FROM} . ' - ' .
                    $overlap->{Sales_Model_Boilerplate::FLD_UNTIL} . '"');
            }
        }
    }
}
