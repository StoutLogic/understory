<?php

namespace Understory\Tests;

use PHPUnit\Framework\TestCase;
use Understory\PostType;
use Understory\CustomPostType;
use Brain\Monkey;

class CustomPostTypeTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        Monkey::setUpWP();
    }

    protected function tearDown()
    {
        Monkey::tearDownWP();
        parent::tearDown();
    }

    public function testRegister()
    {
        $subject = new Product();

        Monkey\Functions::expect('register_post_type')
          ->once()
          ->with('product', \Mockery::type('array'));

        $subject->register();
    }
}

class Product extends CustomPostType
{
    function configure(PostType $postType)
    {
        return $postType;
    }
}
