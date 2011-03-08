<?php
/** @file
 *
 *  View helper to render a single User within an HTML item cloud.
 */
class View_Helper_HtmlItemCloudUser
                    extends Zend_Tag_Cloud_Decorator_HtmlTag
{
    protected   $_view          = null;
    protected   $_showControls  = false;

    public function setView(Zend_View   $view)
    {
        $this->_view = $view;
        return $this;
    }

    public function getView()
    {
        return $this->_view;
    }

    public function setShowControls($showControls)
    {
        $this->_showControls = $showControls;
        return $this;
    }

    public function getShowControls()
    {
        return $this->_showControls;
    }

    /** @brief  Render an HTML version of a single user item.
     *  @param  users   A Zend_Tag_ItemList / Connexions_Set_ItemList instance
     *                  representing the users to be presented.
     *
     *  @return The HTML representation of a user item.
     */
    public function render(Zend_Tag_ItemList $users)
    {
        //Connexions::log("View_Helper_HtmlItemCloudUser:"
        //                          . "render...");

        if (($weightValues = $this->getClassList()) === null)
        {
            $weightValues = range($this->getMinFontSize(),
                                  $this->getMaxFontSize());
        }

        $users->spreadWeightValues($weightValues);

        $result = array();

        foreach ($users as $user)
        {
            $isSelected = ($user->getParam('selected') === true);
            $cssClass   = ($isSelected
                                ? 'selected ui-corner-all ui-state-highlight '
                                : '');
            $weightVal  = $user->getParam('weightValue');
            $attribute  = '';

            /*
            Connexions::log('View_Helper_HtmlItemCloudUser:'
                            . 'user[ %s ], %sselected',
                            $user->getTitle(),
                            ($isSelected ? '' : 'NOT '));
            // */

            if (($classList = $this->getClassList()) === null)
            {
                $attribute = sprintf('style="font-size: %d%s;"',
                                        $weightVal,
                                        $this->getFontSizeUnit());
            }
            else
            {
                $cssClass .= htmlspecialchars($weightVal);
            }

            if (! empty($cssClass))
                $cssClass = ' class="'. $cssClass .'"';

            // Only show the avatar if the user has one defined
            if (empty($user->pictureUrl))
                $avatar = '';
            else
                $avatar = "<div class='avatar ui-state-highlight'>"
                        .  "<img src='{$user->pictureUrl}' />"
                        . "</div>";
            /* Always show an avatar, using a default if there user does not
             * have one defined
             *
            $avatar = "<div class='avatar ui-state-highlight'>";
            if (empty($user->pictureUrl))
                $avatar .= "<div class='ui-icon ui-icon-person'>&nbsp;</div>";
            else
                $avatar .= "<img src='{$user->pictureUrl}' />";
            $avatar .= "</div>";
            */

            $url    = $user->getParam('url');
            $weight = number_format($user->getWeight());
            if (empty($url))
                $userHtml = sprintf( '<span title="%s" %s%s>'
                                    . '%s'
                                    . '<span class="name">%s</span>'
                                    .'</span>',
                                    $weight,
                                    $cssClass,
                                    $attribute,
                                    $avatar,
                                    $user->getTitle());
            else
                $userHtml = sprintf( '<a href="%s" title="%s" %s%s>'
                                    . '%s'
                                    . '<span class="name">%s</span>'
                                    .'</a>',
                                    htmlSpecialChars($url),
                                    $weight,
                                    $cssClass,
                                    $attribute,
                                    $avatar,
                                    $user->getTitle());

            /*
            Connexions::log("View_Helper_HtmlItemCloudUser::render() [%s]: "
                            . "title[ %s ], weight[ %s ], weight title[ %s ]",
                            get_class($user),
                            $user->getTitle(),
                            $user->getWeight(),
                            $weight);
            // */

            foreach ($this->getHtmlTags() as $key => $data)
            {
                if (is_array($data))
                {
                    $htmlTag    = $key;
                    $attributes = '';

                    foreach ($data as $param => $value)
                    {
                        $attributes .= ' '
                                   . $param . '="'
                                   .    htmlspecialchars($value) . '"';
                    }
                }
                else
                {
                    $htmlTag    = $data;
                    $attributes = '';
                }

                $userHtml = sprintf('<%1$s%3$s>%2$s</%1$s>',
                                    $htmlTag, $userHtml, $attributes);
            }

            $result[] = $userHtml;
        }

        return $result;
    }
}
