<?php
/** @file
 *
 *  View helper to render database profiling information in HTML.
 */
class Connexions_View_Helper_HtmlDbProfile extends Zend_View_Helper_Abstract
{
    /** @brief  Render an HTML version of database profile information.
     *
     *  @return The HTML representation of the database profile.
     */
    public function htmlDbProfile()
    {
        $db       = Zend_Registry::get('db');
        $profiler = $db->getProfiler();

        if ((! $profiler instanceof Zend_Db_Profiler) ||
            ($profiler->getEnabled() !== true) )
        {
            //return ("Profiler disabled");
            return ('');
        }

        $totalTime    = $profiler->getTotalElapsedSecs();
        $totalQueries = $profiler->getTotalNumQueries();
        $longest      = null;

        $profiles = $profiler->getQueryProfiles();
        $times    = array();
        $queries  = array();
        foreach ($profiles as $query)
        {
            $time = $query->getElapsedSecs();

            array_push($queries, array('time'   => $time,
                                       'query'  => $query));
            array_push($times, $time);
        }

        array_multisort($times, SORT_DESC, $queries);

        $topCnt = min($totalQueries, 5);

        $html =  "<div class='db-profile'>"
              .   "<h3>Database Profile</h3>"
              .   "<ul>"
              .    sprintf ("<li>Executed %d queries in %f seconds, "
                            .    "average %f seconds/query, "
                            .    "%f queries/second</li>",
                            $totalQueries, $totalTime,
                            $totalTime    / $totalQueries,
                            $totalQueries / $totalTime)
              .    sprintf ("<li>Longest %d queries:<dl>",  $topCnt);
        
        for ($idex = 0; $idex < $topCnt; $idex++)
        {
            $query =& $queries[$idex]['query'];
            $html .= sprintf (  "<dt>%d: %10f seconds</dt>"
                              . "<dd>%s</dd>",
                              $idex + 1,
                              $queries[$idex]['time'],
                              $query->getQuery());

        }
        $html .=    "</dl>"
              .    "</li>"
              .   "</ul>"
              .  "</div>";
        
        return $html;
    }
}
