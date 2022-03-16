<?php declare(strict_types=1);
/**
 * Tine 2.0
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class Tinebase_Server_ZendMethodDefinitionWrapper extends Zend_Server_Method_Definition
{
    protected $apiTimeout;

    public function setApiTimeout(?int $val): self
    {
        $this->apiTimeout = $val;
        return $this;
    }

    public function getApiTimeout()
    {
        return $this->apiTimeout;
    }

    /**
     * Serialize to array
     *
     * @return array
     */
    public function toArray()
    {
        $result = parent::toArray();
        $result['apiTimeout'] = $this->apiTimeout;
        return $result;
    }
}
