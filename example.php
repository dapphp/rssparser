<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">

    <title>RSS Parser Example</title>
  </head>
  <body role="document">
<?php

include_once 'RssParser.php';

// the URL of the RSS feed to read
$feedUrl  = 'http://www.npr.org/rss/rss.php?id=1001'; // NPR News & Headlines

// The HTML template to use for displaying feed <item> entries
// #{tag} in templates will be replaced by <tag> contents
// Supports namespaced tags (e.g. #{dc:creator} for <dc:creator>Someone</dc:creator>
// Supports tag attributes (e.g. #{thumbnail.src} for <thumbnail src="http://example.com/image.jpg" height="50" width="50" />
$template = <<<HTML
    <div>
        <a href="#{link}" style="font-size: 1.2em" target="_blank">#{title}</a><br>
        #{content:encoded}
        Published: #{pubDate}<br>
        Source: #{dc:creator}
    </div>
    <hr>
HTML;

// Create a new parser
$parser  = new Dapphp\RssParser\RssParser;

// Set template, cache directory & cache lifetime, parse the feed and display it
echo $parser->setItemTemplate($template)
             ->setCacheDir(sys_get_temp_dir())->setCacheLifetime(600)
             ->parse($feedUrl)
             ->show();
?>
  </body>
</html>

