<?php

/**
 * Project:  RSS Fetch
 * File:     RssParser.php
 *
 * Copyright (c) 2015, Drew Phillips
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *  - Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * Any modifications to the library should be indicated clearly in the source code
 * to inform users that the changes are not a part of the original software.
 *
 * If you found this script useful, please take a quick moment to rate it.
 * http://www.hotscripts.com/rate/48456.html  Thanks.
 *
 * @link http://www.neoprogrammers.com RSS Fetch
 * @link http://neoprogrammers.com/download.php?file=RssParser.zip Download Latest Version
 * @copyright 2015 Drew Phillips
 * @author Drew Phillips <drew@drew-phillips.com>
 * @package Dapphp\RssParser
 * @version 1.1.0 (Apr 2, 2018)
 */

namespace Dapphp\RssParser;

use Dapphp\RssParser\FeedException;

/**
 * Dapphp\RssParser
 *
 * A PHP5 class for parsing RSS feeds and displaying the contents on webpages
 *
 * Using this class the contents of an RSS feed can be displayed on any PHP
 * enabled webpage in 2 lines of code.  Uses no external dependencies
 * (e.g. cURL) and requires PHP 5.3 or greater.  Supports http/https URLs,
 * caching of feed contents, and can display feed contents using simple
 * templates.
 *
 * @package  Dapphp\RssParser
 */
class RssParser
{
    /* Path to the cache directory */
    private $_cacheDir;

    /* How often (in seconds) to download the remote feed content */
    private $_cacheLifetime = -1;

    /* html template for rendering a feed entry */
    private $_itemTemplate;

    /* encoding to use for translating HTML entities */
    private $_encoding = 'UTF-8';

    /* feed items */
    private $_feedItems = array();

    /**
     * RssParser constructor
     *
     * @param array $options An array of options to set
     */
    public function __construct($options = array())
    {
        if (is_array($options)) {
            foreach($options as $name => $value) {
                $method = 'set' . ucfirst($name);
                if (method_exists($this, $method)) {
                    $this->$method($value);
                } else {
                    trigger_error("Unknown option {$name}", E_USER_WARNING);
                }
            }
        } else {
            trigger_error("Invalid argument passed to RssParser::__construct(), expected array()", E_USER_WARNING);
        }
    }

    /**
     * Set the directory for storing cached feed content
     *
     * @param string $cacheDir The path to the directory for cache files (must exist and be writable)
     * @return Dapphp\RssParser\RssParser
     * @throws \Exception If cache directory does not exist or is not writeable
     */
    public function setCacheDir($cacheDir)
    {
        if (!file_exists($cacheDir)) {
            throw new \Exception("Cache directory '{$cacheDir}' does not exist");
        } else if (!is_writable($cacheDir)) {
            throw new \Exception("Cache directory '{$cacheDir}' is not writable");
        }

        $this->_cacheDir = rtrim($cacheDir, '/\\');
        return $this;
    }

    /**
     * Get the cache directory path
     *
     * @return string Path to cache directory, null if not set
     */
    public function getCacheir()
    {
        return $this->_cacheDir;
    }

    /**
     * Set the cache lifetime (in seconds) for the feed being parsed.  Use a
     * value of 0 or less to disable caching
     *
     *
     * @param int $cacheLifetime Age in seconds to keep a cached feed before retrieving a new copy
     * @return Dapphp\RssParser\RssParser
     */
    public function setCacheLifetime($cacheLifetime)
    {
        $this->_cacheLifetime = (int)$cacheLifetime;
        return $this;
    }

    /**
     * Gets the current cache lifetime value
     *
     * @return int The cache lifetime in seconds
     */
    public function getCacheLifeTime()
    {
        return $this->_cacheLifetime;
    }

    /**
     * Set the content encoding to use when decoding feed content
     *
     * @param string $encoding Encoding to use (e.g. ISO-8859-1, UTF-8)
     * @return \Dapphp\RssParser\RssParser
     */
    public function setEncoding($encoding)
    {
        $this->_encoding = $encoding;
        return $this;
    }

    public function getEncoding()
    {
        return $this->_encoding;
    }

    /**
     * Download and parse an RSS feed (or prepare results from cache)
     *
     * @param string $url The URL beginning with http:// or https:// of the feed to parse
     * @return Dapphp\RssParser\RssParser
     * @throws \Exception Throws exception if feed URL cannot be retrieved, or if RSS feed format cannot be parsed
     */
    public function parse($url)
    {
        $cacheFile = $this->_getCacheFileName($url);
        if ($this->_cacheLifetime > 0 && file_exists($cacheFile) && time() - filemtime($cacheFile) < $this->_cacheLifetime) {
            $this->_feedItems = unserialize(file_get_contents($cacheFile));
        } else {
            $fetch  = true;
            $redirs = 0;

            do {
                $content = $this->_fetchFeed($url);
                list($headers, $content) = explode("\r\n\r\n", $content, 2);
                $headers = $this->_parseHeaders($headers);
                $status  = $headers['status_code'];

                if ($status == '200') {
                    $fetch = false;
                } else if ($status == '301' || $status == '302') {
                    if (!isset($headers['headers']['location'])) {
                        throw new \Exception("Bad redirect.  Server sent a {$status} status but response is missing 'Location' header");
                    }
                    $url = $headers['headers']['location'];
                    $redirs++;
                } else {
                    throw new \Exception("HTTP request failed.  {$status} {$headers['message']}", $status);
                }

                if ($redirs >= 25) {
                    throw new \Exception('Redirection limit exceeded. The site is redirecting the request in a way that will never complete.');
                }
            } while ($fetch);

            $this->_feedItems = $this->_parseFeed($content);

            if ($this->_cacheLifetime > 0) {
                file_put_contents($cacheFile, serialize($this->_feedItems));
            }
        }

        return $this;
    }

    /**
     * Sets the HTML item template to be used when calling RssParser::show()
     *
     * @param string $html The HTML content template for showing feed items
     * @return Dapphp\RssParser\RssParser
     */
    public function setItemTemplate($html)
    {
        $this->_itemTemplate = $html;
        return $this;
    }

    /**
     * Gets the current HTML item template, or null if not set
     *
     * @return string The HTML template for showing feed items
     */
    public function getItemTemplate()
    {
        return $this->_itemTemplate;
    }

    /**
     * Output the formatted contents of an RSS feed using a built-in or supplied HTML template
     *
     * @param int $numberOfItems The maximum number of items to display
     * @param int $offset The starting item number for displaying feeds (from 0)
     * @return string The formatted feed HTML content
     */
    public function show($numberOfItems = 0, $offset = 0)
    {
        $content  = '';
        $template = $this->getItemTemplate();
        if (empty($template)) {
            $template = "<a href=\"#{link}\" target=\"_new\">#{title}</a><br>#{description}<br><br>\n";
        }

        if ($numberOfItems < 1) {
            $numberOfItems = sizeof($this->_feedItems);
        }

        for ($i = 0, $c = 0; $c < $numberOfItems; ++$i) {
            if ($i < $offset) continue;
            if ($i >= sizeof($this->_feedItems)) break;
            if ($c >= $numberOfItems) break;

            $item  = $this->_feedItems[$i];
            $entry = $template;
            $count = preg_match_all('/#\{([^}]+)}/', $template, $tags);
            for ($m = 0; $m < $count; ++$m) {
                if (strpos($tags[1][$m], '.') !== false) {
                    list($tag, $attr) = explode('.', $tags[1][$m], 2);
                    $value = $item[$tag]['attributes'][$attr];
                } else {
                    $value = $item[$tags[1][$m]]['content'];
                }
                $entry = str_replace('#{' . $tags[1][$m] . '}', $this->unHtmlEntities($value), $entry);
            }
            $content .= $entry;
            $c++;
        }

        return $content;
    }

    /**
    * Returns the feed <item> entries.
    * Item content accessed using $items[$n]['tagName']['content']
    * Item attributes accessed using $items[$n]['tagName']['attributes']['attribName']
    *
    * @return array Array of feed items
    */
    public function getItems()
    {
        reset($this->_feedItems);
        return $this->_feedItems;
    }

    /**
     * Download the RSS feed from the remote URL
     *
     * @param string $url The URL of the feed to download
     * @throws \Exception Throws exception if the feed could not be downloaded
     * @return string The RSS feed content
     */
    private function _fetchFeed($url)
    {
        $urlParts  = parse_url($url);
        $content   = '';

        if ($urlParts['scheme'] == 'https') {
            $host = 'ssl://' . $urlParts['host'];
            $port = (isset($urlParts['port'])) ? $urlParts['port'] : 443;
        } else {
            $host = $urlParts['host'];
            $port = (isset($urlParts['port'])) ? $urlParts['port'] : 80;
        }

        $sock = fsockopen($host, $port, $errno, $errstr, 5);
        if (!$sock) {
            throw new \Exception("Failed to connect to {$host}.  Error {$errno}: {$errstr}");
        }

        $request = sprintf(
            "GET %s%s HTTP/1.0\r\n" .
            "Host: %s\r\n" .
            "Accept: */*\r\n" .
            "Connection: close\r\n\r\n",
            $urlParts['path'],
            (!empty($urlParts['query']) ? '?' . $urlParts['query'] : ''),
            $urlParts['host']
        );

        fputs($sock, $request);

        while (!feof($sock)) {
            $content .= fgets($sock, 8192);
        }

        fclose($sock);

        return $content;
    }

    /**
     * Parses the feed content
     *
     * @param string$content The RSS feed contents
     * @throws \Exception Throws exception if no <item> elements found or if feed content is malformed
     * @return array The parsed feed items
     */
    private function _parseFeed($content)
    {
        $entries = array();
        $title   = $link = $description = '';

        if (preg_match("/<title>(.*?)<\/title>/", $content, $title)) {
            $title = $title[1];
        }

        if (preg_match("/<link>(.*?)<\/link>/", $content, $link)) {
            $link = $link[1];
        }

        if (preg_match("/<description>(.*?)<\/description>/", $content, $description)) {
            $description = $description[1];
        }

        $numItems = preg_match_all("/<item[^>]*>(.*?)<\/item>/s", $content, $items);

        if ($numItems < 1) {
            throw new FeedException('No &lt;item&gt; elements found in feed content');
        }

        for($i = 0; $i < $numItems; ++$i) {
            $item      = preg_replace('#</?item[^>]*>#i', '', $items[0][$i]);
            $entries[] = $this->_readTags($item);

        }

        return $entries;
    }

    /**
     * Parse http response headers into associative array
     *
     * @param string $headers String of http headers to parse
     * @return array Parsed array of headers
     */
    private function _parseHeaders($headers)
    {
        $lines    = explode("\r\n", $headers);
        $response = array_shift($lines);
        $header   = array();

        if (!preg_match('/^HTTP\/\d\.\d (\d{3}) (.*)$/i', $response, $match)) {
            throw new \Exception('Bad response.  The server sent a malformed HTTP response. "{$response}"');
        }

        $code    = $match[1];
        $message = $match[2];

        foreach($lines as $line) {
            if (strpos($line, ':') === false) {
                throw new \Exception('Bad response.  Missing : separator. "{$response}"');
            }
            list($name, $value) = explode(':', $line, 2);
                $header[strtolower($name)] = trim($value);
            }

        return array(
            'status_code' => $code,
            'message'     => $message,
            'headers'     => $header,
        );
    }

    /**
     * State machine for parsing <item> tag contents
     *
     * @param string $content The inner content of the <item> tag to parse
     * @param bool $recursed True if the function is being called recursively
     * @param int $offset Starting offset of $content when parsing recursively
     * @throws \Exception If item content does not contain valid RSS code
     * @return array Array of parsed tags
     */
    private function _readTags($content, $recursed = false, &$offset = null)
    {
        $tags  = array();
        $state = 'open_tag_lt';
        $start = ($offset != null ? $offset : 0);

        for ($i = $start; $i < strlen($content); ++$i) {
            $char = $content[$i];

            switch ($state) {
                case 'open_tag_lt':
                    if (trim($char) == '') continue;
                    if ($char != '<') {
                        throw new FeedException("Error parsing &lt;item&gt; content; expected &lt;, got $char");
                    }
                    $tagName      = '';
                    $tagContent   = null;
                    $closeTagName = '';
                    $attributes   = array();
                    $state        = 'tag_name';
                    break;

                case 'tag_name':
                    if ($char == '/') {
                        $state = 'open_tag_short_gt';
                    } else if ($char == '>') {
                        $state = 'tag_content';
                    } else if ($char == ' ') {
                        $state = 'tag_attribute_begin';
                    } else {
                        $tagName .= $char;
                    }
                    break;

                case 'open_tag_short_gt':
                    if (trim($char) == '') {
                        continue;
                    } else if ($char == '>') {
                        $state = 'close_tag_end';
                    } else {
                        throw new FeedException("Error parsing opening tag; expected &gt;, got $char");
                    }
                    break;

                case 'tag_content_begin':
                    if ($char == '<') {
                        if ($i + 8 < strlen($content) && substr($content, $i, 9) == '<![CDATA[') {
                            $state = 'tag_content_cdata';
                            $i += 8;
                        } else {
                            $state = 'close_tag_lt';
                        }
                    } else {
                        $tagContent .= $char;
                        $state       = 'tag_content';
                    }
                    break;

                case 'tag_content':
                    while ($char != '<') {
                        if (!is_array($tagContent))
                            $tagContent .= $char;

                        if ($i + 1 > strlen($content)) {
                            throw new FeedException("Unexpected \$end while reading {$tagName} contents");
                        }
                        $char = $content[++$i];
                    }
                    if ($char == '<') {
                        if ($i + 8 < strlen($content) && substr($content, $i, 9) == '<![CDATA[') {
                            $state = 'tag_content_cdata';
                            $i += 8;
                        } else {
                            $state = 'close_tag_lt';
                        }
                    } else {
                        $tagContent .= $char;
                    }
                    break;

                case 'tag_content_cdata':
                    if ($char == ']' && substr($content, $i + 1, 2) == ']>') {
                        $i += 2;
                        $state = 'tag_content';
                    } else {
                        $tagContent .= $char;
                    }
                    break;

                case 'tag_attribute_begin':
                    $attribName  = '';
                    $attribValue = '';
                    $i--;

                    $state = 'tag_attribute_name_begin';
                    break;

                case 'tag_attribute_name_begin':
                    if (trim($char) == '') {
                        continue;
                    } else if ($char == '>') {
                        $state = 'tag_content';
                    } else if ($char == '/') {
                        $state = 'open_tag_short_gt';
                    } else if (preg_match('/[a-z_]/i', $char)) {
                        $attribName .= $char;
                        $state = 'tag_attribute_name';
                    } else {
                        throw new FeedException("Unexpected character $char while parsing opening tag attribute name");
                    }
                    break;

                case 'tag_attribute_name':
                    if (preg_match('/[a-z0-9_-]/i', $char)) {
                        $attribName .= $char;
                    } else if ($char == ' ') {
                        $state = 'tag_attribute_equals';
                    } else if ($char == '=') {
                        $state = 'tag_attribute_value_quote';
                    } else if ($char == '/') {
                        $attributes[$attribName] = null;
                        $state = 'open_tag_short_gt';
                    } else if ($char == '>') {
                        $attributes[$attribName] = null;
                        $state = 'tag_content';
                    } else {
                        throw new FeedException("Unexpected character $char while parsing opening tag attribute name");
                    }
                    break;

                case 'tag_attribute_equals':
                    if ($char == '=') {
                        $state = 'tag_attribute_value';
                    } else if ($char == '/') {
                        $attributes[$attribName] = null;
                        $state = 'open_tag_short_gt';
                    } else if ($char == '>') {
                        $attributes[$attribName] = null;
                        $state = 'tag_content';
                    } else {
                        throw new FeedException("Expected attribute value; got $char");
                    }
                    break;

                case 'tag_attribute_value_quote':
                    if (trim($char) == '') {
                        continue;
                    } else if ($char == '"' || $char == "'") {
                        $quoteChar = $char;
                        $state = 'tag_attribute_value';
                    } else {
                        throw new FeedException("Error parsing attribute value; expected quotes, got $char");
                    }
                    break;

                case 'tag_attribute_value':
                    if ($char == $quoteChar) {
                        $attributes[$attribName] = $attribValue;

                        $state = 'tag_attribute_begin';
                    } else {
                        $attribValue .= $char;
                    }
                    break;

                case 'close_tag_lt':
                    if ($char != '/') {
                        // nested tags
                        $o = $i - 1;
                        $tagContent = $this->_readTags($content, true, $o);
                        $state = 'tag_content';
                        $i = $o + 1;
                    } else {
                        $state = 'close_tag_name';
                    }
                    break;

                case 'close_tag_name':
                    if ($char == '>') {
                        if ($closeTagName != $tagName) {
                            throw new FeedException("Expected closing tag for $tagName; got $closeTagName");
                        }

                        $state = 'close_tag_end';
                    } else {
                        $closeTagName .= $char;
                    }
                    break;
            }

            if ($state == 'close_tag_end') {
                $tag = array(
                    'content'    => (is_array($tagContent) ? $tagContent : trim($tagContent)),
                    'attributes' => $attributes,
                );

                if (isset($tags[$tagName])) {
                    if (isset($tags[$tagName]['content'])) {
                        $tmp = $tags[$tagName];
                        unset($tags[$tagName]);
                        $tags[$tagName] = array($tmp);
                    }

                    $tags[$tagName][] = $tag;
                } else {
                    $tags[$tagName] = $tag;
                }

                $state = 'open_tag_lt';

                if ($recursed == true) {
                    // break out of nested parsing and return to caller
                    $offset = $i;
                    return $tags;
                }
            }
        }

        if ($state != 'open_tag_lt') {
            throw new FeedException("Unexpected end of feed; current state: $state");
        }

        return $tags;
    }

    /**
     * Get the cache file name to use based on the feed URL
     *
     * @param string $url The URL of the RSS feed being parsed
     * @return string The cache file name and path
     */
    private function _getCacheFileName($url)
    {
        $urlParts  = parse_url($url);
        $cacheFile = $urlParts['host'] . '-' . str_replace('/', '_', $urlParts['path']) . '-' . @$urlParts['query'] . '.dat';
        $cacheDir  = (empty($this->_cacheDir) ? '.' : $this->_cacheDir);
        $cacheFile = rtrim($cacheDir, '/\\') . DIRECTORY_SEPARATOR . $cacheFile;

        return $cacheFile;
    }

    /**
     * Decode HTML entities into corresponding characters
     *
     * @param string $string The content string to decode
     * @return string The decoded content
     */
    public function unHtmlEntities($string)
    {
        $entHtml5  = (defined('ENT_HTML5') ? ENT_HTML5 : 48); // not defined in PHP < 5.4.0
        $trans_tbl = get_html_translation_table(HTML_ENTITIES, ENT_COMPAT | $entHtml5, $this->_encoding);
        $trans_tbl = array_flip($trans_tbl);
        return strtr($string, $trans_tbl);
    }
}

