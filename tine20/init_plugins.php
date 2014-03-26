<?php
/**
 * Tine 2.0
 * 
 * Use this script to initialize plugins for frontend, controller and backend layers
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 * @copyright   Copyright (c) 2008-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 *
 */
/*
 * For injecting plugin into frontend layer:
 * 
 * Tinebase_Frontend_Abstract::attachPlugin('[method]', '[class]');
 * 
 * or
 * 
 * Tinebase_Frontend_Abstract::attachPlugins(array('[method]','[class]'), '[namespace]');
 * 
 * For injecting plugin into controller layer:
 * 
 * Tinebase_Controller_Abstract::attachPlugin('[method]', '[class]');
 * 
 * or
 *  
 * Tinebase_Controller_Abstract::attachPlugins(array('[method]','[class]'), '[namespace]');
 * 
 * For injecting plugin into backend layer:
 * 
 * Tinebase_Backend_Abstract::attachPlugin('[method]', '[class]');
 * 
 * or
 * 
 * Tinebase_Backend_Abstract::attachPlugin(array('[method]','[class]'), '[namespace]'); 
 */