<?php
/** @file
 *
 *  A set of Activity Domain Models.
 *
 */

class Model_Set_Activity extends Connexions_Model_Set
{
    protected   $_modelName = 'Model_Activity';
    //protected   $_mapper    = 'Model_Mapper_Activity';

    /*************************************************************************
     * Conversions
     *
     */

    /** @brief  Return an array version of this instance.
     *  @param  props   Generation properties:
     *                      - deep      Deep traversal (true)
     *                                    or   shallow (false)
     *                                    [true];
     *                      - public    Include only public fields (true)
     *                                    or  also include private (false)
     *                                    [true];
     *                      - dirty     Include only dirty fields (true)
     *                                    or           all fields (false);
     *                                    [false];
     *                      - raw       Return the RAW, unprocessed data of the
     *                                  fields (true) or the processed data of
     *                                  the fields (false);
     *                                    [false];
     *
     *  Override Connexions_Model_Set::toArray() in order to force the use of
     *  __get() for each property.  This will in-turn use getProperties() to
     *  retrieve the 'properties' value, providing an unserialized version
     *  of the properties for use in the json-rpc calls.
     *
     *  @return An array representation of this Domain Model.
     */
    public function toArray(array $props    = array())
    {
        if (isset($props['raw']) && ($props['raw'] === true))
        {
            // Nothing special, simply use our parent
            return parent::toArray($props);
        }

        $res = array();
        foreach ($this->_members as $idex => $item)
        {
            if ($item === null)
            {
                // One or more members are missing...
                $this->_fillMembers($idex, $this->getCount());

                $item =& $this->_members[$idex];
            }

            if ($item instanceof Connexions_Model)
            {
                array_push($res, $item->toArray( $props ));
            }
            else if (is_object($item) && method_exists($item, 'toArray'))
            {
                array_push($res, $item->toArray());
            }
            else if (is_array($item))
            {
                if (is_string($item['properties']))
                {
                    try
                    {
                        $item['properties'] =
                            Zend_Json::decode($item['properties']);
                    }
                    catch (Exception $e)
                    {
                        Connexions::log("Model_Set_Activity::toArray(): "
                                        . "%s [ %s ]",
                                        $e->getMessage(),
                                        Connexions::varExport($item));
                    }
                }

                array_push($res, $item);
            }
        }

        return $res;
    }

    /** @brief  Return a string representation of this instance.
     *
     *  @return The string-based representation.
     */
    public function __toString()
    {
        $strs = array();
        foreach ($this->_members as $activity)
        {
            array_push($strs, (String)$activity);
        }

        return implode(',', $strs);
    }
}

