<?php
define('RPC_DIR', realpath(dirname(__FILE__)));

require_once(RPC_DIR. '/bootstrap.php');

/*
$json = file_get_contents('php://input');
Connexions::log("json-rpc: begin: method[ %s ], request[ %s ], json[ %s ]",
                $_SERVER['REQUEST_METHOD'],
                Connexions::varExport($_REQUEST),
                $json);
// */

$server = new Zend_Json_Server();
$server->setClass('Service_Proxy_User',     'user')
       ->setClass('Service_Proxy_Item',     'item')
       ->setClass('Service_Proxy_Tag',      'tag')
       ->setClass('Service_Proxy_Bookmark', 'bookmark')
       ->setClass('Service_Proxy_Activity', 'activity')
       ->setClass('Service_Util',           'util');

// (Re)Set the server request
$req = new Connexions_Json_Server_Request_Http();

/*
Connexions::log("json-rpc: json[ %s ], request method[ %s ], params[ %s ]",
                $req->getRawJson(), $req->getRequestMethod(),
                print_r($req->getParams(), true));
// */

$server->setRequest($req);
Connexions::setRequest($req);

if ($req->isGet() && ($req->getParam('serviceDescription')))
{
    /*
    Connexions::log("json-rpc: Handle GET request...");
    // */

    $server->setTarget(Connexions::url('/api/v2/json-rpc'))
           ->setEnvelope(Zend_Json_Server_Smd::ENV_JSONRPC_2);

    /*
    echo "<pre>";
    print_r($server->getServiceMap()->toArray());
    echo "</pre>\n";
    return;
    // */

    header('Content-Type: application/json');
    echo $server->getServiceMap();
    return;
}

/*
Connexions::log("json-rpc: Handle non-GET  request...");
// */

$server->setAutoEmitResponse(false);
$rsp = $server->handle();

// /*
Connexions::log("json-rpc: response [ %s ]",
                $rsp->toJson());
// */

echo $rsp;
