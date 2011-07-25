<?php
// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath('./../library'),
    get_include_path(),
)));

//printf ("include path [ %s ]\n", get_include_path());

if ($argc < 2)
{
    printf ("*** Usage: %s <Discovery URL>\n",
            $argv[0]);
    die;
}

require_once 'Zend/OpenId.php';

$url = $argv[1];
if (! Zend_OpenId::normalize($url))
{
    printf ("Normalization of URL[ %s ]: FAILED\n", $url);
}

require_once 'Zend/Http/Client.php';

printf ("Nomalized URL[ %s ]\n", $url);
$response = getUrl($url);

echo $response;



function getUrl($url)
{
    $client = new Zend_Http_Client($url,
                                   array('maxredirects'   => 4,
                                         'timeout'        => 15,
                                         'useragent'      => 'Zend_OpenId'));
    //$client->resetParameters();
    //$client->setMethod(Zend_Http_Client::GET);
    //$client->setParameterGet($params);    // $params = array();

    //$client->setMethod(Zend_Http_Client::HEAD);
    $client->setMethod(Zend_Http_Client::GET);

    $client->setHeaders('Accept', array('application/xrds+xml',
                                        'application/xrd+xml',
                                        'text/uri-list'));

    try {
        $response = $client->request();
    } catch (Exception $e) {
        printf ("*** HTTP Request failed: %s\n", $e->getMessage());
        die;
    }
    //printf ("request:\n%s\n\n", $client->getLastRequest());
    //printf ("response:\n%s\n\n", print_r($client->getLastResponse(), true));

    $status = $response->getStatus();
    $body   = $response->getBody();

    if ($status === 200 || ($status === 400 && !empty($body)))
        return $body;

    printf ("*** Bad HTTP response\n");
    die;
}
