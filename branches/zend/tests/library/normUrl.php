<?php
require_once('./bootstrap.php');

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
);

foreach ($tests as $expectedMd5 => $urls)
{
    foreach ($urls as $url)
    {
        $uri = Connexions::normalizeUrl($url);
        $md5 = Connexions::normalizedMd5($url);

        if ($md5 !== $expectedMd5)
        {
            printf ( "*** url [ %s ]\n"
                    ."    norm[ %s ]\n"
                    ."    expected md5[ %s ]\n"
                    ."    received md5[ %s ]\n",
                    $url, $uri, $expectedMd5, $md5);
        }
    }
}
