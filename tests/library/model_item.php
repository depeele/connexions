<?php
require_once('./bootstrap.php');

echo "<pre>";
echo "<h3>Model_Item tests</h3>";

$item = Model_Item::find(1);
echo "Record for 1:\n";

if ($item instanceof Connexions_Model)
{
    echo $item->debugDump();
}
else
{
    echo " *** ERROR\n";
}
unset($item);
echo "<hr />";

/*****************************************************************************/
$urlHash = '383cb614a2cc9247b86cad9a315d02e3';
$item = Model_Item::find($urlHash);
printf ("Record for '%s':\n", $urlHash);
echo ($item instanceof Connexions_Model ? $item->debugDump() : " *** ERROR\n");

unset($item);
echo "<hr />";

/*****************************************************************************/
$url = 'http://www.clipperz.com/';
$item = Model_Item::find($url);
printf ("Record for url '%s':\n", $url);
echo ($item instanceof Connexions_Model ? $item->debugDump() : " *** ERROR\n");

echo "\n\nChange URL:\n";
$item->url = 'http://www.clipperz.com/test1';
echo $item->debugDump();

echo "\n\nChange URL Hash directly:\n";
$item->urlHash = '383cb614a2cc9247b86cad9a315d02e3';
echo $item->debugDump();

echo "\n\nChange the URL back to the original:\n";
$item->url = $url;
echo $item->debugDump();

unset($item);
echo "<hr />";
