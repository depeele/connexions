<?php
/** @file
 *
 *  Model for the Tag table.
 */

class Model_Tag extends Model_Taggable
{
    /* inferred via classname
    protected   $_mapper    = 'Model_Mapper_Tag'; */

    // The data for this Model
    protected   $_data      = array(
            'tagId'         => null,
            'tag'           => '',

            /* Note: these items are typically computed and may not be 
             *       persisted directly.
             */
            'userItemCount' => 0,
            'userCount'     => 0,
            'itemCount'     => 0,
    );

    /*************************************************************************
     * Connexions_Model abstract method implementations
     *
     */

    /** @brief  Retrieve the unique identifier for this instance.  This MAY 
     *          return an array of identifiers as key/value pairs.
     *
     *  @return The unique identifier.
     */
    public function getId()
    {
        return ( $this->tagId );
    }

    /*************************************************************************
     * Connexions_Model overrides
     *
     */

    /** @brief  Return a string representation of this instance.
     *
     *  @return The string-based representation.
     */
    public function __toString()
    {
        if (! empty($this->tag))
            return $this->tag;

        return parent::__toString();
    }

    /*************************************************************************
     * Zend_Tag_Taggable Interface (via Model_Taggable)
     *
     */

    /** @brief  Return an HTML-safe version of this items title.
     *
     *  @return An HTML-safe title.
     */
    public function getTitle()
    {
        $title = (String)($this->tag);

        return htmlspecialchars($title);
    }

    public function getWeight()
    {
        $weight = $this->getParam('weight');

        if ($weight === null)
        {
            if (@isset($this->weight))
                $weight = (Float)($this->weight);
            else
                $weight = (Float)($this->userItemCount);

            $this->setWeight($weight);
        }

        /*
        Connexions::log("Model_Tag::getWeight: "
                        . "weight[ %s ], "
                        . "userItemCount[ %s ] == [ %s ]",
                        $this->weight,
                        $this->userItemCount,
                        $weight);
        // */

        return (Float)$weight;
    }
}
