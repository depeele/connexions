<?php
/** @file
 *
 *  An adapter to translate between a Connexions_Model_Set and
 *  Zend_Tag_ItemList to allow the set to be used in a Zend_Tag_Cloud.
 */
class Connexions_Model_Set_Adapter_ItemList extends Zend_Tag_ItemList
{
    protected   $_selected      = null;
    protected   $_selectedStr   = '';

    /** @brief  Constructor
     *  @param  set         The Connexions_Model_Set instance to adapt.
     *  @param  selected    The Connexions_Model_Set instance representing
     *                      those items that are currently selected
     *                      (or null if nothing selected);
     *  @param  baseUrl     The baseUrl to use for item completion;
     *  @param  weightName  The name of the field/member to use for weight
     *                      [ null / best guess ];
     *
     */
    public function __construct(Connexions_Model_Set    $set,
                                                        $selected,
                                                        $baseUrl,
                                                        $weightName = null)
    {
        $this->_selectedStr = ($selected instanceof Connexions_Model_Set
                                ?  $selected->__toString()
                                : '');
        $this->_selected    = array();

        $url = $baseUrl;
        if (! empty($this->_selectedStr))
        {
            $this->_selected = explode(',', $this->_selectedStr);

            // Remove the source string from the base url
            $url = str_replace($selected->getSource() .'/', '', $url);
        }

        /*
        Connexions::log("Connexions_Model_Set_Adapter_ItemList:: "
                        . "source[ %s ], selectedStr[ %s ], url[ %s ]",
                        ($selected !== null
                            ? $selected->getSource()
                            : ''),
                        $this->_selectedStr, $url);
        // */

        // Fill _items from the incoming set.
        foreach ($set as $item)
        {
            array_push($this->_items, $this->_completeItem($item,
                                                           $url,
                                                           $weightName));
        }
    }

    /*************************************************************************
     * ArrayIterator interface :: sorting
     *
     */
    public function asort()         { return asort($this->_items); }
    public function ksort()         { return ksort($this->_items); }
    public function natcasesort()   { return natcasesort($this->_items); }
    public function natsort()       { return natsort($this->_items); }
    public function usort($cmp)     { return usort($this->_items,  $cmp); }
    public function uasort($cmp)    { return uasort($this->_items, $cmp); }
    public function uksort($cmp)    { return uksort($this->_items, $cmp); }


    /*************************************************************************
     * Protected helpers
     *
     */

    /** @brief  Given a Connexions_Model instance that implements
     *          Zend_Tag_Taggable, complete it by including Zend_Tag_Taggable
     *          parameters 'selected' and 'url'.
     *  @param  item        The Connexions_Model instance to complete.
     *  @param  baseUrl     The baseurl to use for item completion
     *                      (minus the source string that caused the creation
     *                       of the selected item set);
     *  @param  weightName  The name of the field/member to use for weight;
     *
     *  @return The completed item.
     */
    protected function _completeItem(Zend_Tag_Taggable  $item,
                                                        $baseUrl,
                                                        $weightName)
    {
        /* Include additional parameters for this item:
         *      selected    boolean indicating whether or not this item is in
         *                  the list of those currently selected for this view;
         *      url         The url to visit if this item is clicked;
         */
        $title    = $item->getTitle();
        $urlId    = $item->getParam('urlId');
        $itemList = $this->_selected;

        if ($urlId === null)
            $urlId = $title;

        if (in_array($title, $itemList))
        {
            // Remove this item from the new item list.
            $item->setParam('selected', true);

            $itemList = array_diff($itemList, array($urlId));
        }
        else
        {
            $item->setParam('selected', false);

            array_push( $itemList, $urlId );
        }

        /*
        $item->setParam('selected',
                        ((! empty($this->_selected)) &&
                         in_array($title, $this->_selected)) );
        // */

        // Remove the current item from the selected list
        $url = $baseUrl . implode(',', $itemList);

        $item->setParam('url', $url);

        if ($weightName !== null)
        {
            try
            {
                $val = $item->__get($weightName);

                $item->setWeight($val);
            }
            catch (Exception $e)
            {
                // Ignore 'weightName'
            }
        }

        /*
        Connexions::log("Connexions_Model_Set_Adapter_ItemList::_completeItem()"
                        . ": title[ %s ], weight[ %s ], "
                        .   "selected[ %s ], url[ %s ]",
                        $title, $item->getWeight(),
                        ($item->getParam('selected')
                            ? 'true'
                            : 'false'),
                        $item->getParam('url') );
        // */

        return $item;
    }
}