<?php
/** @file
 *
 *  The concrete base class providing access to Model_Group (memberGroup) and 
 *  Model_Set_Group.
 */
class Service_Group extends Connexions_Service
{
    /* inferred via classname
    protected   $_modelName = 'Model_Group';
    protected   $_mapper    = 'Model_Mapper_Group'; */

    /** @brief  Any default ordering that should be be merged into a specified 
     *          order.
     */
    protected   $_defaultOrdering   = array(
        'name'       => 'ASC',
        'visibility' => 'DESC', // public, private, group
        'groupType'  => 'DESC', // item, tag, user
    );

    /** @brief  Retrieve a set of groups related by a set of Users.
     *  @param  users   A Model_Set_User instance, array, or comma-separated
     *                  string of users to match.
     *  @param  order   Optional ORDER clause (string, array)
     *                      [ 'name ASC, visibility  DESC, groupType DESC' ]
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *
     *  @return A new Model_Set_Group instance.
     */
    public function fetchByOwners($users,
                                  $exact    = false,
                                  $order    = null,
                                  $count    = null,
                                  $offset   = null)
    {
        $ids     = array('ownerId' => $this->_csList2array($users));
        $normIds = $this->_mapper->normalizeIds($ids);
        if ($order === null)
        {
            $order = array('name       ASC',
                           'visibility DESC',
                           'groupType  DESC');
        }
        else
        {
            $order = $this->_csOrder2array($order);
        }

        /*
        Connexions::log('Service_Group::fetchByOwners(): '
                        .   'users[ %s ], ids[ %s ], normIds[ %s ]',
                        Connexions::varExport($users),
                        Connexions::varExport($ids),
                        Connexions::varExport($normIds));
        // */

        return $this->_mapper->fetch( $normIds,
                                      $order,
                                      $count,
                                      $offset );
    }

    /*************************************************************************
     * Protected helpers
     *
     */
}

