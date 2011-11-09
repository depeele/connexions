<?php
/** @file
 *
 *  Shared class for help rendering.
 *
 */
class Connexions_Help
{
    /** @brief  Determine whether or not a section should be collapsed.
     *  @param  view                The view object (uses $view->section).
     *  @param  val                 The target section.
     *  @param  defaultCollapsed    Should the default be 'collapsed' (true)?
     *
     *  @return A string ' collapsed' or ''
     */
    static public function section_collapsed($view, $val,
                                             $defaultCollapsed = false)
    {
        $ret = ($defaultCollapsed ? ' collapsed' : '');

        if (! empty($view->section))
        {
            // A specific section has been requested...
            if (strcasecmp($view->section, $val))
            {
                // But NOT this one.
                $ret = ' collapsed';
            }
            else
            {
                // And it IS this one.
                $ret = '';
            }
        }

        /*
        Connexions::log("Connexions_Help::section_collapsed(): "
                        .   "section[ %s ], val[ %s ], ret[ %s ]",
                        Connexions::varExport($view->section),
                        $val, $ret);
        // */

        return $ret;
    }

    /** @brief  Determine whether or not a sub-section should be collapsed.
     *  @param  view    The view object (uses $view->rest).
     *  @param  val     The path to the target section.
     *
     *  @return A string ' collapsed' or ''
     */
    static public function rest_collapsed($view, $val)
    {
        $ret = ' collapsed';
        if ($view->rest !== null)
        {
            $path   = explode('/', $val);
            $nParts = count($path);
            if (count($view->rest) >= $nParts)
            {
                /*
                Connexions::log("Connexions_Help::rest_collapsed(): "
                                .   "rest[ %s ], path[ %s ]",
                                Connexions::varExport($view->rest),
                                Connexions::varExport($path));
                // */
    
                $ret = '';
                for ($idex = 0; $idex < $nParts; $idex++)
                {
                    if (strcasecmp($view->rest[$idex], $path[$idex]))
                    {
                        $ret = ' collapsed';
                        break;
                    }
                }
            }
        }
    
        /*
        Connexions::log("Connexions_Help::rest_collapsed(): "
                        .   "rest[ %s ], val[ %s ], ret[ %s ]",
                        Connexions::varExport($view->rest),
                        $val, $ret);
        // */
    
        return $ret;
    }
}
