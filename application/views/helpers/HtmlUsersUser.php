<?php
/** @file
 *
 *  View helper to render a single User Item / Bookmark in HTML.
 *
 */
class Connexions_View_Helper_HtmlUsersUser extends Zend_View_Helper_Abstract
{
    /** @brief  Generate an HTML view of a single User Item / Bookmark.
     *  @param  user        The Model_User instance to present.
     *  @param  viewer      The Model_User instance of the current viewer
     *  @param  showParts   The parts to present [ null === 'regular' ]
     *                      (see Connexions_View_Helper_HtmlUsersUser::
     *                                                          $styleParts)
     *  @param  index       The index of this item in any list [ 0 ].
     *  @return The HTML representation of the user items.
     */
    public function htmlUsersUser(Model_User    $user,
                                  Model_User    $viewer,
                                                $showParts  = null,
                                                $index      = 0)
    {
        $html = '';

        if (! @is_array($showParts))
        {
            $showParts = Connexions_View_Helper_HtmlUsersUser::
                            $styleParts[Connexions_View_Helper_HtmlUsersUser::
                                                                STYLE_REGULAR];
        }

        $isMe = ( ($viewer && ($user->userId === $viewer->userId))
                        ? true
                        : false );

        $itemClasses = array();
        if ($isMe)                      array_push($itemClasses, 'me');
        else                            array_push($itemClasses, 'other');

        if ($showParts['minimized'])    array_push($itemClasses, 'minimized');

        $html .= "<li class='person "           // person {
              .             implode(' ', $itemClasses) . "'>"
              .   "<form class='user'>";        // user {

        if ($showParts['meta'] === true)
        {
            $html .=   "<div class='meta'>";    // meta {
            if ($showParts['meta:count:items'] === true)
            {
               $html .= sprintf ("<a class='countItems' href='%s'>%d</a>",
                                 $this->view->url(array(
                                         'controller'   => 'tags',
                                         'action'       => 'index',
                                         'owner'        => $user->name)),
                                 $user->totalItems);
            }

            if ($showParts['meta:count:tags'] === true)
            {
               $html .= sprintf ("<a class='countTags' href='%s'>%d</a>",
                                 $this->view->url(array(
                                         'controller'   => 'index',
                                         'action'       => 'index',
                                         'owner'        => $user->name)),
                                 $user->totalTags);
            }

            $html .=   "</div>";                // meta }
        }

        $clearFloats = $showParts['minimized'];
        $html .=   "<div class='data'>";    // data {

        $html .= $this->_renderAvatar($user, $showParts);

        if ($showParts['meta:relation'])
        {
            $html .= "<div class='relation'>"
                  .   ":TODO:"
                  .  "</div>";
        }

        $html .= $this->_renderUserId($user, $showParts);


        if ($showParts['fullName'] === true)
        {
            $html .= "<div class='fullName'>"    // fullName {
                  .  sprintf("<a href='%s' title='%s'>%s</a>",
                             $this->view->url(array(
                                         'controller'   => 'index',
                                         'action'       => 'index',
                                         'owner'        => $user->name)),
                             htmlspecialchars($user->fullName));

            $html .= "</div>";   // fullName }
        }

        if ($showParts['email'] === true)
        {
            $html .= "<div class='email'>"
                  .   sprintf ("<a href='mailto:%s' title='%s'>%s</a>",
                               $user->email,
                               $user->fullName,
                               $user->email)
                  .  "</div>";
        }

        if ($showParts['tags'] === true)
        {
            /*
            if ( ($showParts['minimized'] === true) ||
                 ($showParts['userId']    !== true) )
                $html .= "<br class='clear' />";
            */

            $html .= "<ul class='tags'>";       // tags {

            foreach ($user->tags as $tag)
            {
                $html .= "<li class='tag'>"
                      .   "<a href='"
                      .     $this->view->url(array(
                                        'action' => 'tagged',
                                        'tag'    => $tag->tag))
                      .         "'>{$tag->tag}</a>"
                      .  "</li>";
            }

            $html .= "</ul>"                    // tags }
                  .  "<br class='clear' />";

            $clearFloats = false;
        }

        if ( $showParts['dates'] )
        {
            $html .= "<div class='dates'>";

            if ($showParts['dates:visited'] === true)
                $html .= "<div class='lastVisit'>"
                      .    $user->lastVisit
                      .  "</div>";

            $html .= "</div>"
                  .  "<br class='clear' />";

            $clearFloats = false;
        }

        if ($clearFloats)
            $html .= "<br class='clear' />";

        $html .=   "</div>"     // data }
              .   "</form>"     // user }
              .  "</li>";       // person }

        return $html;
    }

    /*************************************************************************
     * Protected helpers
     *
     */
    protected function _renderAvatar($user, $showParts)
    {
        if ($showParts['avatar'] !== true)
            return '';

        $html =  "<div class='avatar'>"
              .   sprintf("<a href='%s' title='%s'>",
                          $this->view->url(array(
                                  'action' => $user->name)),
                          $user->fullName);

        $html .=   "<div class='img ui-state-highlight'>";
        if ( ! @empty($user->pictureUrl))
        {
            // Include the user's picture / avatar
            $html .= sprintf ("<img src='%s' />",
                              $user->pictureUrl);
        }
        else
        {
            // Include the default user icon
            $html .= "<div class='ui-icon ui-icon-person'>"
                  .   "&nbsp;"
                  .  "</div>";
        }
        $html .=   "</div>"
              .   "</a>"
              .  "</div>"

        return $html;
    }

    protected function _renderUserId($user, $showParts)
    {
        if ($showParts['userId'] !== true)
            return '';

        $html =  "<div class='userId'>"
              .   sprintf("<a href='%s' title='%s'>",
                          $this->view->url(array(
                                  'action' => $user->name)),
                          $user->fullName)
              .    "<span class='name'>{$user->name}</span>"
              .   "</a>"
              .  "</div>";

        return $html;
    }

    protected function _renderHtmlControl($user, $isMe)
    {
        $html = "<div class='control'>";  //  control {

        if ($isMe)
        {
            $html .= sprintf( "<a class='item-edit'   href='%s'>EDIT</a>",
                            $this->view->url(array(
                                    'action' => 'itemEdit',
                                    'item'   => $user->userId)) );
        }
        else
        {
        }

        $html .=  "</div>"; //  control }

        return $html;
    }
}
