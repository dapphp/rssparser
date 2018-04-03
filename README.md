## Name:

**Dapphp\RssParser** - A PHP5 class for parsing RSS feeds for display on webpages

## Version:

**1.1.0**

## Author:

Drew Phillips <drew@drew-phillips.com>

## Requirements:

* PHP 5.3 or greater

## Synopsis:

**Basic Usage**

    $parser = new Dapphp\RssParser\RssParser;
    $parser->parse($urlOfRssFeed)
           ->show();    

**Usage with caching & custom template**

    $template = <<<HTML
        <div>
          <img src="#{dc:image.src}" height="#{dc:image.height}" width="#{dc:image.width}" align="left"/>
          <a href="#{link}" style="font-size: 1.2em" target="_blank">#{title}</a><br>
          #{content:encoded}
          Published on #{pubDate} by #{dc:creator}
        </div>
        <hr>
    HTML;

    $parser->setCacheDir('/tmp')
           ->setCacheLifetime(600)
           ->setItemTemplate($template)
           ->parse($feedUrl)
           ->show(10); // show 10 items

## Description:

Dapphp\RssParser is a PHP class for parsing RSS feeds and displaying the
contents on webpages.

Using this class the contents of an RSS feed can be displayed on any PHP
enabled webpage in 2 lines of code.  Uses no external dependencies
(e.g. cURL) and requires PHP 5.3 or greater.  Supports http/https URLs,
caching of feed contents, and displaying feed contents using simple
templates.

## Copyright:

    Copyright (c) 2015 Drew Phillips
    All rights reserved.

    Redistribution and use in source and binary forms, with or without
    modification, are permitted provided that the following conditions are met:

    - Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.
    - Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.

    THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
    AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
    IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
    ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
    LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
    CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
    POSSIBILITY OF SUCH DAMAGE.

