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
     *
     *  Note: This helper makes use of several 'view' variables:
     *          tagSet      A Model_TagSet instance representing the tags to be
     *                      presented;
     *          tagInfo     A Connexions_TagInfo instance containing
     *                      information about the requested tags;
     *
     *          maxTags     The maximum number of tags to present
     *                      [100 via $tagSet->get_Tag_ItemList()];
     *          sortBy      The tag field to sort by ['tag'];
     *          sortOrder   Sort order ( ['ASC'] | 'DESC').
     *
     *  @return The HTML representation of a tag cloud.
     */
    public function htmlTagCloud()
    {
        $html = '';

        if ( (! @isset($this->view->tagSet)) ||
             (! $this->view->tagSet instanceof Model_TagSet) )
            throw new Exception("The view MUST have a Model_TagSet ".
                                    "instance in 'tagSet'");

        if ( (! @isset($this->view->tagInfo)) ||
             (! $this->view->tagInfo instanceof Connexions_TagInfo) )
            throw new Exception("The view MUST have a Connexions_TagInfo ".
                                    "instance in 'tagInfo'");


        $maxTags   = (@isset($this->view->maxTags)
                                ? $this->view->maxTags   : null);
        $sortBy    = (@is_string($this->view->sortBy)
                                ? $this->view->sortBy    : 'tag');
        $sortOrder = (@is_string($this->view->sortOrder)
                                ? $this->view->sortOrder : 'ASC');

        // /*
        Connexions::log("Connexions_View_Helper_HtmlTagCloud:: "
                          . "tagInfo[ ".print_r($this->view->tagInfo,true)." ],"
                          . "maxTags[ {$maxTags} ], "
                          . "sortBy[ {$sortBy} ], "
                          . "sortOrder[ {$sortOrder} ]");
        // */

        // Retrieve the Zend_Tag_ItemList adapter for our set.
        $itemList = $this->view->tagSet->get_Tag_ItemList($maxTags,
                                                          $this->view->tagInfo);

        // Create a sort function
        $sortFn = create_function('$a,$b',
                      '$cmp = strcasecmp($a["'. $sortBy .'"],'
                    .                   '$b["'. $sortBy .'"]);'
                    .  ( (($sortOrder === 'ASC') || ($sortOrder === 'asc'))
                            ? ''
                              // Reverse the comparison to reverse the ASC sort
                            : '$cmp = ($cmp < 0 '
                              .         '? 1 '
                              .         ': ($cmp > 0 '
                              .             '? -1 '
                              .             ': 0));')
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
