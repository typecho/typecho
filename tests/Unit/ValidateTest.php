<?php
use PHPUnit\Framework\TestCase;
use Typecho\Validate;

final class ValidateTest extends TestCase
{
    public function testStaticRules(): void
    {
        $this->assertTrue(Validate::email('a.b+c@example.com'));
        $this->assertFalse(Validate::email('not-an-email'));
        $this->assertTrue(Validate::url('https://typecho.org/path?q=1'));
        $this->assertFalse(Validate::url('javascript:alert(1)'));
        $this->assertFalse(Validate::xssCheck('<script>alert(1)</script>'));
        $this->assertTrue(Validate::isInteger('42'));
        $this->assertFalse(Validate::isInteger('4.2'));
    }

    public function testRunCollectsMessages(): void
    {
        $v = new Validate();
        $v->addRule('mail', 'required', 'mail required');
        $v->addRule('mail', 'email', 'bad mail');
        $v->addRule('name', 'maxLength', 'too long', 4);

        $this->assertSame(
            ['mail' => 'bad mail', 'name' => 'too long'],
            $v->run(['mail' => 'x', 'name' => 'typecho'])
        );
        $this->assertSame([], $v->run(['mail' => 'a@b.cn', 'name' => 'tc']));
    }
}
