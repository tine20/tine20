<?php declare(strict_types=1);
/**
 * Tine 2.0
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * @method string getName()
 */
class Tinebase_Server_ZendReflectionMethodWrapper extends Zend_Server_Reflection_Method
{
    protected $apiTimeout;

    protected function _reflect()
    {
        parent::_reflect();
        if (empty($docBlock = $this->_reflection->getDocComment())) {
            return [];
        }
        if (preg_match('/@apiTimeout\s+(\d+)/', $docBlock, $m)) {
            $this->apiTimeout = intval($m[1]);
        }
        return [];
    }

    public function getApiTimeout(): ?int
    {
        return $this->apiTimeout;
    }
}
