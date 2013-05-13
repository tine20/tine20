<?php
/**
 * Interface for code generators
 *
 * @package     Tool
 * @subpackage  CodeGenerator
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$ 
 */
interface Tool_CodeGenerator_Interface
{
    public function build(array $args);
}
