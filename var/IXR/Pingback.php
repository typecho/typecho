<?php

namespace IXR;

use Typecho\Common;
use Typecho\Http\Client as HttpClient;
use Typecho\Http\Client\Exception as HttpException;

/**
 * fetch pingback
 */
class Pingback
{
    /**
     * @var string
     */
    private $html;

    /**
     * @var string
     */
    private $target;

    /**
     * @param string $url
     * @param string $target
     * @throws Exception
     */
    public function __construct(string $url, string $target)
    {
        $client = HttpClient::get();
        $this->target = $target;

        if (!isset($client)) {
            throw new Exception('No available http client', 50);
        }

        try {
            $client->setTimeout(5)
                ->send($url);
        } catch (HttpException $e) {
            throw new Exception('Pingback http error', 50);
        }

        if ($client->getResponseStatus() != 200) {
            throw new Exception('Pingback wrong http status', 50);
        }

        $response = $client->getResponseBody();
        $encoding = 'UTF-8';
        $contentType = $client->getResponseHeader('Content-Type');

        if (!empty($contentType) && preg_match("/charset=([_a-z0-9-]+)/i", $contentType, $matches)) {
            $encoding = strtoupper($matches[1]);
        } elseif (preg_match("/<meta\s+charset=\"([_a-z0-9-]+)\"/i", $response, $matches)) {
            $encoding = strtoupper($matches[1]);
        }

        $this->html = $encoding == 'UTF-8' ? $response : mb_convert_encoding($response, 'UTF-8', $encoding);

        if (
            !$client->getResponseHeader('X-Pingback') &&
            !preg_match_all("/<link[^>]*rel=[\"']pingback[\"'][^>]+href=[\"']([^\"']*)[\"'][^>]*>/i", $this->html)
        ) {
            throw new Exception("Source server doesn't support pingback", 50);
        }
    }

    /**
     * get title
     *
     * @return string
     */
    public function getTitle(): string
    {
        if (preg_match("/\<title\>([^<]*?)\<\/title\\>/is", $this->html, $matchTitle)) {
            return Common::subStr(Common::removeXSS(trim(strip_tags($matchTitle[1]))), 0, 150, '...');
        }

        return parse_url($this->target, PHP_URL_HOST);
    }

    /**
     * get content
     *
     * @return string
     * @throws Exception
     */
    public function getContent(): string
    {
        /** 干掉html tag，只留下<a>*/
        $text = Common::stripTags($this->html, '<a href="">');

        /** 此处将$target quote,留着后面用*/
        $pregLink = preg_quote($this->target);

        /** 找出含有target链接的最长的一行作为$finalText*/
        $finalText = null;
        $lines = explode("\n", $text);

        foreach ($lines as $line) {
            $line = trim($line);
            if (null != $line) {
                if (preg_match("|<a[^>]*href=[\"']{$pregLink}[\"'][^>]*>(.*?)</a>|", $line)) {
                    if (strlen($line) > strlen($finalText)) {
                        /** <a>也要干掉，*/
                        $finalText = Common::stripTags($line);
                        break;
                    }
                }
            }
        }

        if (!isset($finalText)) {
            throw new Exception("Source page doesn't have target url", 50);
        }

        return '[...]' . Common::subStr($finalText, 0, 200, '') . '[...]';
    }
}
