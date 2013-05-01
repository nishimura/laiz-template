<?php

namespace Laiz\Template\Test;

use PHPUnit_Framework_TestCase;
use Laiz\Template\Parser;
use stdClass;

class ParserTest extends PHPUnit_Framework_TestCase
{
    private $target;
    protected function setUp()
    {
        $this->target = new Parser('test/html');
    }
    public function testSimpleVar()
    {
        $this->target->setFile('simple_var.html');
        $args = new stdClass();
        $args->var1 = 'Hello';
        $ret = $this->target->get($args);

        $matcher = array('id' => 'var1',
                         'content' => 'Hello');
        $this->assertTag($matcher, $ret);
    }

    public function testAttr()
    {
        $this->target->setFile('attr.html');
        $args = new stdClass();
        $args->var1 = 'my class';
        $ret = $this->target->get($args);

        $matcher = array('id' => 'var1',
                         'attributes' => array('class' => 'my class'));
        $this->assertTag($matcher, $ret);
    }

    public function testIfCloseTag()
    {
        $this->target->setFile('attr_if_close.html');
        $args = new stdClass();
        $args->var1 = false;
        $args->var2 = true;

        $ret = $this->target->get($args);

        $matcher = array('id' => 'body',
                         'children' => array('count' => 1));
        $this->assertTag($matcher, $ret);
        $matcher = array('id' => 'if2',
                         'attributes' => array('class' => '2'));
        $this->assertTag($matcher, $ret);
    }

    public function testCustomIf()
    {
        $this->target->setFile('custom_if.html');
        $args = new stdClass();
        $args->var1 = false;
        $args->var2 = true;

        $ret = $this->target->get($args);

        $matcher = array('id' => 'body',
                         'children' => array('count' => 1));
        $this->assertTag($matcher, $ret);
        $matcher = array('id' => 'if2',
                         'content' => '2');
        $this->assertTag($matcher, $ret);
    }

    public function testCustomIfel()
    {
        $this->target->setFile('custom_ifel.html');
        $args = new stdClass();
        $args->var1 = false;

        $ret = $this->target->get($args);

        $matcher = array('id' => 'body',
                         'children' => array('count' => 1));
        $this->assertTag($matcher, $ret);
        $matcher = array('id' => 'if2',
                         'content' => '2');
        $this->assertTag($matcher, $ret);
    }

    public function testCustomLoop()
    {
        $this->target->setFile('custom_loop.html');
        $args = new stdClass();
        $args->ITEMS = array();
        for ($i = 0; $i < 2; $i++){
            $row = new stdClass();
            $row->id = $i;
            $row->var1 = $i;
            $args->ITEMS[] = $row;
        }
        $ret = $this->target->get($args);

        $matcher = array('id' => 'loop0',
                         'content' => '0');
        $this->assertTag($matcher, $ret);
        $matcher = array('id' => 'loop1',
                         'content' => '1');
        $this->assertTag($matcher, $ret);
    }
    public function testCustomIfLoop()
    {
        $this->target->setFile('custom_if_loop.html');
        $args = new stdClass();
        $args->check2 = true;
        $args->ITEMS = array();
        for ($i = 0; $i < 2; $i++){
            $row = new stdClass();
            $row->id = $i;
            $row->var1 = $i;
            $args->ITEMS[] = $row;
        }
        $ret = $this->target->get($args);

        $matcher = array('id' => 'body',
                         'children' => array('count' => 2));
        $this->assertTag($matcher, $ret);
        $matcher = array('id' => 'loop20',
                         'content' => '0');
        $this->assertTag($matcher, $ret);
        $matcher = array('id' => 'loop21',
                         'content' => '1');
        $this->assertTag($matcher, $ret);
    }
    public function testCustomLoopIf()
    {
        $this->target->setFile('custom_loop_if.html');
        $args = new stdClass();
        $args->ITEMS = array();
        for ($i = 0; $i < 2; $i++){
            $row = new stdClass();
            $row->id = $i;
            $row->var1 = $i;
            $args->ITEMS[] = $row;
        }
        $ret = $this->target->get($args);

        $matcher = array('id' => 'body',
                         'children' => array('count' => 1));
        $this->assertTag($matcher, $ret);
        $matcher = array('id' => 'loop1',
                         'content' => '1');
        $this->assertTag($matcher, $ret);
    }

    public function testFormCheckbox()
    {
        $this->target->setFile('form_checkbox.html');
        $args = new stdClass();
        $args->check = true;

        $args->my = new stdClass();
        $args->my->obj = new stdClass();
        $args->my->obj->name = 'ON';
        $ret = $this->target->get($args);

        $matcher = array('id' => 'checkbox1',
                         'attributes' => array('checked' => 'checked'));
        $this->assertTag($matcher, $ret);
        $this->assertTrue(strpos($ret, 'checked') !== false);


        $matcher = array('id' => 'checkbox2',
                         'attributes' => array('checked' => 'checked'));
        $this->assertTag($matcher, $ret);
        $this->assertTrue(strpos($ret, 'checked') !== false);

        $args->check = 'false';
        $args->my->obj->name = 'dummy';
        $ret = $this->target->get($args);

        $this->assertFalse(strpos($ret, 'checked') !== false);
    }

    public function testFormRadio()
    {
        $this->target->setFile('form_radio.html');
        $args = new stdClass();
        $args->check = true;

        $args->my = new stdClass();
        $args->my->obj = new stdClass();
        $args->my->obj->name = 'ON2';
        $ret = $this->target->get($args);

        $matcher = array('id' => 'radio1',
                         'attributes' => array('checked' => 'checked'));
        $this->assertTag($matcher, $ret);
        $this->assertTrue(strpos($ret, 'checked') !== false);


        $matcher = array('id' => 'radio2',
                         'attributes' => array('checked' => 'checked'));
        $this->assertTag($matcher, $ret);
        $this->assertTrue(strpos($ret, 'checked') !== false);

        $args->check = false;
        $args->my->obj->name = 'dummy';
        $ret = $this->target->get($args);

        $this->assertFalse(strpos($ret, 'checked') !== false);
    }

    public function testFormSelectbox()
    {
        $this->target->setFile('form_selectbox.html');
        $args = new stdClass();

        $args->list1arr = array();
        for($i = 0; $i < 3; $i++){
            $args->list1arr[] = "item$i";
        }
        $args->list1 = '2';

        $args->obj = new stdClass();
        $args->obj->list = array();
        for($i = 0; $i < 3; $i++){
            $args->obj->list["key$i"] = "item$i";
        }
        $args->obj->name = 'key1';
        $ret = $this->target->get($args);

        $matcher = array('id' => 'list1',
                         'children' => array('count' => 3));
        $this->assertTag($matcher, $ret);
        $matcher = array('id' => 'list1',
                         'child' => array('attributes' =>
                                          array('value' => '2',
                                                'selected' => 'selected')));
        $this->assertTag($matcher, $ret);


        $matcher = array('id' => 'list2',
                         'children' => array('count' => 4));
        $this->assertTag($matcher, $ret);
        $matcher = array('id' => 'list2',
                         'child' => array('attributes' =>
                                          array('value' => 'key1',
                                                'selected' => 'selected')));
        $this->assertTag($matcher, $ret);
        $matcher = array('id' => 'list2',
                         'child' => array('attributes' =>
                                          array('value' => ''),
                                          'content' => 'default'));
        $this->assertTag($matcher, $ret);
    }
}
