<?php
use PHPUnit\Framework\TestCase;
use Typecho\Common;

/** Regressions for 96ccfdd / fb1c761 / 657b48f (SSRF hardening). IP-literal cases need no DNS. */
final class ResolveSafeHostTest extends TestCase
{
    /** @dataProvider unsafeHosts */
    public function testUnsafeHostRejected(string $host): void
    {
        $this->assertNull(Common::resolveSafeHost($host));
    }

    public function unsafeHosts(): array
    {
        return [
            'loopback'          => ['127.0.0.1'],
            'private 10/8'      => ['10.1.2.3'],
            'cgnat'             => ['100.64.0.1'],
            'link-local/meta'   => ['169.254.169.254'],
            'ipv6 loopback'     => ['[::1]'],
            'v4-mapped'         => ['[::ffff:127.0.0.1]'],
            'nat64'             => ['[64:ff9b::a00:1]'],
            '6to4 -> 127/8'     => ['[2002:7f00:1::]'],
            'doc range v6'      => ['[2001:db8::1]'],
            'decimal ipv4'      => ['2130706433'],
            'hex ipv4 label'    => ['0x7f.1'],
            'localhost'         => ['localhost'],
            'sub.localhost'     => ['a.localhost'],
            'trailing dot'      => ['example.com.'],
            'percent-encoded'   => ['exa%6dple.com'],
            'non-ascii'         => ['exämple.com'],
            'brackets on v4'    => ['[8.8.8.8]'],
            'empty'             => [''],
        ];
    }

    public function testPublicLiteralReturnedAsIs(): void
    {
        $this->assertSame('8.8.8.8', Common::resolveSafeHost('8.8.8.8'));
        $this->assertSame('2606:4700:4700::1111', Common::resolveSafeHost('[2606:4700:4700::1111]'));
    }

    /** @group network */
    public function testPublicNameResolves(): void
    {
        $this->assertNotNull(Common::resolveSafeHost('typecho.org'));
    }
}
