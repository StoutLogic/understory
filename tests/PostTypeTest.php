<?php

namespace Understory\Tests;

use PHPUnit\Framework\TestCase;
use Understory\PostType;
use Brain\Monkey;

class PostTypeTest extends TestCase
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

    public function testConfigure()
    {
        $subject = new PostType;
        $subject
            ->setLabelName('SubjectName')
            ->setLabelPlural('SubjectPlural')
            ->setSlug('subject-slug')
            ->setSupports(['title', 'editor', 'thumbnail', 'excerpt'])
            ->setConfig('public', false);

        $expectedConfig = [
            'labels' => [
                'name' => 'SubjectPlural',
                'singular_name' => 'SubjectName',
            ],
            'public' => false,
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
            'rewrite' => [
                'slug' => 'subject-slug',
            ]
        ];

        $this->assertArraySubset($expectedConfig, $subject->getConfig());
    }

    public function testSetPostType()
    {
        $subject = new PostType;
        $subject->setPostType('product');

        $this->assertEquals('product', $subject->getPostType());
    }
}
