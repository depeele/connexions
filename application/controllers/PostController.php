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
                                'index' => array('json'),
                              );

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
        Connexions::log("PostController::postAction");

        if ( (! $this->_viewer instanceof Model_User) ||
             (! $this->_viewer->isAuthenticated()) )
        {
            // Unauthenticated user -- Redirect to signIn
            return $this->_helper->redirector('signIn','auth');
        }

        //$this->layout->setLayout('post');
        //$this->_helper->layout->setLayout('post');

        $request  =& $this->_request;
        $bService = $this->service('Bookmark');
        $bookmark =  null;
        $postIn   =  array(
            'url'           => trim($request->getParam('url',         null)),
            'name'          => trim($request->getParam('name',        null)),
            'description'   => trim($request->getParam('description', null)),
            'rating'        => $request->getParam('rating',           null),
            'isFavorite'    => $request->getParam('isFavorite',       null),
            'isPrivate'     => $request->getParam('isPrivate',        null),
            'tags'          => trim($request->getParam('tags',        null)),
        );
        $postInfo = $postIn;

        if ($postIn['rating'] === null)
            unset($postInfo['rating']);

        if ($postIn['isFavorite'] === null)
            unset($postInfo['isFavorite']);

        if ($postIn['isPrivate'] === null)
            unset($postInfo['isPrivate']);

        // /*
        Connexions::log("PostController::postAction: "
                        . "postInfo [ %s ]",
                        Connexions::varExport($postInfo));
        // */


        if ($request->isPost())
        {
            // This is a POST -- attempt to create/update a bookmark

            $error = null;
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
                    Connexions::log("PostController: Got Bookmark: [ %s ]",
                                    $bookmark->debugDump());

                    $method = ($bookmark->isBacked()
                                ? 'updated'
                                : 'created');

                    $bookmark = $bookmark->save();

                    Connexions::log("PostController: %s Bookmark: [ %s ]",
                                    ucfirst($method),
                                    $bookmark->debugDump());
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
        }
        else
        {
            /* Initial presentation of posting form.
             *
             * Retrieve any existing bookmark for the given URL by the current
             * user.
             */
            if (! empty($postInfo['url']))
            {
                $bookmark = $bService->find( array(
                                                'user'   => $this->_viewer,
                                                'itemId' => $postInfo['url'],
                                             ));

                if ($bookmark !== null)
                {
                    /*
                    Connexions::log("PostController::postAction: "
                                    . "existing bookmark information [ %s ]",
                                    Connexions::varExport(
                                                    $bookmark->toArray()) );
                    // */

                    /* The user has an existing bookmark.  Fill in any data
                     * that was NOT provided directly.
                     */
                    if (empty($postIn['name']))
                        $postInfo['name'] = $bookmark->name;

                    if (empty($postIn['description']))
                        $postInfo['description'] = $bookmark->description;

                    if ($postIn['rating'] === null)
                        $postInfo['rating'] = $bookmark->rating;

                    if ($postIn['isFavorite'] === null)
                        $postInfo['isFavorite'] = $bookmark->isFavorite;

                    if ($postIn['isPrivate'] === null)
                        $postInfo['isPrivate'] = $bookmark->isPrivate;

                    if (empty($postIn['tags']))
                        $postInfo['tags'] = $bookmark->tags->__toString();
                }
            }
        }

        $this->view->headTitle('Save a Bookmark');

        $this->view->viewer   = $viewer;
        $this->view->postInfo = $postInfo;
        $this->view->bookmark = $bookmark;

        $this->_handleFormat();
    }

    /*************************************************************************
     * Protected Helpers
     *
     */

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
        Connexions::log("PostController::_handleFormat: context [ %s ]",
                        $format);

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
            Connexions::log("PostController::_handleFormat: "
                            .   "render 'index-%s'",
                            $format);

            $this->render('index-'. $format);


            Connexions::log("PostController::_handleFormat: "
                            .   "render 'index.%s' COMPLETE",
                            $format);
            break;
        }
    }
}

