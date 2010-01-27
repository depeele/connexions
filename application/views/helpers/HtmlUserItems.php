<?php
/** @file
 *
 *  View helper to render a paginated set of User Items / Bookmarks in HTML.
 */
class Connexions_View_Helper_HtmlUserItems extends Zend_View_Helper_Abstract
{
    const STYLE_TITLE               = 'title';
    const STYLE_REGULAR             = 'regular';
    const STYLE_FULL                = 'full';

    const SORT_BY_DATE              = 'date';
    const SORT_BY_TITLE             = 'title';

    static public $sortTitles       = array(
                    // self::SORT_BY_DATE .'_'. Zend_Db_Select::SQL_ASC
                    "date_asc"      => 'Most Ancient',

                    // self::SORT_BY_DATE .'_'. Zend_Db_Select::SQL_DESC
                    "date_desc"     => 'Most Recent',

                    // self::SORT_BY_TITLE .'_'. Zend_Db_Select::SQL_ASC
                    "title_asc"     => 'Title',

                    // self::SORT_BY_TITLE .'_'. Zend_Db_Select::SQL_DESC
                    "title_desc"    => 'Title (reverse)'
                );

    /** @brief  Render an HTML version of a paginated set of User Items.
     *  @param  paginator       The Zend_Paginator representing the items to
     *                          be presented.
     *  @param  viewer          A Model_User instance representing the
     *                          current viewer;
     *  @param  style           The style to use for each item
     *                          (Connexions_View_Helper_HtmlUserItems::
     *                                                          STYLE_*);
     *  @param  sortBy          The field used to sort the items
     *                          (Connexions_View_Helper_HtmlUserItems::
     *                                                      SORT_BY_*);
     *  @param  sortOrder       The sort order
     *                          (Zend_Db_Select::SQL_ASC | SQL_DESC).
     *
     *  @return The HTML representation of the user items.
     */
    public function htmlUserItems(Zend_Paginator $paginator,
                                  Model_User     $viewer,
                                  $style        = null,
                                  $sortBy       = null,
                                  $sortOrder    = null)
    {
        /*
        Connexions::log("Connexions_View_Helper_HtmlUserItems: "
                            . "style[ {$style} ], "
                            . "sortBy[ {$sortBy} ], "
                            . "sortOrder[ {$sortOrder} ]");
        // */

        switch ($style)
        {
        case self::STYLE_TITLE:
        case self::STYLE_FULL:
        case self::STYLE_REGULAR:
            break;

        default:
            $style = self::STYLE_REGULAR;
            break;
        }

        switch ($sortBy)
        {
        case self::SORT_BY_TITLE:
        case self::SORT_BY_DATE:
            break;

        default:
            $sortBy = self::SORT_BY_DATE;
            break;
        }

        switch (strtoupper($sortOrder))
        {
        case Zend_Db_Select::SQL_ASC:
        case Zend_Db_Select::SQL_DESC:
            break;

        default:
            $sortOrder = Zend_Db_Select::SQL_DESC;
            break;
        }


        $sort      = strtolower($sortBy) .'_'. strtolower($sortOrder);
        $sortTitle = self::$sortTitles[$sort];

        /*
        Connexions::log("Connexions_View_Helper_HtmlUserItems: "
                            . "validated to: "
                            . "style[ {$style} ], "
                            . "sortBy[ {$sortBy} ], "
                            . "sortOrder[ {$sortOrder} ], "
                            . "sort[ {$sort} ], "
                            . "sortTitle[ {$sortTitle} ]");
        // */

        $html = "<div id='displayOptions'>"
              .  "<div id='displayStyle' class='{$style}'>"
              .   "<h3>Display:</h3>"
              .   "<ul>"
              .    $this->htmlDisplayLi(self::STYLE_TITLE,
                                              $sortBy,
                                              $sortOrder,
                                              self::STYLE_TITLE,
                                              $style == self::STYLE_TITLE,
                                              self::STYLE_TITLE)
              .    $this->htmlDisplayLi(self::STYLE_REGULAR,
                                              $sortBy,
                                              $sortOrder,
                                              self::STYLE_REGULAR,
                                              $style == self::STYLE_REGULAR,
                                              self::STYLE_REGULAR)
              .    $this->htmlDisplayLi(self::STYLE_FULL,
                                              $sortBy,
                                              $sortOrder,
                                              self::STYLE_FULL,
                                              $style == self::STYLE_FULL,
                                              self::STYLE_FULL)
              .   "</ul>"
              .  "</div>"
              .  "<div id='displaySort' class='{$sort} ui-form'>"
              .   "<h3>Sorted by "
              .    "<div class='ui-dropDown ui-button ui-corner-all'>"
              .     "<a id='sort_userItems'>{$sortTitle}</a>"
              .     "<ul class='ui-state-default'>";

        foreach (self::$sortTitles as $key => $title)
        {
            $parts = explode('_', $key);

            $html .= $this->htmlDisplayLi($style,
                                          $parts[0],    // $sortBy
                                          $parts[1],    // $sortOrder
                                          $title,
                                          $key == $sort);
        }

        $html .=    "</ul>"
              .    "</div>"
              .   "</h3>"
              .  "</div>"
              . "</div>"
              . $paginator;

        if (count($paginator))
        {
            $html .= "<ul class='items'>";

            foreach ($paginator as $idex => $userItem)
            {
                $html .= $this->view->partial('userItem.phtml',
                                              array(
                                                  'index'    =>  $idex,
                                                  'userItem' => &$userItem,
                                                  'viewer'   => &$viewer));
            }

            $html .= "</ul>";
        }


        $html .= sprintf("%s", $paginator);

        // Return the rendered HTML
        return $html;
    }

    protected function htmlDisplayLi($style,
                                     $sortBy,
                                     $sortOrder,
                                     $title,
                                     $isOn    = false,
                                     $aClass  = '')
    {
        return sprintf(  "<li class='%s'>"
                       .  "<a class='%s' "
                       .     "href='%s/?itemsStyle=%s"
                       .              "&itemsSortBy=%s"
                       .              "&itemsSortOrder=%s'>"
                       .   "<b>%s</b>"
                       .  "</a>"
                       . "</li>",
                       ($isOn ? "on" : ""),
                       $aClass,
                       $this->view->baseUrl(),
                       $style,
                       $sortBy,
                       $sortOrder,
                       $title);
    }
}
