<?php
use PHPUnit\Framework\TestCase;
use Typecho\Common;

/** Regression for 2b96569 "Reject non-string tokens in timeTokenValidate" */
final class TimeTokenTest extends TestCase
{
    public function testFreshTokenValidates(): void
    {
        $this->assertTrue(Common::timeTokenValidate(Common::timeToken('secret'), 'secret'));
        $this->assertFalse(Common::timeTokenValidate(Common::timeToken('secret'), 'other'));
    }

    public function testExpiredTokenRejected(): void
    {
        $this->assertFalse(Common::timeTokenValidate(sha1('secret&' . (time() - 60)), 'secret', 5));
    }

    /** @dataProvider nonStringTokens */
    public function testNonStringTokensRejected($token): void
    {
        $this->assertFalse(Common::timeTokenValidate($token, 'secret'));
    }

    public function nonStringTokens(): array
    {
        return [[true], [0], [1], [[]], [null], ['']];
    }
}
