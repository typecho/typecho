<?php
use PHPUnit\Framework\TestCase;
use Typecho\Validate;

/**
 * Covers the Validate rules and run() semantics that the existing smoke test does not reach:
 * UTF-8 length boundaries, enum/alpha/regexp/float rules, confirm, required() on missing or
 * array keys, callable rules, the explicit-rules argument and the break mode.
 */
final class ValidateRulesTest extends TestCase
{
    public function testLengthRulesCountCharactersNotBytes(): void
    {
        // two CJK characters are six bytes but two characters
        $this->assertTrue(Validate::minLength('世界', 2));
        $this->assertFalse(Validate::minLength('世界', 3));
        $this->assertTrue(Validate::maxLength('世界', 3));
    }

    public function testMaxLengthBoundaryIsExclusive(): void
    {
        // pinned as-is: maxLength(n) rejects a value of exactly n characters
        $this->assertTrue(Validate::maxLength('abc', 4));
        $this->assertFalse(Validate::maxLength('abcd', 4));
    }

    public function testEnum(): void
    {
        $this->assertTrue(Validate::enum('post', ['post', 'page']));
        $this->assertFalse(Validate::enum('draft', ['post', 'page']));
    }

    public function testAlphaRules(): void
    {
        $this->assertTrue(Validate::alpha('abcABC'));
        $this->assertFalse(Validate::alpha('abc1'));
        $this->assertFalse(Validate::alpha(''));

        $this->assertTrue(Validate::alphaNumeric('abc123'));
        $this->assertFalse(Validate::alphaNumeric('a-b'));

        $this->assertTrue(Validate::alphaDash('a-b_c1'));
        $this->assertFalse(Validate::alphaDash('a b'));
    }

    public function testFloatRule(): void
    {
        $this->assertTrue(Validate::isFloat('1.5'));
        $this->assertTrue(Validate::isFloat('-2'));
        $this->assertFalse(Validate::isFloat('abc'));
    }

    public function testRegexpRule(): void
    {
        $this->assertTrue(Validate::regexp('12345', '/^\d+$/'));
        $this->assertFalse(Validate::regexp('12a45', '/^\d+$/'));
    }

    public function testConfirmComparesAgainstAnotherKey(): void
    {
        $validate = new Validate();
        $validate->addRule('password', 'required', 'required');
        $validate->addRule('password2', 'confirm', 'mismatch', 'password');

        $this->assertSame([], $validate->run(['password' => 'secret', 'password2' => 'secret']));
        $this->assertSame(
            ['password2' => 'mismatch'],
            $validate->run(['password' => 'secret', 'password2' => 'other'])
        );
    }

    public function testRequiredRejectsMissingEmptyStringAndEmptyArray(): void
    {
        $validate = new Validate();
        $validate->addRule('tags', 'required', 'tags required');

        $this->assertSame(['tags' => 'tags required'], $validate->run([]));
        $this->assertSame(['tags' => 'tags required'], $validate->run(['tags' => '']));
        $this->assertSame(['tags' => 'tags required'], $validate->run(['tags' => []]));
        $this->assertSame([], $validate->run(['tags' => ['a']]));
    }

    public function testNonRequiredRulesSkipMissingAndEmptyValues(): void
    {
        $validate = new Validate();
        $validate->addRule('mail', 'email', 'bad mail');
        $validate->addRule('nick', 'maxLength', 'too long', 4);

        // a missing key must not raise "undefined index" and must not fail the rule
        $this->assertSame([], $validate->run([]));
        $this->assertSame([], $validate->run(['mail' => '', 'nick' => '']));

        $this->assertSame(['mail' => 'bad mail'], $validate->run(['mail' => 'not-a-mail']));
    }

    public function testCallableRuleGetsValueAndExtraParameters(): void
    {
        $validate = new Validate();
        $validate->addRule('code', function ($value, $prefix) {
            return 0 === strpos($value, $prefix);
        }, 'bad code', 'TC-');

        $this->assertSame([], $validate->run(['code' => 'TC-1']));
        $this->assertSame(['code' => 'bad code'], $validate->run(['code' => 'XX-1']));
    }

    public function testExplicitRulesArgumentOverridesRegisteredRules(): void
    {
        $validate = new Validate();
        $validate->addRule('a', 'required', 'a required');

        $rules = ['b' => [['required', 'b required']]];

        $this->assertSame([], $validate->run(['b' => 'x'], $rules));
        $this->assertSame(['b' => 'b required'], $validate->run([], $rules));
    }

    public function testFirstFailingRuleStopsThatKey(): void
    {
        $validate = new Validate();
        $validate->addRule('mail', 'required', 'required');
        $validate->addRule('mail', 'email', 'bad mail');

        // required fails first, so the email rule never reports
        $this->assertSame(['mail' => 'required'], $validate->run(['mail' => '']));
    }

    public function testBreakModeStopsAfterTheFirstFailingKey(): void
    {
        $validate = new Validate();
        $validate->addRule('a', 'required', 'a required');
        $validate->addRule('b', 'required', 'b required');

        $this->assertSame(
            ['a' => 'a required', 'b' => 'b required'],
            $validate->run(['b' => ''])
        );

        $validate->setBreak();

        $this->assertSame(['a' => 'a required'], $validate->run(['b' => '']));
    }
}