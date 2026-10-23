<?php
use IXR\Server;
use PHPUnit\Framework\TestCase;

final class XmlRpcServerTest extends TestCase
{
    private const LIST_METHODS = '<methodCall><methodName>system.listMethods</methodName></methodCall>';

    public function testRequestAtTheSizeLimitIsServed(): void
    {
        $xml = str_pad(self::LIST_METHODS, Server::MAX_REQUEST_SIZE);
        $this->assertStringContainsString('system.listMethods', (new Server())->handle($xml));
    }

    public function testRequestOverTheSizeLimitIsRejected(): void
    {
        $xml = str_pad(self::LIST_METHODS, Server::MAX_REQUEST_SIZE + 1);
        $response = (new Server())->handle($xml);

        $this->assertStringContainsString('<int>-32700</int>', $response);
        $this->assertStringContainsString('request too large', $response);
    }
}
