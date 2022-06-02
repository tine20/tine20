<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Tinebase_Model_Filter_Group
 *
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Filter_Group extends Tinebase_Model_Filter_Id
{
    public function setValue($_value)
    {
        if (is_array($_value)) {
            foreach ($_value as &$val) {
                $val = $this->transformValue($val);
            }
            unset($val);
        } else {
            $_value = $this->transformValue($_value);
        }
        parent::setValue($_value);
    }

    protected function transformValue($val)
    {
        switch ($val) {
            case Tinebase_Group::DEFAULT_USER_GROUP:
                return Tinebase_Group::getInstance()->getDefaultGroup()->getId();
            case Tinebase_Group::DEFAULT_ADMIN_GROUP:
                return Tinebase_Group::getInstance()->getDefaultAdminGroup()->getId();
            case Tinebase_Group::DEFAULT_ANONYMOUS_GROUP:
                return Tinebase_Group::getInstance()->getDefaultAnonymousGroup()->getId();
        }
        return $val;
    }
}
