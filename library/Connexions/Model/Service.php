<?php
/** @file
 *
 *  The abstract base class for a service that provides access to and
 *  operations on Connexions Domain Models and Model Sets.
 *
 *  This provides logical separation between the application and the Data
 *  Persistence Layer.  Users of a Service only have to deal with Domain Model
 *  abstractions.
 */
abstract class Connexions_Model_Service
{
    protected   $_modelName = null;
    protected   $_mapper    = null;

    const   ORDER_ASC       = 'ASC';
    const   ORDER_DESC      = 'DESC';

    /** @brief  Create a new Domain Model instance.
     *  @param  data    An array of name/value pairs used to initialize the
     *                  Domain Model.  All 'name's MUST be valid for the target
     *                  Domain Model.
     *
     *  @return A new Domain Model instance.
     */
    public function create($data = array())
    {
        $modelName = $this->_modelName;

        return new $modelName( $data );
    }

    /** @brief  Retrieve a single, existing Domain Model instance.
     *  @param  criteria    An array of name/value pairs that represent the
     *                      desired properties of the target Domain Model.  All
     *                      'name's MUST be valid for the target Domain Model.
     *
     *  @return A new Connexions_Model instance.
     */
    public function retrieve($criteria = array())
    {
        return $this->getMapper()->find( $criteria );
    }

    /** @brief  Retrieve a set of Domain Model instances.
     *  @param  criteria    An array of name/value pairs that represent the
     *                      desired properties of the target Domain Model.  All
     *                      'name's MUST be valid for the target Domain Model;
     *  @param  order       An array of name/direction pairs representing the
     *                      desired sorting order.  The 'name's MUST be valid
     *                      for the target Domain Model and the directions a
     *                      Model_Service::ORDER_* constant.  If an order is
     *                      omitted, Model_Service::ORDER_ASC will be used
     *                      [ no specified order ];
     *  @param  count       The maximum number of items from the full set of
     *                      matching items that should be returned
     *                      [ null == all ];
     *  @param  offset      The starting offset in the full set of matching
     *                      items [ null == 0 ].
     *
     *  @return A new Connexions_Model_Set.
     */
    public function retrieveSet($criteria  = array(),
                                $order     = null,
                                $count     = null,
                                $offset    = null)
    {
        if ($order !== null)
            $order = (array)$order;

        if (is_array($order))
        {
            // Ensure that we have all nave/direction pairs
            $newOrder = array();
            foreach ($newOrder as $name => $direction)
            {
                if (is_int($name))
                {
                    $name      = $direction;
                    $direction = self::ORDER_ASC;
                }
                else
                {
                    if ($direction !== self::ORDER_DESC)
                        $direction = self::ORDER_ASC;
                }

                $newOrder[$name] = $direction;
            }

            $order = $newOrder;
        }

        return $this->getMapper()->fetch( $criteria, $order, $count, $offset );
    }

    /** @brief  Retrieve a paginated set of Domain Model instances.
     *  @param  criteria    An array of name/value pairs that represent the
     *                      desired properties of the target Domain Model.  All
     *                      'name's MUST be valid for the target Domain Model;
     *  @param  order       An array of name/direction pairs representing the
     *                      desired sorting order.  The 'name's MUST be valid
     *                      for the target Domain Model and the directions a
     *                      Model_Service::ORDER_* constant.  If an order is
     *                      omitted, Model_Service::ORDER_ASC will be used
     *                      [ no specified order ];
     *
     *  @return A new Connexions_Model_Set.
     */
    public function retrievePaginated($criteria  = array(),
                                      $order     = null)
    {
        $set = $this->getMapper()->fetch( $criteria, $order );
        return new Zend_Paginator( $set );
    }
                                      
    /** @brief  Initiate an update of the provided Domain Model instance.
     *  @param  model   The Domain Model instance to update.
     *
     *  Note: For simple Domain Models, this can also be accomplished
     *        directly via the Domain Model (e.g. $model->save() ).
     *
     *  @return The updated Domain Model instance.
     */
    public function update(Connexions_Model $model)
    {
        return $model->update();
    }

    /** @brief  Initiate the deletion of the provided Domain Model instance.
     *  @param  model   The Domain Model instance to delete.
     *
     *  Note: For simple Domain Models, this can also be accomplished
     *        directly via the Domain Model (e.g. $model->delete() ).
     *
     *  @return void
     */
    public function delete(Connexions_Model $model)
    {
        $model->delete();
    }


    /*********************************************************************
     * Protected methods
     *
     */

    /** @brief  Retrieve the mapper for this Service.
     *
     *  @return The Connexions_Model_Mapper instance.
     */
    protected function getMapper()
    {
        if (is_string($this->_mapper))
        {
            $this->_mapper =
                Connexions_Model_Mapper::factory( $this->_mapper );

            /*
            Connexions::log("Connexions_Model_Service::getMapper(): "
                            .   "mapper[ %s ]",
                            get_class($this->_mapper));
            // */
        }

        return $this->_mapper;
    }
}

