<?php
/** @file
 *
 *  The base class for Connexions Domain Models that also implement the
 *  Zend_Tag_Taggable interface (i.e. are presentable in a tag cloud).
 */
abstract class Model_Taggable extends Model_Base
                                implements  Zend_Tag_Taggable
{
    protected       $_params    = array();

    /** @brief  Retrieve a tag cloud related parameter.
     *  @param  name    The parameter name
     *                  (e.g. title, weight, weightValue, url, selected)
     *
     *  @return The parameter value (null if not set).
     */
    public function getParam($name)
    {
        $val = (@isset($this->_params[$name])
                        ? $this->_params[$name]
                        : null);
        return $val;
    }

    /** @brief  Set a tag cloud related parameter.
     *  @param  name    The parameter name
     *                  (e.g. title, weight, weightValue, url, selected)
     *  @param  value   The parameter value.
     *
     *  @return $this for a fluent interface.
     */
    public function setParam($name, $value)
    {
        // weight, weightValue, url, selected
        $this->_params[$name] = $value;

        return $this;
    }

    /** @brief  Retrieve the string that represents this item.
     *
     *  @return The String title.
     */
    public function getTitle()
    {
        return $this->getParam('title');
    }

    /** @brief  Retrieve the floating point value that represents the weight of
     *          this item.
     *
     *  @return The Floating point weight.
     */
    public function getWeight()
    {
        return (Float)($this->getParam('weight'));
    }

    /** @brief  Set the 'weight' Zend_Tag_Taggable parameter.
     *  @param  weight  The new weight.
     *
     *  @return $this for a fluent interface.
     */
    public function setWeight($weight)
    {
        $this->setParam('weight', $weight);

        return $this;
    }
}

