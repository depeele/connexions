<?php
require_once('./bootstrap.php');

echo "<pre>";
echo "<h3>Model_Gateway_Bookmark tests</h3>";

// Our primary
$gateway = new Model_Gateway_Bookmark();

printf ("gateway is a '%s'\n", get_class($gateway));

/*****************************************************************************/
$id       = array(1, 3);
$bookmark = $gateway->find( $id );

printf ("bookmark: ( %s ):\n", implode(', ', $id));
if ($bookmark instanceof Connexions_Model)
{
    echo $bookmark->debugDump();

}
else
{
    echo "  *** ERROR\n";
}
echo "<hr />";

/*****************************************************************************/
$where     = array('userId' => 1);
$count     = 10;
$bookmarks = $gateway->fetch( $where,
                              null,     // order
                              $count,   // count
                              null );   // offset

printf ("bookmarks: ( %s ), first %d, %d entries:\n",
        Connexions::varExport($where), $count, count($bookmarks));
foreach ($bookmarks as $idex => $bookmark)
{
    printf (" #%2d: [ %s ]\n", $idex, $bookmark->debugDump());
}
echo "<hr />";

/*****************************************************************************/
