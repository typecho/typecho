<?php
use IXR\Message;
use IXR\Request;
use IXR\Server;
use IXR\Value;
use PHPUnit\Framework\TestCase;

/**
 * Golden tests that pin the current XML-RPC behaviour byte for byte.
 *
 * Each tests/fixtures/xmlrpc/<name>.xml is run through Message::parse(), re-encoded with
 * Value/Request, and fed to Server::handle(); the result must match <name>.golden.json.
 * These record what the code does today, bugs included, so a parser rewrite can prove it
 * changed nothing. Regenerate deliberately with IXR_GOLDEN_UPDATE=1 tools/phpunit.sh.
 *
 * Value escapes with htmlspecialchars() defaults, which changed in PHP 8.1 (quotes and
 * invalid UTF-8), so where PHP 7.4 encodes differently it gets <name>.golden.php7.json.
 *
 * Known bugs recorded as-is (to be fixed in stage two, together with their goldens):
 * - type-boolean-false: <boolean>false</boolean> parses as true ((bool) "false")
 * - edge-empty-value: <value></value> and <value/> are dropped instead of becoming ''
 * - edge-whitespace-untyped, type-untyped-value, edge-text-between-tags: untyped values
 *   keep whitespace and stray text from previous markup, because the character buffer is
 *   only reset by some closing tags
 * - edge-nil, edge-i8: <nil/> is dropped and <i8> falls back to an untyped string
 * - type-datetime-extended: "2026-10-22T08:30:15Z" is sliced by fixed offsets into garbage
 * - type-base64-binary: binary strings encode as <string>, mangled by htmlspecialchars()
 * - client-system-multicall: multiCall() tests is_a($result, 'Error'), i.e. the global
 *   \Error, so faults are wrapped and encoded as a struct instead of faultCode/faultString
 *
 * Goldens changed by the switch from ext/xml to DOM, accepted on purpose:
 * - edge-truncated (intentional fix, not a regression): ext/xml called xml_parse() without
 *   is_final, so a truncated request parsed as a success and Server actually executed the
 *   method. DOM reports it as not well formed: -32700 instead of -32601.
 * - edge-invalid-utf8, edge-latin1-declared, edge-mismatched-tags, edge-undefined-entity:
 *   parse() still returns false, and the handle() response and re-encoded output are
 *   byte-identical. Only the public properties left behind after a failed parse differ
 *   (ext/xml filled them while parsing, DOM fills nothing on failure). Server and Client
 *   discard the message when parse() is false, so this is not observable.
 */
final class XmlRpcCodecTest extends TestCase
{
    private const FIXTURES = __DIR__ . '/../fixtures/xmlrpc';

    public static function fixtureProvider(): array
    {
        $cases = [];
        foreach (glob(self::FIXTURES . '/*.xml') as $file) {
            $name = basename($file, '.xml');
            $cases[$name] = [$name];
        }

        return $cases;
    }

    /**
     * @dataProvider fixtureProvider
     */
    public function testGolden(string $name): void
    {
        $xml = file_get_contents(self::FIXTURES . '/' . $name . '.xml');
        $actual = json_encode(
            self::record($xml),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ) . "\n";

        $golden = self::FIXTURES . '/' . $name . '.golden.json';
        $legacy = self::FIXTURES . '/' . $name . '.golden.php7.json';
        if (getenv('IXR_GOLDEN_UPDATE')) {
            if (PHP_VERSION_ID >= 80100) {
                file_put_contents($golden, $actual);
            } elseif (file_get_contents($golden) !== $actual) {
                file_put_contents($legacy, $actual);
            }
        }
        if (PHP_VERSION_ID < 80100 && file_exists($legacy)) {
            $golden = $legacy;
        }

        $this->assertFileExists($golden);
        $this->assertSame(file_get_contents($golden), $actual);
    }

    private static function record(string $xml): array
    {
        $message = new Message($xml);
        $record = ['parse' => self::capture(function () use ($message) {
            return $message->parse();
        })];

        foreach (['messageType', 'methodName', 'params', 'faultCode', 'faultString'] as $property) {
            $ref = new ReflectionProperty($message, $property);
            $record[$property] = $ref->isInitialized($message) ? self::dump($ref->getValue($message)) : '<uninitialized>';
        }

        $record['encoded'] = null;
        if (($record['parse']['result'] ?? null) === true) {
            $record['encoded'] = self::capture(function () use ($message) {
                if ($message->messageType == 'methodCall') {
                    return self::lines((new Request($message->methodName, $message->params))->getXml());
                }

                return self::lines((new Value($message->params))->getXml());
            });
        }

        $record['response'] = self::capture(function () use ($xml) {
            return self::lines((new Server())->handle($xml));
        });

        return $record;
    }

    /**
     * Run $fn and record its result, any throwable (by class, messages vary across PHP
     * versions) and how many warnings/notices it raised. Deprecations are left to PHPUnit.
     */
    private static function capture(callable $fn): array
    {
        $errors = 0;
        set_error_handler(function () use (&$errors) {
            $errors++;
            return true;
        }, E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

        try {
            $result = ['result' => $fn()];
        } catch (Throwable $e) {
            $result = ['throws' => get_class($e)];
        } finally {
            restore_error_handler();
        }

        $result['php_errors'] = $errors;
        return $result;
    }

    private static function lines($xml)
    {
        return is_string($xml) ? explode("\n", $xml) : self::dump($xml);
    }

    /**
     * A type-preserving, JSON-safe view of a parsed value.
     */
    private static function dump($value)
    {
        if ($value === null || is_bool($value)) {
            return $value;
        }
        if (is_int($value)) {
            return ['int' => $value];
        }
        if (is_float($value)) {
            return ['double' => var_export($value, true)];
        }
        if (is_string($value)) {
            return preg_match('//u', $value) ? ['string' => $value] : ['bytes' => base64_encode($value)];
        }
        if (is_array($value)) {
            if ($value === [] || array_keys($value) === range(0, count($value) - 1)) {
                return ['list' => array_map([self::class, 'dump'], $value)];
            }

            $pairs = [];
            foreach ($value as $key => $item) {
                $pairs[] = [$key, self::dump($item)];
            }
            return ['map' => $pairs];
        }

        $props = [];
        foreach ((new ReflectionObject($value))->getProperties() as $ref) {
            if (PHP_VERSION_ID < 80100) {
                $ref->setAccessible(true);
            }
            $props[$ref->getName()] = $ref->isInitialized($value) ? self::dump($ref->getValue($value)) : '<uninitialized>';
        }
        return ['object' => get_class($value), 'props' => $props];
    }
}
