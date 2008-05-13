<?php

$gUseGlobalLoading  = true;     // Use the global 'loading' indicator?
$gUseThumbnails     = false;    // Show thumbnails if they are available?
$gUserPhotos        = true;     // Use user photos?
$gJsDebug           = true;     // Show the JavaScript debug console
$gProfile           = true;     // Record profiling information?

$gBaseUrl           = '/connexions';
$db_options         = array(
                        'debug'         => false,
                        'db_user'       => 'connexions',
                        'db_pass'       => '',
                        'db_host'       => 'localhost',
                        'db_name'       => 'connexions',
                        'table_prefix'  => '',
                        'noexec'        => false,
                      );

/** @brief  Define the routes that will be used by the plugin director.
 *
 *  url => array(className, methodName)
 *
 */
$gRoutes            = array(
    '/'                         => array('Main', 'view'),

    '/$user/watchlist'          => array('Watchlist', 'view'),
    '/$user/watchlist/$tags'    => array('Watchlist', 'view'),

    '/$user/$tags'              => array('Main', 'view'),
    '/$user'                    => array('Main', 'view'),
    '/$user'                    => array('Main', 'view'),
    '/tag/$tags'                => array('Main', 'viewTags'),


    '/details'                  => array('Main', 'details'),
    '/details/$url'             => array('Main', 'details'),

    '/people'                   => array('Main', 'people'),

    /*'/watchlist/$user/$tags'      => array('Watchlist', 'view'),
    '/watchlist/$user'            => array('Watchlist', 'view'),*/
    '/watchlist/$tags'            => array('Watchlist', 'viewTags'),
    '/watchlist'                  => array('Watchlist', 'view'),

    '/settings/bookmarks/$cmd'  => array('Settings', 'bookmarks'),
    '/settings/$type/$cmd'      => array('Settings', 'main'),
    '/settings/$type'           => array('Settings', 'main'),
    '/settings'                 => array('Settings', 'main'),

    '/help/$topic'              => array('Help', 'main'),
    '/help/$topic/$subTopic'    => array('Help', 'main'),
    '/help'                     => array('Help', 'main'),

    '/feeds/$type/$cmd/$params' => array('Feeds', 'main'),
    '/feeds/$type/$cmd'         => array('Feeds', 'main'),
    '/feeds'                    => array('Feeds', 'main'),

    '/for'                      => array('Main',  'linksFor'),
    '/post'                     => array('Main',  'post'),
    '/post/$params'             => array('Main',  'post'),

    '/$area'                    => array('Main',    'nyi'),
                      );

/** @brief  Define the available menu items that will be presented on the
 *          left and/or right of the page header (Tagging::pageHeader()).
 *
 *  There are 2 major sections -- left and right.  Each of these is divided
 *  into 3 major sections.  One for authenticated users, one for
 *  unauthenticated users, and one that applies to everyone.
 *
 *  Each section is an associative array where the 'key' is the name of the
 *  area and the value is the URL pattern for that area.  The URL pattern may
 *  contain one or more special markers that will be replaced before
 *  presentation:
 *      - %base_url%    : the site's base URL
 *      - %user_name%   : the name of the current user
 */
$gPageMenu  = array(
    'left'  => array('auth'  => array(   // Authenticated users
                            'your posts'    => '%base_url%/%user_name%',
                            'watchlist'     => '%base_url%/watchlist',
                            'links for you%count%' => '%base_url%/for',
                            'post'          => '%base_url%/post',
                                    ),
                     'unauth'=> array(   // Unauthenticated  users
                                    ),
                     'all'   => array(   // All users
                                    ),
                    ),
    'right' => array('auth'  => array(   // Authenticated users
                            'settings'  => '%base_url%/settings/',
                            //'logout'    => '%base_url%/logout',
                                    ),
                     'unauth'=> array(   // Unauthenticated  users
                            //'login'     => '%base_url%/login',
                            //'register'  => '%base_url%/register',
                                    ),
                     'all'   => array(   // All users
                            'people'    => '%base_url%/people/',
                            'help'      => '%base_url%/help/',
                                    ),
                    )
    );

?>
