<?php
/** @file
 *
 *  View helper to render a single User Item / Bookmark in HTML.
 *
 */
class View_Helper_HtmlUsersUser extends Zend_View_Helper_Abstract
{
    /** @brief  Generate an HTML view of a single User Item / Bookmark.
     *  @param  user        The Model_User instance to present.
     *  @param  viewer      The Model_User instance of the current viewer
     *  @param  showParts   The parts to present.
     *  @param  index       The index of this item in any list [ 0 ].
     *  @return The HTML representation of the user items.
     */
    public function htmlUsersUser(Model_User    $user,
                                  Model_User    $viewer,
                                  array         $showParts  = array(),
                                                $index      = 0)
    {
        $html = '';
        $isMe = ( ($viewer && ($user->userId === $viewer->userId))
                        ? true
                        : false );

        $itemClasses = array();
        if ($isMe)                      array_push($itemClasses, 'me');
        else                            array_push($itemClasses, 'other');

        if ($showParts['minimized'])    array_push($itemClasses, 'minimized');

        $html .= "<li class='person "           // person {
              .             implode(' ', $itemClasses) . "'>\n"
              .   "<form class='user'>";        // user {

        /*****************
         * stats
         *
         */
        if ($showParts['user:stats'] === true)
        {
            $html .=   "<div class='stats ui-corner-bottom'>";  // stats {
            if ($showParts['user:stats:countItems'] === true)
            {
                $count = $user->getWeight();    // $user->totalItems;

                $html .= sprintf ("<a class='countItems' "
                                  .   "href='%s' "
                                  .  "title='Bookmarks'>%d</a>",
                                 $this->view->url(array(
                                         'controller'   => 'index',
                                         'action'       => 'index',
                                         'owner'        => $user->name)),
                                 $count);
            }

            if ($showParts['user:stats:countTags'] === true)
            {
                $html .= sprintf (  "<a class='countTags' "
                                  .    "title='Tags' "
                                  .     "href='%s'>"
                                  .  "<div class='icon icon-active'>"
                                  .   "<div class='ui-icon ui-icon-tag'>"
                                  .    "&nbsp;"
                                  .   "</div>"
                                  .  "</div>"
                                  .  "%d"
                                  . "</a>",
                                 $this->view->url(array(
                                         'controller'   => 'tags',
                                         'action'       => 'index',
                                         'owner'        => $user->name)),
                                 $user->totalTags);
            }

            $html .=   "</div>";                // stats }
        }

        $clearFloats = $showParts['minimized'];
        $html .=   "<div class='data'>";    // data {

        /*****************
         * avatar
         *
         */
        if ($showParts['user:data:avatar'] === true)
        {
            $html .= "<div class='avatar'>"
                  .   sprintf("<a href='%s' title='%s'>",
                              $this->view->url(array(
                                      'action' => $user->name)),
                              $user->fullName);

            $html .=   "<div class='img icon-highlight'>";
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
                  .  "</div>";
        }

        /*****************
         * userId
         *
         */
        if ($showParts['user:data:userId'] === true)
        {
            $html .= "<div class='userId'>"
                  .   sprintf("<a href='%s' title='%s'>",
                              $this->view->url(array(
                                      'controller'  => 'index',
                                      'action'      => 'index',
                                      'owner'       => $user->name)),
                              $user->fullName)
                  .    "<span class='name'>{$user->name}</span>"
                  .   "</a>"
                  .  "</div>";
        }


        /*****************
         * fullName
         *
         */
        if ($showParts['user:data:fullName'] === true)
        {
            $html .= "<div class='fullName'>";   // fullName {

            if ($showParts['user:data:userId'] !== true)
            {
                $html .= sprintf("<a href='%s' title='%s'>%s</a>",
                                 $this->view->url(array(
                                         'controller'   => 'index',
                                         'action'       => 'index',
                                         'owner'        => $user->name)),
                                 htmlspecialchars($user->fullName),
                                 htmlspecialchars($user->fullName));
            }
            else
            {
                $html .= htmlspecialchars($user->fullName);
            }

            $html .= "</div>";   // fullName }
        }

        /*****************
         * email address
         *
         */
        if ($showParts['user:data:email'] === true)
        {
            $html .= "<div class='email'>"
                  .   sprintf ( "<a href='mailto:%s' "
                               .  "title='%s'>"
                               . "<div class='icon icon-highlight'>"
                               .  "<div class='ui-icon ui-icon-mail-closed'>"
                               .   "&nbsp;"
                               .  "</div>"
                               . "</div>"
                               . "%s"
                              . "</a>",
                               $user->email,
                               $user->fullName,
                               $user->email)
                  .  "</div>";
        }

        /*****************
         * tags
         *
         */
        if ($showParts['user:data:tags'] === true)
        {
            /*
            if ( ($showParts['minimized'] === true) ||
                 ($showParts['userId']    !== true) )
                $html .= "<br class='clear' />";
            */

            $html .= "<ul class='tags'>";       // tags {

            $tags = $user->tags->weightBy('userItem');

            // Only show the top 5
            $tagLimit = 5;
            foreach ($tags as $tag)
            {
                if ($tagLimit-- < 0)
                    break;

                $title  = $tag->getTitle();
                $weight = $tag->getWeight();

                $html .= "<li class='tag'>"
                      .   "<a href='"
                      .             $this->view->url(array(
                                        'controller' => 'index',
                                        'action'     => 'index',
                                        'owner'      => $user->name,
                                        'tag'        => $title)) . "' "
                      .      "title='{$title}: {$weight}'>"
                      .     $title
                      .   "</a>"
                      .  "</li>";
            }

            $html .= "</ul>";                   // tags }

            $clearFloats = true;
        }

        /*****************
         * dates
         *
         */
        if ( $showParts['user:data:dates'] )
        {
            $html .= "<div class='dates'>";

            if ($showParts['user:data:dates:lastVisit'] === true)
                $html .= "<div class='lastVisit'>"
                      .    $user->lastVisit
                      .  "</div>";

            $html .= "</div>";
        }

        if ($clearFloats)
            $html .= "<br class='clear' />";

        $html .=   "</div>"     // data }
              .   "</form>"     // user }
              .  "</li>\n";     // person }

        return $html;
    }

    /*************************************************************************
     * Protected helpers
     *
     */
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
