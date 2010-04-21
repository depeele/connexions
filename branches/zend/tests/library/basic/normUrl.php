<?php
require_once('./bootstrap.php');

echo "<pre>Starting...\n";

$tests = array(
    '2d27a6266ab802c6239de5fc8b763369'  => array(
        "http://HelloThere.com/What's my/line?abc=def&hij=%2055",
        "http://HelloThere.com/What's   my/line  ?abc=def&hij=%2055",
        "HTTP://hellothere.com/what%27s my/line?abc=def&hij= 55",
        "HTTP://hellothere.com/what%27s my/line?abc=def&hij=   55",
        "HTTP://hellothere.com/what%27s my/line?hij= 55&abc=def",
        "hTTp://hellothere.com:80/what%27s my/line?abc=def&hij= 55",
        "hTTp://hellothere.com:80/what%27s my\line?abc=def&hij= 55",
    ),

    '5832116cdf2e0f02a4b2fcb4e771440d'  => array(
        "hTTp://user:pass@hellothere.com:80/what%27s my/line?abc=def&hij= 55",
        "hTTp://user:pass@hellothere.com:80/what%27s my/job/../line?abc=def&hij= 55",
    ),

    'b5ff6d9f74e085adae3b93b4d717d120'  => array(
        'mailto:abc@def.com',
        'mailto://localhost:25/abc@def.com',
        'mailto://localhost/abc@def.com',
    ),

    'fbeb94b8bd331fc8ae5690f51550e521'  => array(
        'smb://user:pass@host.com:234:/path/to/resource?query=string#fragment',
        'smb://user:pass@host.com:234/path/to/resource?query=string#fragment',
    ),

    '92e2423ebdeba205570b4467c946318e'  => array(
        'http://www.google.com/url?sa=t&ct=res&cd=16&url=http%3A%2F%2Fwww.wikisym.org%2Fws2006%2Fproceedings%2Fp47.pdf&ei=Doz2Rfi_BIHSgwTArfnnAQ&usg=__GKIetVM45n1YLf6vBY_NAU42LBY=&sig2=T5IVzgy5Lg7feAbJC6mtrA',
    ),
);

$testRecursiveHash = true;

foreach ($tests as $expectedMd5 => $urls)
{
    if ($testRecursiveHash)
    {
        $md5 = Connexions::md5Url( $expectedMd5 );
        printf ("md5[ %s ] == [ %s ]\n", $md5, $expectedMd5);
        if ($md5 !== $expectedMd5)
            echo "*** Hashing a hash fails.\n";

        $md5 = Connexions::md5Url( $expectedMd5 .'1234' );
        printf ("md5[ %s ] != [ %s ]\n", $md5, $expectedMd5);
        if ($md5 === $expectedMd5)
            echo "*** Hashing a hash fails (2).\n";

        $testRecursiveHash = false;
    }

    foreach ($urls as $url)
    {
        $uri = Connexions::normalizeUrl($url);
        $md5 = Connexions::md5Url($url);

        if ($md5 !== $expectedMd5)
        {
            printf ( "*** url [ %s ]\n"
                    ."    norm[ %s ]\n"
                    ."    expected md5[ %s ]\n"
                    ."    received md5[ %s ]\n",
                    $url, $uri, $expectedMd5, $md5);
        }
        else
        {
            printf ( "+ url [ %s ]\n"
                    ."  norm[ %s ]\n"
                    ."  expected md5[ %s ]\n"
                    ."  received md5[ %s ]\n",
                    $url, $uri, $expectedMd5, $md5);
        }
    }
}

echo "</pre>\n";
