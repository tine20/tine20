<?php
require_once 'PHPUnit/Framework.php';

require_once '../jsb2tk.php';

class jsb2tkTest extends PHPUnit_Framework_TestCase
{
    
    public function tearDown()
    {
        $basePath = dirname(__FILE__);
        `rm -rf {$basePath}/core/deploy/*`;
        `rm -rf {$basePath}/modul/deploy/*`;
    }
    
    public function testGetDefinition()
    {
        $definition = jsb2tk::getDefinition('core/core.jsb2');
        
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $definition);
        $this->assertObjectHasAttribute('pkgs', $definition);
        
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $definition->pkgs);
    }

    public function testConstruct()
    {
        $config = array(
            'deploymode'    => jsb2tk::DEPLOYMODE_STATIC,
            'includemode'   => jsb2tk::INCLUDEMODE_PACKAGE,
            'jsb2bin'       => '../JSBulder2/JSBuilder2.jar',
            'appendctime'   => TRUE,
            'htmlindention' => "\t\t",
        );
        
        $tk = new jsb2tk($config);
    }
    
    public function testConstructFailWithWrongValue()
    {
        $config = array(
            'deploymode'  => 'fail',
        );
        
        $this->setExpectedException('Exception');
        $tk = new jsb2tk($config);
    }

    public function testGetIndividualFiles()
    {
        $tk = new jsb2tk();
        $tk->register('core/core.jsb2', 'core');
        $tk->register('modul/modul.jsb2', 'modul');
        
        $fileObjects = $tk->getIndividualFiles('js');
        
        $files = array();
        foreach ($fileObjects as $fo) {
            $files[] = $fo->path;
        }
        
        $this->assertTrue(in_array('core/src/ModulSel.js', $files));
        $this->assertTrue(in_array('modul/src/SomeComponent.js', $files));
    }
    
    public function testDynamicDeploy()
    {
        $tk = new jsb2tk(array(
            'deploymode'    => jsb2tk::DEPLOYMODE_DYNAMIC,
            'includemode'   => jsb2tk::INCLUDEMODE_DEBUG_PACKAGE,
            'jsb2bin'       => '../JSBuilder2/JSBuilder2.jar',
            'appendctime'   => TRUE,
        ));
        
        $tk->register('core/core.jsb2', 'core');
        $tk->register('modul/modul.jsb2', 'modul');
        
        $html = $tk->getHTML();
        sleep(1);
        
        $basePath = dirname(__FILE__);
        `touch {$basePath}/modul/src/model.js`;
        
        $html = $tk->getHTML();
        
        $this->assertTrue(filectime("{$basePath}/modul/deploy/pkgs/modul.js") >= filectime("{$basePath}/modul/src/model.js"));
        $this->assertTrue(filectime("{$basePath}/modul/deploy/pkgs/modul.js") > filectime("{$basePath}/core/deploy/pkgs/framework.js"));
        
        $this->assertRegExp('/core\.css\?\d+/', $html);
        $this->assertRegExp('/modul\.css/', $html);
        $this->assertRegExp('/framework-debug\.js/', $html);
        $this->assertRegExp('/modul-debug\.js/', $html);
    }
    
}