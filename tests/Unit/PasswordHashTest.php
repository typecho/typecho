<?php
use PHPUnit\Framework\TestCase;
use Utils\PasswordHash;

final class PasswordHashTest extends TestCase
{
    public function testPortableHashRoundTrip(): void
    {
        // Same parameters as Widget\User / install.php
        $hasher = new PasswordHash(8, true);
        $hash = $hasher->hashPassword('s3cret-密码');

        $this->assertStringStartsWith('$P$', $hash);
        $this->assertSame(34, strlen($hash));
        $this->assertTrue($hasher->checkPassword('s3cret-密码', $hash));
        $this->assertFalse($hasher->checkPassword('s3cret-密码 ', $hash));
    }

    public function testLegacyMd5HashIsNotAcceptedByPhpass(): void
    {
        $hasher = new PasswordHash(8, true);
        $this->assertFalse($hasher->checkPassword('admin', md5('admin')));
    }
}
