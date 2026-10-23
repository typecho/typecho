<?php
use IXR\Date;
use PHPUnit\Framework\TestCase;

final class IxrDateTest extends TestCase
{
    /**
     * @dataProvider isoProvider
     */
    public function testParsesIsoDates(string $input, string $iso): void
    {
        $this->assertSame($iso, (new Date($input))->getIso());
    }

    public static function isoProvider(): array
    {
        return [
            'basic'              => ['20261022T08:30:15', '20261022T08:30:15'],
            'basic with offset'  => ['20261022T08:30:15+08:00', '20261022T08:30:15+08:00'],
            'extended with Z'    => ['2026-10-22T08:30:15Z', '20261022T08:30:15Z'],
            'extended, fraction' => ['2026-10-22T08:30:15.250-05:00', '20261022T08:30:15-05:00'],
            'compact time'       => ['20261022T083015', '20261022T08:30:15'],
            'timestamp'          => ['1792657815', '20261022T08:30:15'],
        ];
    }
}
