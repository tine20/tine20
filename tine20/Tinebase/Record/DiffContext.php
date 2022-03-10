<?php declare(strict_types=1);

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class Tinebase_Record_DiffContext
{
    /** @var ?Closure */
    protected $subDiffOmitFieldsDelegator;

    public function setSubDiffOmitFieldsDelegator(?Closure $c): self
    {
        $this->subDiffOmitFieldsDelegator = $c;
        return $this;
    }

    public function getSubDiffOmitFields(?Tinebase_ModelConfiguration $mc): array
    {
        return $this->subDiffOmitFieldsDelegator ? ($this->subDiffOmitFieldsDelegator)($mc) : [];
    }
}
