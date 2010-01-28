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

    static public $styleTitles      = array(
                    self::STYLE_TITLE   => 'Title',
                    self::STYLE_REGULAR => 'Regular',
                    self::STYLE_FULL    => 'Full'
                );

    const SORT_BY_DATE_TAGGED       = 'taggedOn';
    const SORT_BY_DATE_UPDATED      = 'dateUpdated';
    const SORT_BY_TITLE             = 'name';
    const SORT_BY_RATING            = 'rating';
    const SORT_BY_USER_COUNT        = 'userCount';

    static public $sortTitles       = array(
                    self::SORT_BY_DATE_TAGGED   => 'Tag Date',
                    self::SORT_BY_DATE_UPDATED  => 'Update Date',
                    self::SORT_BY_TITLE         => 'Title',
                    self::SORT_BY_RATING        => 'Rating',
                    self::SORT_BY_USER_COUNT    => 'User Count'
                );

    static public $orderTitles      = array(
                    Zend_Db_Select::SQL_ASC     => 'Ascending',
                    Zend_Db_Select::SQL_DESC    => 'Descending'
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
        case self::SORT_BY_DATE_TAGGED:
        case self::SORT_BY_DATE_UPDATED:
        case self::SORT_BY_TITLE:
        case self::SORT_BY_RATING:
        case self::SORT_BY_USER_COUNT:
            break;

        default:
            $sortBy = self::SORT_BY_DATE_TAGGED;
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


        /*
        Connexions::log("Connexions_View_Helper_HtmlUserItems: "
                            . "validated to: "
                            . "style[ {$style} ], "
                            . "styleTitle[ ".self::$styleTitles[$style]." ], "
                            . "sortBy[ {$sortBy} ], "
                            . "sortByTitle[ ".self::$sortTitles[$sortBy]." ], "
                            . "sortOrder[ {$sortOrder} ], "
                            . "sortOrderTitle[ ".
                                        self::$sortTitles[$sortBy]." ]");
        // */

        $html = sprintf("<input type='hidden' name='page' value='%s' />",
                            $paginator->getCurrentPageNumber())
              . "<div id='displayOptions'>"     // displayOptions {
              .  "<div class='displayStyle'>";  // displayStyle {

        foreach (self::$styleTitles as $key => $title)
        {
            $html .= $this->renderOption('itemsStyle',
                                         $key,
                                         $title,
                                         $key == $style,
                                         'radio',
                                         $key);
        }

        $html .= "</div>"                       // displayStyle }
              .  $this->view->paginationControl($paginator,
                                                null,        // style
                                                'paginationControl.phtml',
                                                array('excludeInfo' => true))
              .  "<div class='displaySort'>"    // displaySort {
              .   "<label   for='itemsSortBy'>Sorted by</label>"
              .   "<select name='itemsSortBy' "
              .          "class='sort-by sort-by-{$sortBy} "
              .                  "ui-input ui-state-default ui-corner-all'>";

        foreach (self::$sortTitles as $key => $title)
        {
            $html .= $this->renderOption('sortBy',
                                         $key,
                                         $title,
                                         $key == $sortBy);
        }

        $html .=  "</select>"
              .   "<div class='sort-order sort-order-{$sortOrder}'>";

        foreach (self::$orderTitles as $key => $title)
        {
            $html .= $this->renderOption('itemsSortOrder',
                                         $key,
                                         $title,
                                         $key == $sortOrder,
                                         'radio',
                                         'ui-icon ui-icon-arrowthick-1-'
                                          . ($key === Zend_Db_Select::SQL_ASC
                                                ? 'n ui-corner-top'
                                                : 's ui-corner-bottom')
                                          . ($key == $sortOrder
                                                ? ' ui-state-highlight'
                                                : ' ui-state-default'));
        }

        $html .=  "</div>"  // sort-order
              .  "</div>"   // displaySort }
              . "</div>";   // displayOptions }

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


        $html .= $paginator
              .  "<br class='clear' />\n";

        // Return the rendered HTML
        return $html;
    }

    protected function renderOption($name,
                                    $value,
                                    $title,
                                    $isOn       = false,
                                    $type       = 'option',
                                    $css        = '')
    {
        $html = '';

        switch ($type)
        {
        case 'toggle-button':
            $html = sprintf(  "<button type='submit' "
                            .         "name='%s' "
                            .        "class='ui-state-%s "
                            .               "ui-toggle-button%s' "
                            .        "title='%s' value='%s'>"
                            .  "<span>%s</span>"
                            . "</button>",
                            $name,
                            ( $isOn
                                ? "highlight"
                                : "default"),
                            ( !@empty($css)
                                ? " ". $css
                                : ""),
                            $title,
                            $value,
                            $title);
            break;

        case 'radio':
            $html = sprintf(  "<div class='ui-radio%s'>"
                            .  "<div class='ui-radio-button%s' "
                            .          "title='%s'>"
                            .   "<input type='radio' name='%s' "
                            .          "title='%s' value='%s'%s />"
                            .   "<label for='%s'>%s</label>"
                            .  "</div>"
                            . "</div>",
                            ($isOn ? " ui-radio-on" : ""),
                            ( !@empty($css)
                                ? " ". $css
                                : ""),
                            $title,
                            $name,
                            $title,
                            $value,
                            ($isOn ? " checked" : ""),
                            $name,
                            $title);
            break;

        case 'option':
        default:
            if ($isOn)  $css .= ' option-on';

            $html = sprintf(  "<option%s title='%s' value='%s'%s>"
                            .  "<span>%s</span>"
                            . "</option>",
                            ( !@empty($css)
                                ? " class='". $css ."'"
                                : ""),
                            $title,
                            $value,
                            ($isOn ? " selected" : ""),
                            $title);
        }

        return $html;
    }
}
