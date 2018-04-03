<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">

    <title>RSS Parser Example</title>

    <style type="text/css">
      pre { border: 1px solid #000; padding: 5px; font-family: "Lucida Console", Monaco, monospace; }
    </style>
  </head>
  <body role="document">
<?php

require_once 'vendor/autoload.php';

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
?>

    <h3>Show feed items using a template</h3>
    <code><pre>
&lt;?php
// Create a new parser
$parser   = new Dapphp\RssParser\RssParser;

$feedUrl  = 'http://www.npr.org/rss/rss.php?id=1001'; // NPR News &amp; Headlines

$template = &lt;&lt;&lt;HTML
    &lt;div&gt;
        &lt;a href="#{link}" style="font-size: 1.2em" target="_blank"&gt;#{title}&lt;/a&gt;&lt;br&gt;
        #{content:encoded}
        Published: #{pubDate}&lt;br&gt;
        Source: #{dc:creator}
    &lt;/div&gt;
    &lt;hr&gt;
HTML;


// Set template, cache directory &amp; cache lifetime, parse the feed and display it
echo $parser-&gt;setItemTemplate($template)
            -&gt;setCacheDir(sys_get_temp_dir())
            -&gt;setCacheLifetime(600)
            -&gt;parse($feedUrl)
            -&gt;show();
    </pre></code>

<?php

// Create a new parser
$parser  = new Dapphp\RssParser\RssParser;

// Set template, cache directory & cache lifetime, parse the feed and display it
echo $parser->setItemTemplate($template)
             ->setCacheDir(sys_get_temp_dir())->setCacheLifetime(600)
             ->parse($feedUrl)
             ->show();
?>

    <h3>Iterating over feed items</h3>
    <code><pre>
&lt;?php
// Create a new parser
$parser   = new Dapphp\RssParser\RssParser;

$feedUrl  = 'http://www.npr.org/rss/rss.php?id=1001'; // NPR News &amp; Headlines

// Set cache lifetime, parse the feed
$parser-&gt;setCacheDir(sys_get_temp_dir())
       -&gt;setCacheLifetime(600)
       -&gt;parse($feedUrl);
?&gt;

&lt;!-- Iterate over feed items by calling getItems() method and display feed --&gt;
&lt;?php foreach($parser-&gt;getItems() as $item): ?&gt;
&lt;div&gt;
  &lt;!-- show content of &lt;title&gt;, link, description, and pubDate tags from each feed item --&gt;
  &lt;strong&gt;Title:&lt;/strong&gt; &lt;?php echo $item['title']['content'] ?&gt;&lt;br&gt;
  &lt;strong&gt;Link:&lt;/strong&gt; &lt;?php echo $item['link']['content'] ?&gt;&lt;br&gt;
  &lt;strong&gt;Description:&lt;/strong&gt; &lt;?php echo $parser-&gt;unHtmlEntities($item['description']['content']) ?&gt;&lt;br&gt;
  &lt;strong&gt;Published:&lt;/strong&gt; &lt;?php echo date('l F jS, Y \a\t h:i A', strtotime($item['pubDate']['content'])) ?&gt;
&lt;/div&gt;
&lt;hr&gt;
&lt;?php endforeach; ?&gt; 
    </pre></code>

    <?php foreach($parser->getItems() as $item): ?>
    <div> 
      <strong>Title:</strong> <?php echo $item['title']['content'] ?><br>
      <strong>Link:</strong> <?php echo $item['link']['content'] ?><br>
      <strong>Description:</strong> <?php echo $parser->unHtmlEntities($item['description']['content']) ?><br>
      <strong>Published:</strong> <?php echo date('l F jS, Y \a\t h:i A', strtotime($item['pubDate']['content'])) ?>
    </div>
    <hr>
    <?php endforeach; ?>
  </body>
</html>

