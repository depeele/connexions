<?php
require_once('./bootstrap.php');

$rec = Model_Tag::find(1);
echo "<pre>Record for 1:\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "</pre><hr />";

/*****************************************************************************/
$tag = 'security';
$rec = Model_Tag::find($tag);
printf ("<pre>Record for '%s':\n", $tag);
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "</pre><hr />";

/*****************************************************************************/
$tags = 'security,identity,abc,def';
$data = Model_Tag::ids($tags);
echo "<pre>ID information for '{$tags}':\n";
print_r($data);
echo "</pre><hr />";
unset($data);
