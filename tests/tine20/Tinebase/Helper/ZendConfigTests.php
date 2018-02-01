<?php
/**
 * Tine 2.0
 *
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Paul Mehrer <p.mehrer@metaways.de>
 * @copyright    Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Class Tinebase_Helper_ZendConfigTest
 */
class Tinebase_Helper_ZendConfigTests extends TestCase
{
    public function testGetChildrenStrings()
    {
        $xml = new Zend_Config_Xml(
            '<?xml version="1.0" encoding="UTF-8"?>
                <test>
                    <emptyChild1/>
                    <emptyChild2>bla</emptyChild2>
                    <singleChild1>
                        <child>child1</child>
                    </singleChild1>
                    <singleChild2>bla
                        <child>child1</child>
                    </singleChild2>
                    <multiChild1>
                        <child>child1</child>
                        <child>child2</child>
                    </multiChild1>
                    <multiChild2>bla
                        <child>child1</child>
                        <child>child2</child>
                    </multiChild2>
                    
                    <brokenSingleChild1>
                        <child><bla>blub</bla></child>
                    </brokenSingleChild1>
                    <brokenSingleChild2>bla
                        <child><bla>blub</bla></child>
                    </brokenSingleChild2>
                    <brokenMultiChild1>
                        <child>child1</child>
                        <child><bla>blub</bla></child>
                    </brokenMultiChild1>
                    <brokenMultiChild2>bla
                        <child>child1</child>
                        <child><bla>blub</bla></child>
                    </brokenMultiChild2>
                </test>');

        static::assertEquals([], Tinebase_Helper_ZendConfig::getChildrenStrings($xml->emptyChild1, 'child'));
        static::assertEquals([], Tinebase_Helper_ZendConfig::getChildrenStrings($xml->emptyChild2, 'child'));

        static::assertEquals(['child1'], Tinebase_Helper_ZendConfig::getChildrenStrings($xml->singleChild1, 'child'));
        static::assertEquals(['child1'], Tinebase_Helper_ZendConfig::getChildrenStrings($xml->singleChild2, 'child'));

        static::assertEquals(['child1', 'child2'], Tinebase_Helper_ZendConfig::getChildrenStrings($xml->multiChild1,
            'child'));
        static::assertEquals(['child1', 'child2'], Tinebase_Helper_ZendConfig::getChildrenStrings($xml->multiChild2,
            'child'));


        try {
            Tinebase_Helper_ZendConfig::getChildrenStrings($xml->brokenSingleChild1, 'child');
            static::fail('expected exception for brokenSingleChild1');
        } catch (Tinebase_Exception_InvalidArgument $teia) {}
        try {
            Tinebase_Helper_ZendConfig::getChildrenStrings($xml->brokenSingleChild2, 'child');
            static::fail('expected exception for brokenSingleChild2');
        } catch (Tinebase_Exception_InvalidArgument $teia) {}

        try {
            Tinebase_Helper_ZendConfig::getChildrenStrings($xml->brokenMultiChild1, 'child');
            static::fail('expected exception for brokenMultiChild1');
        } catch (Tinebase_Exception_InvalidArgument $teia) {}
        try {
            Tinebase_Helper_ZendConfig::getChildrenStrings($xml->brokenMultiChild2, 'child');
            static::fail('expected exception for brokenMultiChild2');
        } catch (Tinebase_Exception_InvalidArgument $teia) {}
    }

    public function testGetChildrenConfigs()
    {
        $xml = new Zend_Config_Xml(
            '<?xml version="1.0" encoding="UTF-8"?>
                <test>
                    <emptyChild1/>
                    <emptyChild2>bla</emptyChild2>
                    <emptyChild3><blub>bla</blub></emptyChild3>
                    <emptyChild4>bla<blub>bla</blub></emptyChild4>
                    
                    <singleChild1>
                        <child><blub>bla</blub></child>
                    </singleChild1>
                    <singleChild2>bla
                        <child><blub>bla</blub></child>
                    </singleChild2>
                    <singleChild3>bla
                        <child>bla<blub>bla</blub></child>
                    </singleChild3>
                    <singleChild4>
                        <child>
                            <blub>bla</blub>
                            <bla>blub</bla>
                        </child>
                    </singleChild4>
                    <singleChild5>bla
                        <child>
                            <blub>bla</blub>
                            <bla>blub</bla>
                        </child>
                    </singleChild5>
                    <singleChild6>bla
                        <child>bla
                            <blub>bla</blub>
                            <bla>blub</bla>
                        </child>
                    </singleChild6>
                    
                    <multiChild1>
                        <child><blub>bla</blub></child>
                        <child><blub>bla</blub></child>
                    </multiChild1>
                    <multiChild2>bla
                        <child><blub>bla</blub></child>
                        <child><blub>bla</blub></child>
                    </multiChild2>
                    <multiChild3>bla
                        <child>bla<blub>bla</blub></child>
                        <child>bla<blub>bla</blub></child>
                    </multiChild3>
                    <multiChild4>
                        <child>
                            <blub>bla</blub>
                            <bla>blub</bla>
                        </child>
                        <child>
                            <blub>bla</blub>
                            <bla>blub</bla>
                        </child>
                    </multiChild4>
                    <multiChild5>bla
                        <child>
                            <blub>bla</blub>
                            <bla>blub</bla>
                        </child>
                        <child>
                            <blub>bla</blub>
                            <bla>blub</bla>
                        </child>
                    </multiChild5>
                    <multiChild6>bla
                        <child>bla
                            <blub>bla</blub>
                            <bla>blub</bla>
                        </child>
                        <child>bla
                            <blub>bla</blub>
                            <bla>blub</bla>
                        </child>
                    </multiChild6>
                    
                    
                    <brokenSingleChild1>
                        <child>bla</child>
                    </brokenSingleChild1>
                    <brokenSingleChild2>bla
                        <child>bla</child>
                    </brokenSingleChild2>
                    <brokenMultiChild1>
                        <child>bla</child>
                        <child><bla>blub</bla></child>
                    </brokenMultiChild1>
                    <brokenMultiChild2>bla
                        <child>bla</child>
                        <child><bla>blub</bla></child>
                    </brokenMultiChild2>
                </test>');

        static::assertEquals([], Tinebase_Helper_ZendConfig::getChildrenConfigs($xml->emptyChild1, 'child'));
        static::assertEquals([], Tinebase_Helper_ZendConfig::getChildrenConfigs($xml->emptyChild2, 'child'));
        static::assertEquals([], Tinebase_Helper_ZendConfig::getChildrenConfigs($xml->emptyChild3, 'child'));
        static::assertEquals([], Tinebase_Helper_ZendConfig::getChildrenConfigs($xml->emptyChild4, 'child'));

        list($result) = Tinebase_Helper_ZendConfig::getChildrenConfigs($xml->singleChild1, 'child');
        static::assertTrue($result instanceof Zend_Config, 'result is not instance of Zend_Config');
        static::assertEquals('bla', $result->blub);
        list($result) = Tinebase_Helper_ZendConfig::getChildrenConfigs($xml->singleChild2, 'child');
        static::assertTrue($result instanceof Zend_Config, 'result is not instance of Zend_Config');
        static::assertEquals('bla', $result->blub);
        list($result) = Tinebase_Helper_ZendConfig::getChildrenConfigs($xml->singleChild3, 'child');
        static::assertTrue($result instanceof Zend_Config, 'result is not instance of Zend_Config');
        static::assertEquals('bla', $result->blub);
        list($result) = Tinebase_Helper_ZendConfig::getChildrenConfigs($xml->singleChild4, 'child');
        static::assertTrue($result instanceof Zend_Config, 'result is not instance of Zend_Config');
        static::assertEquals('bla', $result->blub);
        static::assertEquals('blub', $result->bla);
        list($result) = Tinebase_Helper_ZendConfig::getChildrenConfigs($xml->singleChild5, 'child');
        static::assertTrue($result instanceof Zend_Config, 'result is not instance of Zend_Config');
        static::assertEquals('bla', $result->blub);
        static::assertEquals('blub', $result->bla);
        list($result) = Tinebase_Helper_ZendConfig::getChildrenConfigs($xml->singleChild6, 'child');
        static::assertTrue($result instanceof Zend_Config, 'result is not instance of Zend_Config');
        static::assertEquals('bla', $result->blub);
        static::assertEquals('blub', $result->bla);

        list($result1, $result2) = Tinebase_Helper_ZendConfig::getChildrenConfigs($xml->multiChild1, 'child');
        static::assertTrue($result1 instanceof Zend_Config, 'result is not instance of Zend_Config');
        static::assertEquals('bla', $result1->blub);
        static::assertTrue($result2 instanceof Zend_Config, 'result is not instance of Zend_Config');
        static::assertEquals('bla', $result2->blub);
        list($result1, $result2) = Tinebase_Helper_ZendConfig::getChildrenConfigs($xml->multiChild2, 'child');
        static::assertTrue($result1 instanceof Zend_Config, 'result is not instance of Zend_Config');
        static::assertEquals('bla', $result1->blub);
        static::assertTrue($result2 instanceof Zend_Config, 'result is not instance of Zend_Config');
        static::assertEquals('bla', $result2->blub);
        list($result1, $result2) = Tinebase_Helper_ZendConfig::getChildrenConfigs($xml->multiChild3, 'child');
        static::assertTrue($result1 instanceof Zend_Config, 'result is not instance of Zend_Config');
        static::assertEquals('bla', $result1->blub);
        static::assertTrue($result2 instanceof Zend_Config, 'result is not instance of Zend_Config');
        static::assertEquals('bla', $result2->blub);
        list($result1, $result2) = Tinebase_Helper_ZendConfig::getChildrenConfigs($xml->multiChild4, 'child');
        static::assertTrue($result1 instanceof Zend_Config, 'result is not instance of Zend_Config');
        static::assertEquals('bla', $result1->blub);
        static::assertEquals('blub', $result1->bla);
        static::assertTrue($result2 instanceof Zend_Config, 'result is not instance of Zend_Config');
        static::assertEquals('bla', $result2->blub);
        static::assertEquals('blub', $result2->bla);
        list($result1, $result2) = Tinebase_Helper_ZendConfig::getChildrenConfigs($xml->multiChild5, 'child');
        static::assertTrue($result1 instanceof Zend_Config, 'result is not instance of Zend_Config');
        static::assertEquals('bla', $result1->blub);
        static::assertEquals('blub', $result1->bla);
        static::assertTrue($result2 instanceof Zend_Config, 'result is not instance of Zend_Config');
        static::assertEquals('bla', $result2->blub);
        static::assertEquals('blub', $result2->bla);
        list($result1, $result2) = Tinebase_Helper_ZendConfig::getChildrenConfigs($xml->multiChild6, 'child');
        static::assertTrue($result1 instanceof Zend_Config, 'result is not instance of Zend_Config');
        static::assertEquals('bla', $result1->blub);
        static::assertEquals('blub', $result1->bla);
        static::assertTrue($result2 instanceof Zend_Config, 'result is not instance of Zend_Config');
        static::assertEquals('bla', $result2->blub);
        static::assertEquals('blub', $result2->bla);


        try {
            Tinebase_Helper_ZendConfig::getChildrenConfigs($xml->brokenSingleChild1, 'child');
            static::fail('expected exception for brokenSingleChild1');
        } catch (Tinebase_Exception_InvalidArgument $teia) {}
        try {
            Tinebase_Helper_ZendConfig::getChildrenConfigs($xml->brokenSingleChild2, 'child');
            static::fail('expected exception for brokenSingleChild2');
        } catch (Tinebase_Exception_InvalidArgument $teia) {}

        try {
            Tinebase_Helper_ZendConfig::getChildrenConfigs($xml->brokenMultiChild1, 'child');
            static::fail('expected exception for brokenMultiChild1');
        } catch (Tinebase_Exception_InvalidArgument $teia) {}
        try {
            Tinebase_Helper_ZendConfig::getChildrenConfigs($xml->brokenMultiChild2, 'child');
            static::fail('expected exception for brokenMultiChild2');
        } catch (Tinebase_Exception_InvalidArgument $teia) {}
    }
}