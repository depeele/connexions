<?php

/** @brief  Identify the user that is attempting access.
 *
 *  @return An associative array of user information.
 */
function identify_user($tagdb)
{
    $funcId = 'identify_user';

    /*
     * Retrieve authentication/identification information for the
     * requesting user from somewhere.
     *
     * It must contain at least:
     *  - id            A globally unique identifier
     *  - name_first    User's first/given name
     *  - name_last     User's last/sur name
     *  - email         User's email address
     *  - authenticated A boolean indicating whether or not this identified
     *                  user has been authenticated.
     */
    $authInfo = array(  'id'            => 'elmo',
                        'name_first'    => 'D. Elmo',
                        'name_last'     => 'Peele',
                        'email'         => 'dep@home',
                        'authenticated' => true
                     );
    if ($authInfo === false)
        return false;

    $userInfo = $tagdb->user($authInfo['id']);
    if ($userInfo === false)
    {
        $name     = $authInfo['id'];

        $userInfo = array('fullName' => $authInfo['name_first'] . ' '
                                           .  $authInfo['name_last'],
                          'email'    => $authInfo['email']);

        // There is no user record for this user.  Create one now.
        $id = $tagdb->userAdd($name, $userInfo);
        if ($id === false)
        {
            // Something is wrong -- the user record doesn't exist
            // yet we can't create it...
            return false;
        }

        $userInfo = $tagdb->user($id);
        if ($userInfo === false)
        {
            // Something is VERY wrong -- we suppposedly created new
            // user information but we can't find it now...
            return false;
        }
    }

    // Take our authentication status from the PKI info.
    $userInfo['authenticated'] = $authInfo['authenticated'];

    // :TODO: Update the user's last visit date
    // $tagdb->userStatsUpdate($userInfo['userid']);

    return $userInfo;
}
