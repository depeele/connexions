<?php
/** @file
 *
 *  View helper to render a paginated set of Users in HTML.
 *
 *  REQUIRES:
 *      application/view/scripts/list.phtml
 *      application/view/scripts/user.phtml
 *
 *      application/view/scripts/list_group.phtml
 *      application/view/scripts/list_groupDate.phtml
 *      application/view/scripts/list_groupAlpha.phtml
 *      application/view/scripts/list_groupNumeric.phtml
 */
class View_Helper_HtmlGroupUsers extends View_Helper_HtmlUsers
{
    /** @brief  Configure and retrive this helper instance OR, if no
     *          configuration is provided, perform a render.
     *  @param  config  A configuration array (see populate());
     *
     *  @return A (partially) configured instance of $this OR, if no
     *          configuration is provided, the HTML rendering of the configured
     *          users.
     */
    public function htmlGroupUsers(array $config = array())
    {
        return $this->htmlUsers($config);
    }

    /** @brief  Retrieve the users to be presented.
     *
     *  @return The Model_Set_User instance representing the users.
     */
    public function getUsers()
    {
        $key = $this->listName;
        if ( (! @isset($this->_params[$key])) ||
             ($this->_params[$key] === null) )
        {
            /* This is here in a view helper and not in the controller
             * primarily to allow centralized, contextual default values
             * for things like sortBy, sortOrder and perPage.
             */
            $fetchOrder = $this->sortBy .' '. $this->sortOrder;
            $perPage    = $this->perPage;
            $page       = ($this->page > 0
                            ? $this->page
                            : 1);

            $count      = $perPage;
            $offset     = ($page - 1) * $perPage;

            /*
            Connexions::log("View_Helper_HtmlGroupUsers::getUsers(): "
                            . "Retrieve users: "
                            . "listname[ %s ], "
                            . "order[ %s ], count[ %d ], offset[ %d ]",
                            $key,
                            $fetchOrder, $count, $offset);
            // */

            if ((! @isset($this->_params['group'])) ||
                ($this->_params['group'] === null))
            {
                /*
                Connexions::log("View_Helper_HtmlGroupUsers::getUsers(): "
                                . "Missing group");
                // */

                //throw new Exception("Missing 'group'");
                $users = null;
            }
            else
            {
                $users = $this->_params['group']->getItems($fetchOrder,
                                                           $count,
                                                           $offset);
            }

            $this->_params[$key] = $users;
        }
        $val = $this->_params[$key];

        return $val;
    }
}
