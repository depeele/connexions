<?php
/** @file
 *
 *  Generate a graphical timeline of actions.
 */

/** @brief  Compute the average of the 'timestamp' values of the array.
 */
function tlAverage(&$array)
{
   $count = count($array);
   if ($count < 1)
       return 0;

   $sum   = array_sum($array);
   return $sum/$count;
}

/** @brief  Compute the variance of the values of an array.
 *  @param  array   The array of values.
 *
 *  @return An array of variance values.
 */
function tlVariance(&$array)
{
   $variance = array();
   $avg      = tlAverage($array);
   foreach ($array as $value)
   {
       $variance[] = pow($value-$avg, 2);
   }
   return ($variance);
}

/** @brief  Compute the standard deviation of the values of an array.
 *  @param  array   The array of values.
 *
 *  @return The standard deviation.
 */
function tlDeviation (&$array)
{
    $variance  = tlVariance($array);
    $deviation = sqrt(tlAverage($variance));
    return $deviation;
}

/** @brief  Generate HTML to present a timeline based upon the provided records.
 *  @param  ary records     The records to use.
 *
 *  'records' should be sorted in time order (ascending or descending) and each
 *  record should be an associative array with at least a 'timestamp' value.
 *
 *  @return HTML representing the timeline.
 */
function timeline(&$records)
{
    $count = count($records);

    // Extract the timestamp fields into a separate array that we can sort.
    $timestamps = array();
    for ($idex = 0; $idex < $count; $idex++)
    {
        $timestamp = strtotime($records[$idex]['tagged_on']);

        $records[$idex]['timestamp'] = $timestamp;
        $timestamps[$idex] = $timestamp;
    }

    // First, figure out the time range.
    $min   = $timestamps[0];
    $max   = $timestamps[$count - 1];
    if ($min > $max)
    {
        $tmp   = $min;
        $min   = $max;
        $max   = $tmp;

        // Sort the timestamps in the reverse order (ascending)
        sort($timestamps, SORT_NUMERIC);
    }

    $range = $max - $min;

    /* Figure out what sort of scale to use based upon the standard deviation
     * of the timestamps:
     *  year(365 days)  31536000 <= dev
     *  month(30 days)   2592000 <= dev < 31536000
     *  week              604800 <= dev <  2592000
     *  day                86400 <= dev <   604800
     *  hour                3600 <= dev <    86400
     */
    $dev = tlDeviation($timestamps);
    if      ($dev >= 31536000)  $scale = array('year',  '%Y',       31536000);
    else if ($dev >=  2592000)  $scale = array('month', '%Y.%m',     2592000);
    else if ($dev >=    86400)  $scale = array('day',   '%Y.%m.%d',    86400);
    else if ($dev >=     3600)  $scale = array('hour',  '%Y.%m.%d.%H',  3600);
    else                        $scale = array('month', '%Y.%m',     2592000);

    /*printf ("min[%u], max[%u]: range[%u], dev[%f], scale[%s, %u]<br />\n",
                      $min, $max,
                      $range, $dev,
                      $scale[0], $scale[2]);*/


    $buckets = array();
    $bucket  = -1;
    $upper   = array('ts'=>0,'year'=>0,'month'=>0,'day'=>0,'hour'=>0);
    for ($idex = 0; $idex < $count; $idex++)
    {
        $curTs  = $timestamps[$idex];

        while ($curTs >= $upper['ts'])
        {
            // New bucket
            /*if ($bucket >= 0)
                printf("bucket#%d: %u<br />\n", $bucket, $buckets[$bucket]);
            printf("new_bucket#%d: %u [%u (%s)] %u<br />\n",
                   $bucket+1, $lower['ts'],
                   $curTs, strftime("%Y.%m.%d.%H", $curTs), $upper['ts']);*/

            // Remember the current upper bound as a new lower bound
            if ($upper['ts'] > 0)
            {
                $lower = $upper;
            }
            else
            {
                $date = getdate($curTs);

                switch ($scale[0])
                {
                case 'year':
                    $lower['year']  = $date['year'];
                    $lower['month'] = 1;
                    $lower['day']   = 1;
                    $lower['hour']  = 0;
                    break;
                case 'month':
                    $lower['year']  = $date['year'];
                    $lower['month'] = $date['mon'];
                    $lower['day']   = 1;
                    $lower['hour']  = 0;
                    break;
                case 'day':
                    $lower['year']  = $date['year'];
                    $lower['month'] = $date['mon'];
                    $lower['day']   = $date['mday'];
                    $lower['hour']  = 0;
                    break;
                case 'hour':
                    $lower['year']  = $date['year'];
                    $lower['month'] = $date['mon'];
                    $lower['day']   = $date['mday'];
                    $lower['hour']  = $date['hours'];
                    break;
                }

                $lower['ts'] = mktime($lower['hour'], 0, 0,
                                      $lower['month'],
                                      $lower['day'],
                                      $lower['year']);

                /*printf ("New bucket lowerTs[%u] (%s)<br />\n",
                          $lower['ts'], strftime("%Y.%m.%d.%H",$lower['ts']));*/
            }

            // Compute the new upper bound based upon the new lower bound
            // and the current scale
            $upper = $lower;
            $upper[$scale[0]]++;
            $upper['ts'] = mktime($upper['hour'], 0, 0,
                                  $upper['month'],
                                  $upper['day'],
                                  $upper['year']);


            /*printf ("New bucket lowerTs[%u], upperTs[%u]<br />\n",
                      $lower['ts'], $upper['ts']);*/
            $bucket++;
            $buckets[$bucket] = 0;

            if ($bucket == 0)
                $startTime = $lower;
        }

        /*printf("%u < %u (%s) < %u<br />\n",
               $lower['ts'],$curTs,strftime("%Y.%m.%d",$curTs),$upper['ts']);*/

        $buckets[$bucket]++;
    }

    $minCnt   = min($buckets);
    $maxCnt   = max($buckets);
    $imgScale = 20 / $maxCnt;

    //printf("%u buckets: %u .. %u<br />\n", count($buckets),$minCnt,$maxCnt);


    $labelHtml = '';
    $valueHtml = '';
    $dispTime  = $startTime;
    for ($idex = 0; $idex < count($buckets); $idex++)
    {
        $minTs = $dispTime['ts'];

        $label = '';
        switch ($scale[0])
        {
        case 'year':
            $label  = "<span style='font-size:.8em;'>".
                            strftime("%Y", $minTs);
                      "</span>";
            break;

        case 'month':
            // Have we crossed a year boundary?
            $curYear  = strftime("%Y", $minTs);
            if ($prevYear != $curYear)
                $context = $curYear;
            else
                $context = '&nbsp;';

            $label  = "<span style='font-size:.75em;'>{$context}</span><br />".
                      "<span style='font-size:.8em;'>".
                            strftime("%b", $minTs).
                      "</span>";
            $prevYear = $curYear;
            break;

        case 'day':
            // Have we crossed a month or year boundary?
            $curYear  = strftime("%Y", $minTs);
            $curMonth = strftime("%b", $minTs);
            if ($prevYear != $curYear)
                $context .= "$curYear&nbsp;$curMonth";
            else if ($prevMonth != $curMonth)
                $context .= $curMonth;
            else
                $context = '&nbsp;';

            $label  = "<span style='font-size:.75em;'>{$context}</span><br />".
                      "<span style='font-size:.8em;'>".
                         strftime("%d", $minTs) .
                      "</span>";

            $prevYear  = $curYear;
            $prevMonth = $curMonth;
            break;

        case 'hour':
            // Have we crossed a day, month or year boundary?
            $curYear  = strftime("%Y", $minTs);
            $curMonth = strftime("%b", $minTs);
            $curDay   = strftime("%d", $minTs);
            if ($prevYear != $curYear)
                $context .= "$curYear&nbsp;$curMonth,&nbsp;$curDay";
            else if ($prevMonth != $curMonth)
                $context .= "$curMonth,&nbsp;$curDay";
            else if ($prevDay != $curDay)
                $context .= "$curDay";
            else
                $context = '&nbsp;';

            $label  = "<span style='font-size:.75em;'>{$context}</span><br />".
                      "<span style='font-size:.8em;'>".
                         strftime("%H:&nbsp;", $minTs) .
                      "</span>";

            $prevYear  = $curYear;
            $prevMonth = $curMonth;
            $prevDay   = $curDay;
            break;
        }
        $dispTime[$scale[0]]++;
        $dispTime['ts'] = mktime($dispTime['hour'], 0, 0,
                                 $dispTime['month'],
                                 $dispTime['day'],
                                 $dispTime['year']);

        $height = $buckets[$idex] * $imgScale;

        $labelHtml .= "<td style='vertical-align:top;text-align:center;font-size:0.9em;border-bottom:1px solid #ddd;'>$label</td>";
        $valueHtml .= "<td style='vertical-align:top;text-align:center;font-size:0.9em;'>";
        
        if ($buckets[$idex] > 0)
        {
            $valueHtml .= "<div style='background-color:#9f9;height:{$height}px;font-size:1px;border:1px solid #ddd;'>&nbsp;</div><span style='font-size:0.75em;'>{$buckets[$idex]}</span>";
        }
        else
        {
            $valueHtml .= "&nbsp;";
        }
        
        $valueHtml .= "</td>";
    }

    $html = "
<div style='margin:1em; padding:1em; background-color: #eee;'>
 <table border='0' cellpadding='0' cellspacing='0' width='100%'>
  <tr>{$labelHtml}</tr>
  <tr>{$valueHtml}</tr>
 </table>
</div>";

    return ($html);
}
?>
