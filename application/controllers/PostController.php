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

        $this->view->headTitle('Save a Bookmark');

        $this->_handleFormat();
    }

    /*************************************************************************
     * Protected Helpers
     *
     */

    /** @brief  Prepare for rendering the main view, regardless of format.
     *
     *  This will collect the variables needed to render the main view, placing
     *  them in $view->main as a configuration array.
     */
    protected function _prepareMain($htmlNamespace  = '')
    {
        Connexions::log("PostController::_prepareMain():");

        parent::_prepareMain($htmlNamespace);

        $request  =& $this->_request;
        $bookmark =  null;
        $postInfo =  array(
            'url'           => trim($request->getParam('url',         null)),
            'name'          => trim($request->getParam('name',        null)),
            'description'   => trim($request->getParam('description', null)),
            'rating'        => $request->getParam('rating',           null),
            'isFavorite'    => $request->getParam('isFavorite',       null),
            'isPrivate'     => $request->getParam('isPrivate',        null),
            'tags'          => trim($request->getParam('tags',        null)),
            'mode'          => $request->getParam('mode',             null),
        );

        if ($postInfo['rating'] === null)
            unset($postInfo['rating']);

        if ($postInfo['isFavorite'] === null)
            unset($postInfo['isFavorite']);

        if ($postInfo['isPrivate'] === null)
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

        $this->view->viewer     = $viewer;
        $this->view->postInfo   = $postInfo;
        $this->view->bookmark   = $bookmark;

        if (Connexions::to_bool($request->getParam('excludeSuggestions',
                                                   false)) !== true)
        {
            $this->view->suggest = $this->_prepareSuggestions($postInfo);
        }
    }

    /** @brief  Given incoming bookmark-related data, generate suggestion
     *          configuration data.
     *  @param  param   postInfo    An array of incoming data.
     *
     *  @return An array of suggestion configuration data.
     */
    protected function _prepareSuggestions(array &$postInfo)
    {
        Connexions::log("PostController::_prepareSuggestions():");

        $suggest        = array(
            'tags'      => $this->_prepareSuggestions_Tags($postInfo),
            'people'    => $this->_prepareSuggestions_People($postInfo),
        );

        return $suggest;
    }

    /** @brief  Given incoming bookmark-related data, prepare the 'tags' pane 
     *          for the suggestions area.
     *  @param  param   postInfo    An array of incoming data.
     *
     *  @return An array of tags pane configuration data.
     */
    protected function _prepareSuggestions_Tags(array &$postInfo)
    {
        // /*
        Connexions::log("PostController::_prepareSuggestions_Tags(): "
                        . "partials[ %s ]",
                        Connexions::varExport($this->_partials));
        // */

        /* '_partials' represents any partial portion of a page we are
         * rendering.  For example, 'main-tags-recommended' where 'main' has 
         * already be extracted leaving '_partials' containing
         * [ tags, recommended ]
         */
        if ( (count($this->_partials) > 0) &&
             ($this->_partials[0] !== 'tags'))
        {
            /* We're not rendering the 'tags' portion so no configuration is 
             * needed
             */
            return array();
        }

        // We are rendering the 'tags' portion, possibly just a certain section
        $section = (count($this->_partials) > 1
                        ? $this->_partials[1]
                        : null);
        $config  = array();
        $service = $this->service('Tag');

        if ( ($section === null) || ($section === 'recommended') )
        {
            // Rendering all tabs/all sections OR 'tabs/recommended'

            // /*
            Connexions::log("PostController::_prepareSuggestions_Tags(): "
                            . "prepare 'recommended' section, url[ %s ]",
                            Connexions::varExport($postInfo['url']));
            // */


            $sConfig    = array(
                //'namespace'     => 'suggest:tags:recommended',
                'showRelation'  => false,
                'showOptions'   => false,
                'highlightCount'=> 0,
            );

            if (! empty($postInfo['url']))
            {
                // Locate the top '_maxTagsRecommended' tags for this item
                $itemId = (empty($postInfo['itemId'])
                            ? $postInfo['url']
                            : $postInfo['itemId']);

                /* Retrieve the top '_maxTagsRecommended' tags associated with 
                 * the target Item
                 */
                $fetchOrder = array('userItemCount DESC',
                                    'userCount     DESC',
                                    'itemCount     DESC',
                                    'tag           ASC');

                $tags = $service->fetchByItems($itemId,
                                                $fetchOrder,
                                                $this->_maxTagsRecommended);

                if ($tags !== null)
                {
                    $sConfig['items']            = $tags;
                    $sConfig['itemsType']        =
                                 View_Helper_HtmlItemCloud::ITEM_TYPE_ITEM;
                    $sConfig['weightName']       = 'userItemCount';
                    $sConfig['weightTitle']      = 'Bookmarks with this tag';
                    $sConfig['titleTitle']       = 'Tag';
                    $sConfig['currentSortBy']    =
                                 View_Helper_HtmlItemCloud::SORT_BY_WEIGHT;
                    $sConfig['currentSortOrder'] =
                                 Connexions_Service::SORT_DIR_DESC;
                    $sConfig['displayStyle']     =
                                 View_Helper_HtmlItemCloud::STYLE_CLOUD;
                }
            }

            $config['recommended'] = $sConfig;
        }

        if ( ($section === null) || ($section === 'top') )
        {
            // Rendering all tabs/all sections OR 'tabs/top'

            // /*
            Connexions::log("PostController::_prepareSuggestions_Tags(): "
                            . "prepare 'top' section, viewer[ %s ]",
                            Connexions::varExport($this->_viewer));
            // */

            $sConfig    = array(
                //'namespace'     => 'suggest:tags:top',
                'showRelation'  => false,
                'showOptions'   => false,
                'highlightCount'=> 0,
                'title'         => 'Your top '.  $this->_maxTagsViewer,
                'expanded'      => false,
            );

            // Retrieve the top '_maxTagsViewer' tags for '_viewer'
            $fetchOrder = array('userItemCount DESC',
                                'userCount     DESC',
                                'itemCount     DESC',
                                'tag           ASC');

            $tags = $service->fetchByUsers($this->_viewer,
                                           $fetchOrder,
                                           $this->_maxTagsViewer);

            if ($tags !== null)
            {
                $sConfig['items']            = $tags;
                $sConfig['itemsType']        =
                             View_Helper_HtmlItemCloud::ITEM_TYPE_ITEM;
                $sConfig['weightName']       = 'userItemCount';
                $sConfig['weightTitle']      = 'Bookmarks with this tag';
                $sConfig['titleTitle']       = 'Tag';
                $sConfig['currentSortBy']    =
                             View_Helper_HtmlItemCloud::SORT_BY_WEIGHT;
                $sConfig['currentSortOrder'] =
                             Connexions_Service::SORT_DIR_DESC;
                $sConfig['displayStyle']     =
                             View_Helper_HtmlItemCloud::STYLE_CLOUD;
            }

            $config['top'] = $sConfig;
        }

        return $config;
    }

    /** @brief  Given incoming bookmark-related data, prepare the 'people' pane 
     *          for the suggestions area.
     *  @param  param   postInfo    An array of incoming data.
     *
     *  @return An array of tags pane configuration data.
     */
    protected function _prepareSuggestions_People(array &$postInfo)
    {
        // /*
        Connexions::log("PostController::_prepareSuggestions_People(): "
                        . "partials[ %s ]",
                        Connexions::varExport($this->_partials));
        // */

        /* '_partials' represents any partial portion of a page we are
         * rendering.  For example, 'main-tags-recommended' where 'main' has 
         * already be extracted leaving '_partials' containing
         * [ tags, recommended ]
         */
        if ( (count($this->_partials) > 0) &&
             ($this->_partials[0] !== 'people'))
        {
            /* We're not rendering the 'people' portion so no configuration is 
             * needed
             */
            return array();
        }

        /* We are rendering the 'people' portion, possibly just a certain 
         * section
         */
        $section = (count($this->_partials) > 1
                        ? $this->_partials[1]
                        : null);
        $config  = array();
        $service = $this->service('User');

        if ( ($section === null) || ($section === 'network') )
        {
            // Rendering all tabs/all sections OR 'tabs/recommended'

            $sConfig    = array(
                //'namespace'     => 'suggest:tags:recommended',
                'showRelation'  => false,
                'showOptions'   => false,
                'highlightCount'=> 0,
            );

            // :TODO: Retrieve the viewer's network.
            $fetchOrder = array('totalItems DESC',
                                'totalTags  DESC',
                                'lastVisit  DESC',
                                'name       ASC');
            $people     = null;

            if ($people !== null)
            {
                $sConfig['items']            = $people;
                $sConfig['itemsType']        =
                                 View_Helper_HtmlItemCloud::ITEM_TYPE_USER;
                $sConfig['weightName']       = 'totalItems';
                $sConfig['weightTitle']      = 'Total Bookmarks';
                $sConfig['titleTitle']       = 'Person';
                $sConfig['currentSortBy']    =
                                 View_Helper_HtmlItemCloud::SORT_BY_WEIGHT;
                $sConfig['currentSortOrder'] =
                                 Connexions_Service::SORT_DIR_DESC;
                $sConfig['displayStyle']     =
                                 View_Helper_HtmlItemCloud::STYLE_LIST;
            }

            $config['network'] = $sConfig;
        }

        return $config;
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

    /** @brief  Render the sidebar based upon the incoming request.
     *  @param  usePlaceholder      Should the rendering be performed
     *                              immediately into a placeholder?
     *                              [ true, into the 'right' placeholder ]
     *
     *
     *  :XXX: Override to produce nothing since Post currently has no sidebar.
     */
    protected function _renderSidebar($userPlaceholde = true)
    {
        return;
    }
}
