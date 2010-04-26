<?php
/** @file
 *
 *  The concrete base class providing access to Model_User.
 */
class Service_User extends Connexions_Service
{
    /* inferred via classname
    protected   $_modelName = 'Model_User';
    protected   $_mapper    = 'Model_Mapper_User'; */

    /** @brief  Given a user identifier and/or credential, attempt to
     *          authenticate the identified user.
     *  @param  userId      The user identifier.
     *  @param  credential  The user credential, possibly authenticated
     *                      (i.e. OpenId, PKI).
     *  @param  authType    The type of authentication to perform
     *                      (Model_UserAuth::AUTH_*)
     *
     *  @return A Model_User instance with isAuthenticated() set accordingly.
     */
    public function authenticate($userId,
                                 $credential,
                                 $authType   = Model_UserAuth::AUTH_PASSWORD)
    {
        // First, see if 'userId' identifies a valid user.
        $user = $this->retrieve( $userId );
        if ($user === null)
        {
            // Create a new "anonymous" user instance
            $class = $this->_modelName;
            $user  = new $class( array(
                            'name'  => (is_string($userId)
                                            ? $userId
                                            : 'anonymous'),
                            'fullName'  => 'Visitor'
                         ));
        }

        if ( ! $user->isBacked())
        {
            return $user;
        }

        // Include the incoming authentication information
        $user->authType   = $authType;
        $user->credential = $credential;

        // Attempt to authenticate this user
        $user->authenticate();

        // Remove the authentication information
        unset($user->authType);
        unset($user->credential);

        return $user;
    }
}
