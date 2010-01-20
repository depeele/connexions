<?php
/** @file
 *
 *  An adapter to translate between a Connexions_Set and a Zend_Tag_ItemList.
 *
 */

/** @brief  An adapter to translate between Connexions_Set and
 *          Zend_Tag_ItemList
 *
 *  This class provides lazy evaluation of the list items, returning a
 *  Connexions_Model instance during iteration that contains additional
 *  contextual information (i.e. whether or not the item is currently selected
 *                               and the URL to use when presenting the item).
 *
 *  Zend_Tag_ItemList implements
 *      Countable, SeekableIterator, ArrayAccess
 */
class Connexions_Set_ItemList extends Zend_Tag_ItemList
{
    protected   $_reqUrl        = null;
    protected   $_reqInfo       = null;
    protected   $_reqStr        = null; // Simplified version of the original
                                        // comma-separated string of items.
    protected   $_validList     = null; // Normalized _reqInfo->validList

    /** @brief  Constructor
     *  @param  iterator    A traversable iterator representing the item list.
     *  @param  reqInfo     A Connexions_Set_Info instance containing
     *                      information about any "items" specified in the
     *                      request.
     *  @param  url         The base url for items
     *                      (defaults to the request URL).
     */
    public function __construct(Traversable         $iterator,
                                Connexions_Set_Info $reqInfo    = null,
                                                    $url        = null)
    {
        if ($reqInfo !== null)
            $this->_reqInfo  =& $reqInfo;

        $this->setUrl($url);

        if ($this->_reqInfo !== null)
        {
            $this->_reqStr = $this->_reqInfo->reqStr;

            // Case-normalize all string in validList
            $this->_validList = array();
            foreach ($this->_reqInfo->validList as $str)
            {
                array_push($this->_validList, strtolower($str));
            }

            if (is_string($this->_reqStr))
            {
                /* decode, collapse spaces, and removing any space around ','
                 * from the incoming 'reqUrl'
                 */
                $items = urldecode($this->_reqStr);
                $items = preg_replace('/\s+/',     ' ', $items);
                $items = preg_replace('/\s*,\s*/', ',', $items);

                $this->_reqStr = $items;
            }
        }

        /* Convert the list represented by the incoming iterator into a simple
         * array of instances that implement Zend_Tag_Taggable.
         */
        foreach ($iterator as $item)
        {
            $completed = $this->_completeItem($item);

            /*
            Connexions::log(
                    sprintf("Connexions_Set_ItemList: #%d [ %s : %f ]",
                            count($this->_items),
                            $completed->getTitle(),
                            $completed->getWeight()) );
            // */

            array_push($this->_items, $completed);
        }

        /*
        Connexions::log(sprintf("Connexions_Set_ItemList:: ".
                                    "reqUrl[ %s ], ".
                                    "reqStr[ %s ]",
                                $this->_reqUrl,
                                $this->_reqStr));
        // */
    }

    public function setUrl($url)
    {
        if (@empty($url))
        {
            /* Retrieve the current request URL.  Simplify it by removing any
             * query/fragment, urldecoding, collapsing spaces, trimming any
             * right-most white-space and ending '/'
             */
            $url = Connexions::getRequestUri();
        }
        else
        {
            $url = Connexions::url($url);
        }

        $url = preg_replace('/[\?#].*$/', '',  $url);   // query/fragment
        $url = urldecode($url);
        $url = preg_replace('/\s\s+/',    ' ', $url);   // white-space collapse
        $url = rtrim($url, " \t\n\r\0\x0B/");

        $this->_reqUrl = $url;

        /* (Re)complete all items using this new URL
         *
         * Note: The first time this is called from the constructure, the
         *       _items array will be empty.  It's initiallly filled at the end
         *       of the constructor.
         *
         */
        foreach ($this->_items as &$item)
        {
            $item = $this->_completeItem($item);
        }
    }

    /** @brief  Spread values in the items relative to their weight.
     *  @param  values  An array of values to spread into.
     *
     *  @throws Zend_Tag_Exception  When value list is empty.
     *  @return void
     */
    public function spreadWeightValues(array $values)
    {
        // Modeled after Zend_Tag_ItemList::spreadWeightValues()
        $numValues = @count($values);

        /*
        Connexions::log(
                    sprintf("Connexions_Set_ItemList::spreadWeightValues: "
                              . "%d values [ %s ]",
                              $numValues,
                              implode(", ", $values)));
        // */

        if ($numValues < 1)
            throw new Zend_Tag_Exception('Value list may not be empty');

        // Re-index the array
        $values = array_values($values);

        // If just a single value is supplied, simply assign it to all items
        if ($numValues === 1)
        {
            foreach ($this->_items as $item)
            {
                $item->setParam('weightValue', $values[0]);
            }
        }
        else
        {
            // Calculate min and max weights
            $minWeight = null;
            $maxWeight = null;

            foreach ($this->_items as $idex => $item)
            {
                /*
                Connexions::log(
                    sprintf("Connexions_Set_ItemList::spreadWeightValues: "
                              . "item #%d: title[ %s ], weight[ %s ]",
                              $idex, $item->getTitle(), $item->getWeight()));
                // */

                if (($minWeight === null) && ($maxWeight === null))
                {
                    $minWeight = $item->getWeight();
                    $maxWeight = $item->getWeight();
                }
                else
                {
                    $minWeight = min($minWeight, $item->getWeight());
                    $maxWeight = max($maxWeight, $item->getWeight());
                }
            }

            // Calculate the thresholds
            $steps      = count($values);
            $delta      = ($maxWeight - $minWeight) / ($steps - 1);
            $thresholds = array();

            for ($idex = 0; $idex < $steps; $idex++)
            {
                $thresholds[$idex] =
                    floor(100 * log(($minWeight + ($idex * $delta)) + 2));
            }

            /*
            Connexions::log(
                    sprintf("Connexions_Set_ItemList::spreadWeightValues: "
                              . "min/max[%f / %f], steps[ %d ], delta[ %f ], "
                              . "thresholds[ %s ]",
                              $minWeight, $maxWeight, $steps, $delta,
                              implode(", ", $thresholds)));
            // */

            // Assign the weight values
            foreach ($this->_items as $item)
            {
                $threshold = floor(100 * log($item->getWeight() + 2));

                for ($idex = 0; $idex < $steps; $idex++)
                {
                    if ($threshold <= $thresholds[$idex])
                    {
                        $item->setParam('weightValue', $values[$idex]);
                        break;
                    }
                }
            }
        }
    }

    /*************************************************************************
     * Protected helpers
     *
     */

    /** @brief  Given a Connexions_Model instance that implements
     *          Zend_Tag_Taggable, complete it by including Zend_Tag_Taggable
     *          parameters 'selected' and 'url'.
     *  @param  item    The item to complete.
     */
    protected function _completeItem(Connexions_Model $item)
    {
        if (! $item instanceof Zend_Tag_Taggable)
            throw new Exception("Connexions_Set_ItemList: "
                                    ."Items MUST implement Zend_Tag_Taggable");

        if ($this->_reqInfo === null)
            return $item;

        /* Include additional parameters for this item:
         *      selected    boolean indicating whether this item is in the
         *                  item list for the current request / view;
         *      url         The url to visit if this item is clicked.
         */
        $itemStr  = strtolower($item->__toString());
        $itemList = $this->_validList;  //$this->_reqInfo->validList;

        $url = $this->_reqUrl;
        if (! @empty($this->_reqStr))
            // Remove the requested items from the request URL
            $url = str_replace('/'. $this->_reqStr, '', $url);

        //if ($this->_reqInfo->isValidItem($itemStr))
        if (@in_array($itemStr, $itemList))
        {
            // Remove this item from the new item list.
            $item->setParam('selected', true);

            $itemList = array_diff($itemList, array($itemStr));
        }
        else
        {
            // Include this item in the item list.
            $item->setParam('selected', false);

            $itemList[] = $itemStr;
        }
        $url .= '/'. implode(',', $itemList);

        $item->setParam('url', $url);

        /*
        Connexions::log(
                sprintf("Connexions_Set_ItemList::current: "
                            . "reqUrl[ %s ], "
                            . "itemStr[ %s ], "
                            . "weight[ %f ], "
                            . "valid list[ %s ], "
                            . "selected[ %s ]: "
                            . "is %sselected, url[ %s ]",
                        $this->_reqUrl,
                        $itemStr,
                        $item->getWeight(),
                        implode(', ', $this->_validList),
                        $this->_reqStr,
                        ($item->getParam('selected') ? '' : 'NOT '),
                        $item->getParam('url') ));
        // */

        return $item;
    }

    /*************************************************************************
     * ArrayIterator interface :: sorting
     *
     */
    public function asort()
                        { return asort($this->_items); }
    public function ksort()
                        { return ksort($this->_items); }
    public function uasort($cmp_func)
                        { return uasort($this->_items, $cmp_func); }
    public function uksort($cmp_func)
                        { return uksort($this->_items, $cmp_func); }
}
