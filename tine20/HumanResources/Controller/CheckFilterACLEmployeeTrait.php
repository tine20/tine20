<?php declare(strict_types=1);
/**
 * checkFilterAcls with employee_id trait for HumanResources Controller
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

trait HumanResources_Controller_CheckFilterACLEmployeeTrait
{
    protected $_traitDelegateAclField = 'employee_id';
    protected $_traitCheckFilterACLRight = HumanResources_Acl_Rights::MANAGE_EMPLOYEE;
    protected $_traitGetOwnGrants = [HumanResources_Model_DivisionGrants::READ_OWN_DATA];

    /**
     * Removes containers where current user has no access to
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action get|update
     */
    public function checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = self::ACTION_GET)
    {
        if (!$this->_doContainerACLChecks) {
            return;
        }

        // if we have manage_employee right, we need no acl filter
        if (Tinebase_Core::getUser()->hasRight(HumanResources_Config::APP_NAME, $this->_traitCheckFilterACLRight)) {
            return;
        }
        parent::checkFilterACL($_filter, $_action);

        // for GET we also allow HumanResources_Model_DivisionGrants::READ_OWN_DATA
        if (self::ACTION_GET !== $_action) {
            return;
        }

        $orWrapper = new Tinebase_Model_Filter_FilterGroup([], Tinebase_Model_Filter_FilterGroup::CONDITION_OR);
        $andWrapper = new Tinebase_Model_Filter_FilterGroup();
        $filters = $_filter->getAclFilters();
        foreach ($filters as $filter) {
            $_filter->removeFilter($filter);
            $andWrapper->addFilter($filter);
        }
        $orWrapper->addFilterGroup($andWrapper);

        $andWrapper = new Tinebase_Model_Filter_FilterGroup();
        $containerFilter = new Tinebase_Model_Filter_DelegatedAcl($this->_traitDelegateAclField, null, null,
            array_merge($_filter->getOptions(), [
                'modelName' => $this->_modelName
            ]));
        $containerFilter->setRequiredGrants($this->_traitGetOwnGrants);
        $andWrapper->addFilter($containerFilter);
        $andWrapper->addFilter($this->_getCheckFilterACLTraitFilter());
        $orWrapper->addFilterGroup($andWrapper);

        $_filter->addFilterGroup($orWrapper);
    }

    protected function _getCheckFilterACLTraitFilter()
    {
        return new Tinebase_Model_Filter_ForeignId('employee_id', 'definedBy', [
            ['field' => 'account_id', 'operator' => 'equals', 'value' => Tinebase_Core::getUser()->getId()],
        ], [
            'controller' => HumanResources_Controller_Employee::class,
            'filtergroup' => HumanResources_Model_EmployeeFilter::class
        ]);
    }
}
