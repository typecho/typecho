<?php
use PHPUnit\Framework\TestCase;

/**
 * The comment list used to crash when the site has replies but the
 * threaded comment option is off.
 *
 * In that case Archive::execute() never computes the order of a comment,
 * so $this->order is null. alt() reads the order for a comment that has a
 * level, and altBy() takes an int, which made the whole list fail.
 *
 * The real alt() and altBy() implementations are taken from the source
 * files, so a change to the code changes the result.
 */
final class CommentAltTest extends TestCase
{
    /**
     * @var string
     */
    private $altBody;

    /**
     * @var string
     */
    private $altByBody;

    /**
     * Extract the two implementations from the source files and turn them
     * into plain functions.
     */
    protected function setUp(): void
    {
        $widget = file_get_contents(
            dirname(__DIR__, 2) . '/var/Typecho/Widget.php'
        );

        if (!preg_match('/public function altBy\((int|\?int) \$current, \.\.\.\$args\)\s*\{.*?\n    \}/s', $widget, $matches)) {
            $this->fail('cannot extract altBy() from var/Typecho/Widget.php');
        }

        $this->assertSame(
            'int',
            strpos($matches[0], '?int') !== false ? '?int' : 'int',
            'altBy() must keep its int parameter, the fix must not widen it'
        );

        $this->altByBody = str_replace(
            ['public function altBy(int $current, ...$args)', 'public function altBy(?int $current, ...$args)'],
            'function altBy_impl(int $current, ...$args)',
            $matches[0]
        );

        $archive = file_get_contents(
            dirname(__DIR__, 2) . '/var/Widget/Comments/Archive.php'
        );

        if (!preg_match('/public function alt\(\.\.\.\$args\)\s*\{.*?\n    \}/s', $archive, $matches)) {
            $this->fail('cannot extract alt() from var/Widget/Comments/Archive.php');
        }

        $this->altBody = str_replace(
            [
                'public function alt(...$args)',
                '$this->levels',
                '$this->order',
                '$this->sequence',
                '$this->altBy',
            ],
            [
                'function alt_impl(...$args)',
                '$GLOBALS["__levels"]',
                '$GLOBALS["__order"]',
                '$GLOBALS["__sequence"]',
                'altBy_impl',
            ],
            $matches[0]
        );

        if (!function_exists('altBy_impl')) {
            eval($this->altByBody);
        }

        if (!function_exists('alt_impl')) {
            eval($this->altBody);
        }
    }

    /**
     * Run alt() with the given state and return what it printed.
     */
    private function alt(int $levels, int $sequence, ?int $order): string
    {
        $GLOBALS['__levels'] = $levels;
        $GLOBALS['__sequence'] = $sequence;
        $GLOBALS['__order'] = $order;

        ob_start();

        try {
            alt_impl(' odd', ' even');
        } catch (\Throwable $e) {
            ob_end_clean();

            throw $e;
        }

        return ob_get_clean();
    }

    public function testTopLevelCommentKeepsWorking(): void
    {
        $output = $this->alt(0, 0, null);

        $this->assertContains($output, [' odd', ' even']);
    }

    /**
     * The crash itself: a reply without an order must not raise.
     */
    public function testReplyWithoutOrderDoesNotRaise(): void
    {
        $output = $this->alt(1, 0, null);

        $this->assertContains($output, [' odd', ' even']);
    }

    public function testReplyWithOrderKeepsTheOriginalResult(): void
    {
        $output = $this->alt(1, 0, 2);

        $this->assertSame(' even', $output);
    }

    public function testConsecutiveRepliesAlternate(): void
    {
        $first = $this->alt(1, 0, null);
        $second = $this->alt(1, 1, null);
        $third = $this->alt(1, 2, null);

        $this->assertNotSame($first, $second);
        $this->assertNotSame($second, $third);
        $this->assertSame($first, $third);
    }
}
