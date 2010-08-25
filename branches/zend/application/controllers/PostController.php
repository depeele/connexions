<?php
/** @file
 *
 *  This controller controls bookmark posting and is accessed
 *  via the url/routes:
 *      /post[ post parameters ]
 */

class PostController extends Connexions_Controller_Action
{
    // Tell Connexions_Controller_Action_Helper_ResourceInjector which
    // Bootstrap resources to make directly available
    public  $dependencies = array('db','layout');

    public      $contexts   = array(
                                'index' => array('json', 'partial'),
                              );

    protected   $_maxTagsRecommended    = 20;
    protected   $_maxTagsViewer         = 20;

    public function init()
    {
        parent::init();

        // Initialize context switching (via $this->contexts)
        $cs = $this->_helper->contextSwitch();
        $cs->initContext();
    }

    /** @brief  Index/Get/Read/View action.
     *
     *  Handle the presentation and processing of a bookmark post.
     */
    public function indexAction()
    {
        //Connexions::log("PostController::indexAction");

        $request  =& $this->_request;

        if ( (! $this->_viewer instanceof Model_User) ||
             (! $this->_viewer->isAuthenticated()) )
        {
            /* Unauthenticated user -- Redirect to signIn with a flash
             * indicating that it should return here upon successful
             * authentication.
             */
            return $this->_redirectToSignIn();
        }

        //$this->layout->setLayout('post');
        //$this->_helper->layout->setLayout('post');

        $bookmark =  null;
        $postIn   =  array(
            'url'           => trim($request->getParam('url',         null)),
            'name'          => trim($request->getParam('name',        null)),
            'description'   => trim($request->getParam('description', null)),
            'rating'        => $request->getParam('rating',           null),
            'isFavorite'    => $request->getParam('isFavorite',       null),
            'isPrivate'     => $request->getParam('isPrivate',        null),
            'tags'          => trim($request->getParam('tags',        null)),
            'mode'          => $request->getParam('mode',             null),
        );
        $postInfo = $postIn;

        if ($postIn['rating'] === null)
            unset($postInfo['rating']);

        if ($postIn['isFavorite'] === null)
            unset($postInfo['isFavorite']);

        if ($postIn['isPrivate'] === null)
            unset($postInfo['isPrivate']);

        /*
        Connexions::log("PostController::indexAction: "
                        . "postInfo [ %s ]",
                        Connexions::varExport($postInfo));
        // */


        if ($request->isPost())
        {
            // This is a POST -- attempt to create/update a bookmark
            $bookmark = $this->_doPost( $postInfo );
        }
        else
        {
            /* Initial presentation of posting form.
             *
             * Retrieve any existing bookmark for the given URL by the current
             * user.
             */
            $bookmark = $this->_doGet( $postInfo );
        }

        $this->view->headTitle('Save a Bookmark');

        $this->view->viewer     = $viewer;
        $this->view->postInfo   = $postInfo;
        $this->view->bookmark   = $bookmark;

        $this->view->suggest    = $this->_genSuggest($postInfo);

        $this->_handleFormat();
    }

    /*************************************************************************
     * Protected Helpers
     *
     */

    /** @brief  Given incoming bookmark-related data, generate suggestion
     *          configuration data.
     *  @param  param   postInfo    An array of incoming data.
     *
     *  @return An array of suggestion configuration data.
     */
    protected function _genSuggest(array &$postInfo)
    {
        $suggest    = array(
            'tags'              => array(
                'recommended'   => array(
                    //'namespace'     => 'suggest:tags:recommended',
                    'showRelation'  => false,
                    'showOptions'   => false,
                    'highlightCount'=> 0,
                ),
                'top'           => array(
                    //'namespace'     => 'suggest:tags:top',
                    'showRelation'  => false,
                    'showOptions'   => false,
                    'highlightCount'=> 0,
                    'title'         => 'Your top '.  $this->_maxTagsViewer,
                    'expanded'      => false,
                ),
            ),
            'people'            => array(
                'network'       => array(
                    //'namespace'     => 'suggest:people:network',
                    'showRelation'  => false,
                    'showOptions'   => false,
                    'highlightCount'=> 0,
                ),
            ),
        );


        $tService = $this->service('Tag');

        if (! empty($postInfo['url']))
        {
            // Locate the top '_maxTagsRecommended' tags for this item
            $itemId = (empty($postInfo['itemId'])
                        ? $postInfo['url']
                        : $postInfo['itemId']);

            /* Retrieve the top '_maxTagsRecommended' tags associated with the
             * target Item
             */
            $fetchOrder = array('userItemCount DESC',
                                'userCount     DESC',
                                'itemCount     DESC',
                                'tag           ASC');

            $tags = $tService->fetchByItems($itemId,
                                            $fetchOrder,
                                            $this->_maxTagsRecommended);

            if ($tags !== null)
            {
                $config =& $suggest['tags']['recommended'];

                $config['items']            = $tags;
                $config['itemsType']        =
                                 View_Helper_HtmlItemCloud::ITEM_TYPE_ITEM;
                $config['weightName']       = 'userItemCount';
                $config['weightTitle']      = 'Bookmarks with this tag';
                $config['titleTitle']       = 'Tag';
                $config['currentSortBy']    =
                                 View_Helper_HtmlItemCloud::SORT_BY_WEIGHT;
                $config['currentSortOrder'] =
                                 Connexions_Service::SORT_DIR_DESC;
                $config['displayStyle']     =
                                 View_Helper_HtmlItemCloud::STYLE_CLOUD;
            }
        }

        if (! empty($this->_viewer))
        {
            // Retrieve the top '_maxTagsViewer' tags for '_viewer'
            $fetchOrder = array('userItemCount DESC',
                                'userCount     DESC',
                                'itemCount     DESC',
                                'tag           ASC');

            $tags = $tService->fetchByUsers($this->_viewer,
                                            $fetchOrder,
                                            $this->_maxTagsViewer);

            if ($tags !== null)
            {
                $config =& $suggest['tags']['top'];

                $config['items']            = $tags;
                $config['itemsType']        =
                                 View_Helper_HtmlItemCloud::ITEM_TYPE_ITEM;
                $config['weightName']       = 'userItemCount';
                $config['weightTitle']      = 'Bookmarks with this tag';
                $config['titleTitle']       = 'Tag';
                $config['currentSortBy']    =
                                 View_Helper_HtmlItemCloud::SORT_BY_WEIGHT;
                $config['currentSortOrder'] =
                                 Connexions_Service::SORT_DIR_DESC;
                $config['displayStyle']     =
                                 View_Helper_HtmlItemCloud::STYLE_CLOUD;
            }
        }

        // :TODO: Retrieve the viewer's network.
        if (false)
        {
            $fetchOrder = array('totalItems DESC',
                                'totalTags  DESC',
                                'lastVisit  DESC',
                                'name       ASC');

            if ($people !== null)
            {
                $config =& $suggest['people']['network'];

                $config['items']            = $people;
                $config['itemsType']        =
                                 View_Helper_HtmlItemCloud::ITEM_TYPE_USER;
                $config['weightName']       = 'totalItems';
                $config['weightTitle']      = 'Total Bookmarks';
                $config['titleTitle']       = 'Person';
                $config['currentSortBy']    =
                                 View_Helper_HtmlItemCloud::SORT_BY_WEIGHT;
                $config['currentSortOrder'] =
                                 Connexions_Service::SORT_DIR_DESC;
                $config['displayStyle']     =
                                 View_Helper_HtmlItemCloud::STYLE_LIST;
            }
        }

        return $suggest;
    }

    /** @brief  Given incoming POST data, attempt to create/update a bookmark.
     *  @param  param   postInfo    An array of incoming POST data.
     *
     *  @return The new/updated Model_Bookmark instance (null on error).
     */
    protected function _doPost(array &$postInfo)
    {
        $error    = null;
        $boomkark = null;
        $bService = $this->service('Bookmark');
        try
        {
            $postInfo['user']   = $this->_viewer;
            $postInfo['itemId'] = $postInfo['url'];
            unset($postInfo['url']);

            $bookmark = $bService->get($postInfo);
            if ($bookmark === null)
            {
                $error = "Cannot create new bookmark (internal error)";
            }
            else if (! $bookmark->isValid())
            {
                $messages = $bookmark->getValidationMessages();
                $errors   = array();
                foreach ($messages as $field => $message)
                {
                    array_push($errors,
                               sprintf("%s: %s", $field, $message));
                }

                $error = implode(', ', $errors);
            }
            else
            {
                /* Attempt to save this bookmark.  This should either
                 * update or create
                 */

                /*
                Connexions::log("PostController: Got Bookmark: [ %s ]",
                                $bookmark->debugDump());
                // */

                $method = ($bookmark->isBacked()
                            ? 'updated'
                            : 'created');

                $bookmark = $bookmark->save();

                $postInfo['itemId'] = $bookmark->itemId;

                /*
                Connexions::log("PostController: %s Bookmark: [ %s ]",
                                ucfirst($method),
                                $bookmark->debugDump());
                // */
            }
        }
        catch (Exception $e)
        {
            $error = $e->getMessage();
        }

        if ($error !== null)
        {
            $this->view->error = $error;
        }
        else
        {
            $postInfo['method'] = $method;
        }

        return $bookmark;
    }

    /** @brief  Given incoming bookmark-related data, see if a matching
     *          bookmark exists and, if so, update 'postInfo' to represent the
     *          data of the bookmark.
     *  @param  param   postInfo    An array of incoming data.
     *
     *  @return The matching Model_Bookmark instance (null if no match).
     */
    protected function _doGet(array &$postInfo)
    {
        if (empty($postInfo['url']))
            return null;

        $bService = $this->service('Bookmark');
        $bookmark = $bService->find( array(
                                        'user'   => $this->_viewer,
                                        'itemId' => $postInfo['url'],
                                     ));

        if ($bookmark !== null)
        {
            /*
            Connexions::log("PostController::indexAction: "
                            . "existing bookmark information [ %s ]",
                            Connexions::varExport(
                                            $bookmark->toArray()) );
            // */

            /* The user has an existing bookmark.  Fill in any data
             * that was NOT provided directly.
             */
            $postInfo['userId'] = $bookmark->userId;
            $postInfo['itemId'] = $bookmark->itemId;

            if (empty($postInfo['name']))
                $postInfo['name'] = $bookmark->name;

            if (empty($postInfo['description']))
                $postInfo['description'] = $bookmark->description;

            if ($postInfo['rating'] === null)
                $postInfo['rating'] = $bookmark->rating;

            if ($postInfo['isFavorite'] === null)
                $postInfo['isFavorite'] = $bookmark->isFavorite;

            if ($postInfo['isPrivate'] === null)
                $postInfo['isPrivate'] = $bookmark->isPrivate;

            if (empty($postInfo['tags']))
            {
                $postInfo['tags'] =
                    preg_replace('/\s*,\s*/', ', ',
                                 $bookmark->tags->__toString());
            }
        }

        return $bookmark;
    }

    /** @brief  Determine the proper rendering format.  The only ones we deal
     *          with directly are:
     *              partial - render a single part of this page
     *              html    - normal HTML rendering
     *
     *  All others are handled by the 'contextSwitch' established in
     *  this controller's init method.
     */
    protected function _handleFormat()
    {
        $format =  $this->_helper->contextSwitch()->getCurrentContext();

        /*
        Connexions::log("PostController::_handleFormat: context [ %s ]",
                        $format);
        // */

        if (empty($format))
            $format = $this->_request->getParam('format', 'html');

        Connexions::log("PostController::_handleFormat: [ %s ]", $format);

        switch ($format)
        {
        case 'html':
            // Normal HTML rendering includes the sidebar
            $this->render('index');
            break;

        case 'json':
        default:
            /*
            Connexions::log("PostController::_handleFormat: "
                            .   "render 'index-%s'",
                            $format);
            // */

            $this->render('index-'. $format);


            /*
            Connexions::log("PostController::_handleFormat: "
                            .   "render 'index.%s' COMPLETE",
                            $format);
            // */
            break;
        }
    }
}

