<?php
require_once('./bootstrap.php');

$front   = Zend_Controller_Front::getInstance();
$request = $front->getRequest();

Connexions::log("auth_oid: request: "
                    . ($request->isPost() ? "POST" : "GET") .":\n"
                    . print_r($request, true));

$user        = Zend_Registry::get('user');
$auth        = Zend_Auth::getInstance();
$authAdapter = null;

if ($request->isPost() &&
    (($action = $request->getPost('openid_action', null)) !== null) )
{
    if ($action === 'login')
    {
        // Initiate login
        $id = $request->getParam('openid_identifier', null);

        Connexions::log("auth_oid: Initiate login, id[ {$id} ]");

        $authAdapter = new Connexions_Auth_OpenId($id);
    }
    else
    {
        // Logout
        $auth->clearIdentity();
        $user->setAuthResult(false);
    }
}
else if (isset($request->openid_mode))
{
    // Login response
    Connexions::log("auth_oid: Login response, "
                    .   "mode[ {$request->openid_mode} ]");
    $authAdapter = new Connexions_Auth_OpenId();
}


$error = '';
if ($authAdapter instanceof Zend_Auth_Adapter_Interface)
{
    $authResult = $auth->authenticate($authAdapter);

    Connexions::log("auth_oid: results: "
                    .   "code [ {$authResult->getCode()} ], "
                    .   "messages [ "
                    .       @implode('; ', $authResult->getMessages()) ." ], "
                    .   "identity [ {$authResult->getIdentity()} ], "
                    .   ($authResult->isValid()
                            ? ''
                            : 'NOT ') .'valid');

    if (! $authResult->isValid())
    {
        $error = implode('<br />', $authResult->getMessages());
    }

    $user = $authResult->getUser();
    if ( (! $user instanceof Model_User) || (! $user->isAuthenticated()) )
    {
        $error = "Invalid user (! Model_User or ! Authenticated)";
    }
}

if ( (! $user instanceof Model_User) || (! $user->isAuthenticated()) )
{
    // Present the OpenId form
    ?>

<form method='POST'>
 <label for='openid_identifier'>OpenId Identifier</label>
 <input type='text'   name='openid_identifier' value='' />

 <input type='submit' name='openid_action' value='login' />
</form>
<div class='error'><?= $error ?></div>

<?php
}
else
{
    ?>

<p>Authenticated as <b><?= $user->name ?></b></p>

<form method='POST'>
 <input type='submit' name='openid_action' value='logout' />
</form>

<?php
}
