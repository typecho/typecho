<?php
use IXR\Message;
use PHPUnit\Framework\TestCase;

/**
 * Messages larger than the PCRE backtrack limit must still parse. The XML declaration used to be
 * stripped with preg_replace(), which returned null on a long single line and made parse() throw a
 * TypeError, so any big metaWeblog.newMediaObject upload failed with a 500.
 */
final class XmlRpcLargeMessageTest extends TestCase
{
    private const DECLARATION = '<?xml version="1.0" encoding="UTF-8"?>';

    public function testTwoMegabyteMediaObjectWithDeclarationParses(): void
    {
        $bits = str_repeat('A', 2 * 1024 * 1024);
        $message = new Message(self::DECLARATION . self::newMediaObject($bits));

        $this->assertTrue($message->parse());
        $this->assertSame('metaWeblog.newMediaObject', $message->methodName);
        $this->assertSame('photo.jpg', $message->params[3]['name']);
        $this->assertSame(base64_decode($bits), $message->params[3]['bits']);
    }

    public function testSmallMessageWithDeclarationParses(): void
    {
        $message = new Message(self::DECLARATION . "\n" . self::newMediaObject(base64_encode('tiny')));

        $this->assertTrue($message->parse());
        $this->assertSame('methodCall', $message->messageType);
        $this->assertSame('tiny', $message->params[3]['bits']);
    }

    public function testMessageWithoutDeclarationParses(): void
    {
        $message = new Message(self::newMediaObject(base64_encode('tiny')));

        $this->assertTrue($message->parse());
        $this->assertSame('tiny', $message->params[3]['bits']);
    }

    public function testBlankMessageIsRejected(): void
    {
        $this->assertFalse((new Message(''))->parse());
        $this->assertFalse((new Message(" \n\t "))->parse());
    }

    /**
     * The whole request on one line, the way most clients send it.
     */
    private static function newMediaObject(string $bits): string
    {
        return '<methodCall><methodName>metaWeblog.newMediaObject</methodName><params>'
            . '<param><value><string>1</string></value></param>'
            . '<param><value><string>admin</string></value></param>'
            . '<param><value><string>secret</string></value></param>'
            . '<param><value><struct>'
            . '<member><name>name</name><value><string>photo.jpg</string></value></member>'
            . '<member><name>type</name><value><string>image/jpeg</string></value></member>'
            . '<member><name>bits</name><value><base64>' . $bits . '</base64></value></member>'
            . '</struct></value></param>'
            . '</params></methodCall>';
    }
}
