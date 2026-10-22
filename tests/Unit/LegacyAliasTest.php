<?php
use PHPUnit\Framework\TestCase;

/** Old underscore names must keep resolving for plugins/themes in the wild */
final class LegacyAliasTest extends TestCase
{
    public function testMechanicalAliasResolves(): void
    {
        $this->assertTrue(class_exists('Typecho_Validate'));
        $this->assertInstanceOf(\Typecho\Validate::class, new \Typecho_Validate());
    }
}
