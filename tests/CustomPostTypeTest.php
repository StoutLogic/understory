<?php

namespace Understory\Tests;

use PHPUnit\Framework\TestCase;
use Understory\PostType;
use Understory\CustomPostType;
use Understory\Sequential;
use Brain\Monkey;
use Mockery;

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

    public function testHasWithSequential()
    {
        $sequential = $this->prophesize(Sequential::class);
        $sequential
            ->setSequentialPosition(1)->shouldBeCalled();

        $sequential2 = $this->prophesize(Sequential::class);
        $sequential2
            ->setSequentialPosition(2);

        $subject = Mockery::namedMock('SequenceTestPostType', CustomPostType::class . '[configure]')
            ->shouldAllowMockingProtectedMethods();

        $subject
            ->shouldReceive('configure')
            ->andReturnUsing(function (PostType $postType) use (
                $subject,
                $sequential,
                $sequential2
            ) {
                $subject->has('field1', $sequential->reveal());
                $subject->has('field2', $sequential2->reveal());

                return $postType;
            });

        $subject->getMetaDataBinding();
    }
}

class Product extends CustomPostType
{
    function configure(PostType $postType)
    {
        return $postType;
    }
}
