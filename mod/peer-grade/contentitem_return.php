<h1>Item Selected</h1>
<!--
GET:
<?php
var_dump($_GET);

echo("POST:\n");
var_dump($_POST);
echo("-->\n");

// Compute the input tag in the parent document
$partno = $_GET['partno'];
$id = 'input_content_item_'.$partno;

// Check to see if we are doing the standard or faking Canvas
if ( isset($_POST['content_items'] ) ) { 
    $items = json_decode($_POST['content_items']);

    echo("<!--\n");
    var_dump($items);
    echo("-->\n");

    $graph = $items->{'@graph'};
    $found = false;
    foreach($graph as $item ) {
        if ( strpos($item->{'@type'}, 'LtiLink') !== 0 ) continue;
        $found = true;
        break;
    }
    if ( ! $found ) {
        echo("<p>Unable to find returned ContentItem data.</p>");
        return;
    }
} else {
    if ( ! isset($_GET['url']) ) {
        echo("<p>Missing return url parameter.</p>");
        return;
    }
    $item = json_decode('{
      "@type": "LtiLinkItem",
      "url": "http:\/\/localhost:8888\/sakai-api-test\/tool.php?sakai=98765",
    }');
    $full_url = $_GET['url'];
    echo("<!--\n");
    echo(htmlentities($full_url)."\n");
    echo("-->\n");

    $pos = strpos($full_url,"url=");
    if ( $pos === false ) {
        echo("<p>Unable to parse url= parameter.</p>");
        return;
    }
    $url = urldecode(substr($full_url,$pos+4));
    echo("<!--\n");
    echo(htmlentities($url)."\n");
    echo("-->\n");

    $item['url'] = $url;
}
?>
<script>
console.log(window.parent.$("#<?= $id ?>").val());
item = <?= json_encode($item) ?>;
window.parent.$("#<?= $id ?>").val(JSON.stringify(item));
</script>
<p>You may now close the window</p>

