<?php
/** @file
 *
 *  View helper to render a Tag Cloud in HTML.
 *
 *  Note: Within a view, use EITHER this helper:
 *          $view->htmlTagCloud();
 *
 *        OR the 'tagCloud.phtml' partial:
 *          $view->partial('tagCloud.phtml',
 *                         array('tagSet'   => &$tagSet,
 *                               'tagInfo'  => &$tagInfo));
 *
 *        Both make use of the Connexions_View_Helper_HtmlTagItem helper
 *        to render individual tag items.
 */
class Connexions_View_Helper_HtmlTagCloud extends Zend_View_Helper_Abstract
{
    /** @brief  Render an HTML version of a tag cloud.
     *  @param  itemList    A Connexions_Set_ItemList instance representing the
     *                      items to be presented;
     *  @param  sortBy      The tag field to sort by ( ['title'] | 'count' );
     *  @param  sortOrder   Sort order ( ['ASC'] | 'DESC').
     *
     *  @return The HTML representation of a tag cloud.
     */
    public function htmlTagCloud(Connexions_Set_ItemList    $itemList,
                                 $sortBy       = 'title',
                                 $sortOrder    = 'ASC')
    {
        $html = '';

        if ($sortBy    === null)    $sortBy    = 'title';
        if ($sortOrder === null)    $sortOrder = 'ASC';

        /*
        $itemListParts = array();
        foreach ($itemList as $idex => $item)
        {
            array_push($itemListParts,
                       sprintf("%s[%d, %d]",
                               $item->tag, $item->tagId,
                               $item->userItemCount) );
        }
        $itemListStr = implode(', ', $itemListParts);

        Connexions::log("Connexions_View_Helper_HtmlTagCloud:: "
                          . "sortBy[ {$sortBy} ], "
                          . "sortOrder[ {$sortOrder} ], "
                          . count($itemList) . " items "
                          . "in list[ {$itemListStr} ]");
        // */


        // Create a sort function
        $sortFn = create_function('$a,$b',
                      '$aStr = $a->'. $sortBy .';'
                    . '$bStr = $b->'. $sortBy .';'
                    . '$cmp = strcasecmp($aStr, $bStr);'
                    .  ( (($sortOrder === 'ASC') || ($sortOrder === 'asc'))
                            ? ''
                              // Reverse the comparison to reverse the ASC sort
                            : '$cmp = ($cmp < 0 '
                              .         '? 1 '
                              .         ': ($cmp > 0 '
                              .             '? -1 '
                              .             ': 0));')
                    /*
                    . 'Connexions::log("HtmlTagCloud:cmp: '
                    .                   'a[{$aStr}], '
                    .                   'b[{$bStr}]: "'
                    .                   '.($cmp < 0'
                    .                       '? "&lt;"'
                    .                       ': ($cmp > 0'
                    .                           '? "&gt;"'
                    .                           ':"="))'
                    .                 ');'
                    */
                    . 'return  $cmp;');

        // Sort the item list
        $itemList->uasort($sortFn);

        // Create a Zend_Tag_Cloud renderer (by default, renders HTML)
        $cloud = new Zend_Tag_Cloud(
                array(
                    /* Make the Connexions_View_Helper_HtmlTagItem helper
                     * available.
                     */
                    'prefixPath'            => array(
                        'prefix'    => 'Connexions_View_Helper',
                        'path'      => APPLICATION_PATH .'/views/helpers/'
                     ),
                    'ItemList'              => &$itemList,
                    'CloudDecorator'        => array(
                        'decorator'         => 'htmlCloud',
                        'options'           => array(
                            'HtmlTags'      => array(
                                'ul'        => array(
                                    'class' =>'Tag_Cloud clearfix'
                                )
                            )
                        )
                    ),
                    'TagDecorator'          => array(
                        /* Use the Connexions_View_Helper_HtmlTagItem helper
                         * to render tag items.
                         */
                        'decorator'         => 'htmlTagItem',   //'htmlTag',
                        'options'           => array(
                            'HtmlTags'      => array(
                                'li'        => array(
                                    'class'=>'tag'
                                )
                            ),
                            'ClassList'     => array(
                                'size0', 'size1', 'size2', 'size3',
                                'size4', 'size5', 'size6'
                            )
                        )
                    )
                ));

        // Render the HTML
        $html .= $cloud->render();

        // Return the rendered HTML
        return $html;
    }
}
