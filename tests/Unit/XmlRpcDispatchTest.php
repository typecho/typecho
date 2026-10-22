<?php
use IXR\Server;
use PHPUnit\Framework\TestCase;

/**
 * Golden tests for what Server::handle() actually passes to the methods behind it.
 *
 * Each client-*.xml fixture is dispatched to XmlRpcSignatureStub, whose methods mirror the
 * signatures of Widget\XmlRpc and echo their arguments back; the response must match
 * <name>.dispatch.json. Regenerate with IXR_GOLDEN_UPDATE=1, like XmlRpcCodecTest.
 *
 * Known bugs recorded as-is:
 * - client-metaweblog-editpost-no-publish: the omitted optional $publish is settype()'d from
 *   null into false and passed on, so the post is saved as a draft instead of published
 */
final class XmlRpcDispatchTest extends TestCase
{
    private const FIXTURES = __DIR__ . '/../fixtures/xmlrpc';

    private const METHODS = [
        'metaWeblog.newPost'        => 'mwNewPost',
        'metaWeblog.editPost'       => 'mwEditPost',
        'metaWeblog.getRecentPosts' => 'mwGetRecentPosts',
        'wp.getUsersBlogs'          => 'wpGetUsersBlogs',
        'blogger.getUsersBlogs'     => 'bloggerGetUsersBlogs',
        'mt.getRecentPostTitles'    => 'mtGetRecentPostTitles',
        'pingback.ping'             => 'pingbackPing',
    ];

    public static function fixtureProvider(): array
    {
        $cases = [];
        foreach (glob(self::FIXTURES . '/client-*.xml') as $file) {
            $name = basename($file, '.xml');
            $cases[$name] = [$name];
        }

        return $cases;
    }

    /**
     * @dataProvider fixtureProvider
     */
    public function testDispatchGolden(string $name): void
    {
        $stub = new XmlRpcSignatureStub();
        $callbacks = [];
        foreach (self::METHODS as $rpcName => $method) {
            $callbacks[$rpcName] = [$stub, $method];
        }

        $xml = file_get_contents(self::FIXTURES . '/' . $name . '.xml');
        $actual = json_encode(
            explode("\n", (new Server($callbacks))->handle($xml)),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ) . "\n";

        $golden = self::FIXTURES . '/' . $name . '.dispatch.json';
        if (getenv('IXR_GOLDEN_UPDATE')) {
            file_put_contents($golden, $actual);
        }

        $this->assertFileExists($golden);
        $this->assertSame(file_get_contents($golden), $actual);
    }

    public function testStubMirrorsWidgetSignatures(): void
    {
        foreach (self::METHODS as $method) {
            $this->assertSame(
                self::signature(new ReflectionMethod(\Widget\XmlRpc::class, $method)),
                self::signature(new ReflectionMethod(XmlRpcSignatureStub::class, $method)),
                $method
            );
        }
    }

    private static function signature(ReflectionMethod $method): array
    {
        return array_map(function (ReflectionParameter $parameter) {
            return (string) $parameter;
        }, $method->getParameters());
    }
}

/**
 * Same parameter lists as Widget\XmlRpc; each method returns the arguments it received.
 */
final class XmlRpcSignatureStub
{
    public function mwNewPost(int $blogId, string $userName, string $password, array $content, bool $publish): array
    {
        return func_get_args();
    }

    public function mwEditPost(
        int $postId,
        string $userName,
        string $password,
        array $content,
        bool $publish = true
    ): array {
        return func_get_args();
    }

    public function mwGetRecentPosts(int $blogId, string $userName, string $password, int $postsNum): array
    {
        return func_get_args();
    }

    public function wpGetUsersBlogs(string $userName, string $password): array
    {
        return func_get_args();
    }

    public function bloggerGetUsersBlogs(int $blogId, string $userName, string $password): array
    {
        return func_get_args();
    }

    public function mtGetRecentPostTitles(int $blogId, string $userName, string $password, int $postsNum): array
    {
        return func_get_args();
    }

    public function pingbackPing(string $source, string $target): array
    {
        return func_get_args();
    }
}
