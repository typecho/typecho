<?php
/**
 * curl stubs for tests/Security/HttpClientRebindRecheckTest.php.
 *
 * Defined in the Typecho\Http namespace so that Client's unqualified curl_*() calls
 * resolve here instead of ext-curl, letting the test drive a safe-mode request without
 * any network. Behaviour is driven by $GLOBALS['__http_stub__']:
 *
 *   exec    => value returned by curl_exec() (false simulates a transport failure)
 *   error   => curl_error() string used when exec === false
 *   info    => [CURLINFO_* => value] returned by curl_getinfo()
 *   options => collected curl_setopt() values, appended by the stub
 *
 * MUST only be loaded inside an isolated process: once defined, these functions shadow
 * ext-curl for every Client in the process. The test class uses
 * @runTestsInSeparateProcesses and requires this file from setUp(), so the main
 * PHPUnit process and every other test class stay unaffected.
 */

namespace Typecho\Http;

function curl_init()
{
    return fopen('php://memory', 'r');
}

function curl_setopt($ch, int $option, $value): bool
{
    $GLOBALS['__http_stub__']['options'][$option] = $value;
    return true;
}

function curl_exec($ch)
{
    return $GLOBALS['__http_stub__']['exec'];
}

function curl_error($ch): string
{
    return $GLOBALS['__http_stub__']['error'] ?? 'stub transport failure';
}

function curl_getinfo($ch, ?int $option = null)
{
    $info = $GLOBALS['__http_stub__']['info'];
    return null === $option ? $info : ($info[$option] ?? null);
}

function curl_close($ch): void
{
}
