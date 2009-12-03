<?php
/** @file
 *
 *  The Connexions singleton.  Provided general functionality.
 */

class Connexions
{
    public static   $curUser    = null;

    public static function curUser()
    {
        if (self::$curUser === null)
        {
            try
            {
                self::$curUser = Zend_Registry::get('user');
            }
            catch (Zend_Exception $e)
            {
                self::$curUser = false;
            }
        }

        return self::$curUser;
    }

    public static function curRequest()
    {
        return Zend_Controller_Front::getInstance()->getRequest();
    }

    /** @brief  Given a site URL, apply any 'base' url prefix and return.
     *  @param  url     The site URL.
     *
     *  @return The full site URL with any 'base' prefix.
     */
    public static function url($url)
    {
        $front  =& Zend_Controller_Front::getInstance();

        if (@is_string($url))
        {
            if ($url[0] == '/')
            {
                // Convert to a site-absolute URL
                $url = $front->getBaseUrl() . $url;
            }

        }
        else if (@is_array($url))
        {
            $router =& $front->getRouter();
            $url    =  $router->assemble(array(), $url);
        }

        
        return $url;
    }

    /** @brief  Given a URL and name, return the HTML of a valid anchor.
     *  @param  url         The site URL.
     *  @param  name        The anchor name/title.
     *  @param  cssClass    A CSS class string (or array of class strings).
     *
     *  @return The HTML of an anchor with a full site URL including any 'base'
     *          prefix.
     */
    public static function anchor($url, $name, $cssClass = null)
    {
        if (@is_array($cssClass))
            $cssClass = implode(' ', $cssClass);

        return sprintf ("<a href='%s'%s>%s</a>",
                        self::url($url),
                        (! @empty($cssClass)
                            ? "class='". $cssClass ."'"
                            : ''),
                        $name);
    }
}
