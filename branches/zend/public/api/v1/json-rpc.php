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
       ->setClass('Service_Proxy_Bookmark', 'bookmark');

if ($_SERVER['REQUEST_METHOD'] === 'GET')
{
    $server->setTarget(Connexions::url('/api/v1/json-rpc'))
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

// (Re)Set the server request
$req = $server->getRequest();
Connexions::setRequest($req);
Connexions::log("json-rpc: json[ %s ]",
                $req->getRawJson());

$server->setAutoEmitResponse(false);
$rsp = $server->handle();
Connexions::log("json-rpc: response [ %s ]",
                $rsp->toJson());

echo $rsp;
