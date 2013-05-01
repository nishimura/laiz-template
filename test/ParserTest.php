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
}
