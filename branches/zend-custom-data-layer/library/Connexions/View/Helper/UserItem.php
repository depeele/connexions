<?php
/** @file
 *
 *  View helper used as a super-class for those that render a single
 *  User Item / Bookmark.
 *
 *  Primarily offers descrption summarization.
 *
 */
class Connexions_View_Helper_UserItem extends Zend_View_Helper_Abstract
{
    /** @brief  The maximum number of characters to include in a summary,
     *          particularly a summary of a description.
     */
    public static   $summaryMax = 40;


    /** @brief  Generate a "summary" of provided text.  This simply shortens
     *          the text to the last full word before the 'summaryMax'th
     *          character.
     *  @param  text        The text to "summarieze".
     *
     *  @return The summary string.
     */
    public function getSummary($text)
    {
        $summary = html_entity_decode($text, ENT_QUOTES);
        if (strlen($summary) > self::$summaryMax)
        {
            // Shorten to no more than 'summaryMax' characters
            $summary = substr($summary, 0, self::$summaryMax);
            $summary = substr($summary, 0, strrpos($summary, " "));

            // Trim any white-space or punctuation from the end
            $summary = rtrim($summary, " \t\n\r.!?:;,-");

            $summary .= '...';
        }
        $summary = htmlentities($summary, ENT_QUOTES);

        return $summary;
    }
}
