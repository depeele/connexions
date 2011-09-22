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
    public    $dependencies = array('db','layout');
    public    $contexts     = array(
                                'index' => array('json', 'partial'),
                              );
    protected $_noSidebar   = true;

    protected $_maxTagsRecommended    = 20;
    protected $_maxTagsViewer         = 20;

    public function init()
    {
        $this->_baseUrl    = $this->_helper->url(null, 'post');
        $this->_cookiePath = $this->_baseUrl;

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
    protected function _prepare_main()
    {
        //Connexions::log("PostController::_prepare_main():");

        parent::_prepare_main();

        $request  =& $this->_request;
        $bookmark =  null;
        $postInfo =  array(
            'url'           => trim($request->getParam('url',         null)),
            'name'          => trim($request->getParam('name',
                                    //Backward compatability
                                    $request->getParam('title',       null))),
            'description'   => trim($request->getParam('description', null)),
            'rating'        => $request->getParam('rating',           null),
            'isFavorite'    => $request->getParam('isFavorite',       null),
            'isPrivate'     => $request->getParam('isPrivate',
                                    //Backward compatability
                                    $request->getParam('private',     null)),
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

        // Allow 'closeAction' to be specified in the request.
        $this->view->closeAction = trim($request->getParam('closeAction',
                                                           'back'));

        $this->view->postInfo   = $postInfo;
        $this->view->bookmark   = $bookmark;

        if (Connexions::to_bool($request->getParam('excludeSuggestions',
                                                   false)) !== true)
        {
            $this->view->tabs = $this->_prepare_suggestions($postInfo);
        }
    }

    /** @brief  Given incoming bookmark-related data, generate suggestion
     *          configuration data.
     *  @param  param   postInfo    An array of incoming data.
     *
     *  @return An array of suggestion configuration data.
     */
    protected function _prepare_suggestions(array &$postInfo)
    {
        //Connexions::log("PostController::_prepare_suggestions():");

        // Suggestion tabs with collapsible sections
        $suggest        = array(
            'tags'      => array(
                'title'     => 'Tags',
                'script'    => 'main-tags',
                'cssClass'  => 'suggested-tags',
                'sections'  => $this->_prepare_suggestions_Tags($postInfo),
            ),
            'people'    => array(
                'title'     => 'People',
                'script'    => 'main-people',
                'cssClass'  => 'suggested-people',
                'sections'  => $this->_prepare_suggestions_People($postInfo),
            ),
        );

        return $suggest;
    }

    /** @brief  Given incoming bookmark-related data, prepare the 'tags' pane 
     *          for the suggestions area.
     *  @param  param   postInfo    An array of incoming data.
     *
     *  @return An array of tags pane configuration data.
     */
    protected function _prepare_suggestions_Tags(array &$postInfo)
    {
        /*
        Connexions::log("PostController::_prepare_suggestions_Tags(): "
                        . "partials[ %s ]",
                        Connexions::varExport($this->_partials));
        // */

        /* '_partials' represents any partial portion of a page we are
         * rendering.  For example, 'main-tags-recommended' would result in
         * '_partials' of [ 'main', 'tags', 'recommended' ].
         */
        if ( (count($this->_partials) > 1) &&
             ($this->_partials[1] !== 'tags'))
        {
            /* We're not rendering the 'tags' portion so no configuration is 
             * needed
             */
            return array();
        }

        // We are rendering the 'tags' portion, possibly just a certain section
        $section  = (count($this->_partials) > 2
                        ? $this->_partials[2]
                        : null);
        $sections = array();
        $service  = $this->service('Tag');

        if ( ($section === null) || ($section === 'recommended') )
        {
            // Rendering all tabs/all sections OR 'tags/recommended'

            /*
            Connexions::log("PostController::_prepare_suggestions_Tags(): "
                            . "prepare 'recommended' section, url[ %s ]",
                            Connexions::varExport($postInfo['url']));
            // */

            $sections['recommended'] = array(
                'title'     => 'Recommended',
                'expanded'  => true,
                'script'    => 'main-tags-recommended',
                'config'    => array(
                    'namespace'     => 'suggest:tags:recommended',
                    'showRelation'  => false,
                    'showOptions'   => false,
                    'highlightCount'=> 0,
                ),
            );
            $config =& $sections['recommended']['config'];

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

                /*
                Connexions::log("PostController::_prepare_suggestions_Tags(): "
                                . "tags[ %s ]",
                                Connexions::varExport($tags));
                // */

                if ($tags !== null)
                {
                    $config['items']            = $tags;
                    $config['itemType']         =
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
        }

        if ( ($section === null) || ($section === 'top') )
        {
            // Rendering all tabs/all sections OR 'tags/top'

            /*
            Connexions::log("PostController::_prepare_suggestions_Tags(): "
                            . "prepare 'top' section, viewer[ %s ]",
                            Connexions::varExport($this->_viewer));
            // */
            //
            $sections['top'] = array(
                'title'     => 'Your top '. $this->_maxTagsViewer,
                'expanded'  => false,
                'script'    => 'main-tags-top',
                'config'    => array(
                    'namespace'     => 'suggest:tags:top',
                    'showRelation'  => false,
                    'showOptions'   => false,
                    'highlightCount'=> 0,
                ),
            );
            $config =& $sections['top']['config'];

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
                $config['items']            = $tags;
                $config['itemType']         =
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

        return $sections;
    }

    /** @brief  Given incoming bookmark-related data, prepare the 'people' pane 
     *          for the suggestions area.
     *  @param  param   postInfo    An array of incoming data.
     *
     *  @return An array of tags pane configuration data.
     */
    protected function _prepare_suggestions_People(array &$postInfo)
    {
        /*
        Connexions::log("PostController::_prepare_suggestions_People(): "
                        . "partials[ %s ]",
                        Connexions::varExport($this->_partials));
        // */

        /* '_partials' represents any partial portion of a page we are
         * rendering.  For example, 'main-people-network' would result in
         * '_partials' of [ 'main', 'people', 'network' ].
         */
        if ( (count($this->_partials) > 1) &&
             ($this->_partials[1] !== 'people'))
        {
            /* We're not rendering the 'people' portion so no configuration is 
             * needed
             */
            return array();
        }

        /* We are rendering the 'people' portion, possibly just a certain 
         * section
         */
        $section  = (count($this->_partials) > 2
                        ? $this->_partials[2]
                        : null);
        $sections = array();
        $service  = $this->service('User');

        if ( ($section === null) || ($section === 'network') )
        {
            // Rendering all tabs/all sections OR 'people/network'
            $sections['network'] = array(
                'title'     => 'Network',
                'expanded'  => false,
                'async'     => true,
                'script'    => 'main-people-network',
                'config'    => array(
                    'namespace'     => 'suggest:people:network',
                    'showRelation'  => false,
                    'showOptions'   => false,
                    'highlightCount'=> 0,
                ),
            );
            $config =& $sections['network']['config'];

            // :TODO: Retrieve the viewer's network.
            $network    = $this->_viewer->getNetwork();

            /*
            Connexions::log("PostController::_prepare_suggestions_People(): "
                            .   "viewer[ %s ], network[ %s ]",
                            $this->_viewer,
                            Connexions::varExport($network));
            // */

            $fetchOrder = array('name       ASC',
                                'totalItems DESC',
                                'totalTags  DESC',
                                'lastVisit  DESC');
            $people     = ($network
                            ? $network->getitems($fetchOrder)
                            : null);

            if ($people !== null)
            {
                $config['items']            = $people;
                $config['itemType']         =
                                 View_Helper_HtmlItemCloud::ITEM_TYPE_USER;
                $config['weightName']       = 'totalItems';
                $config['weightTitle']      = 'Total Bookmarks';
                $config['titleTitle']       = 'Person';
                $config['currentSortBy']    =
                                 View_Helper_HtmlItemCloud::SORT_BY_TITLE;
                $config['currentSortOrder'] =
                                 Connexions_Service::SORT_DIR_ASC;
                $config['displayStyle']     =
                                 View_Helper_HtmlItemCloud::STYLE_LIST;
            }
        }

        return $sections;
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

            if (empty($postInfo['tags']) && $bookmark->tags)
            {
                $postInfo['tags'] =
                    preg_replace('/\s*,\s*/', ', ',
                                 $bookmark->tags->__toString());
            }
        }

        return $bookmark;
    }
}
