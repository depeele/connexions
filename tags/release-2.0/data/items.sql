SELECT t1.object_id,t1.tagger_id, t2.url,t1.name,
       t1.description,t1.tagged_on, t1.rating,t1.is_favorite,t1.is_private,
       (SELECT count(DISTINCT tagger_id) FROM object_details
            WHERE object_id=t1.object_id) AS taggers,
       (SELECT COUNT(*) FROM object_details
            WHERE object_id=t1.object_id AND rating>0) AS votes,
       (SELECT AVG(rating) FROM object_details
            WHERE object_id=t1.object_id AND rating>0) AS avgRating
       FROM object_details AS t1
            LEFT JOIN objects AS t2
                ON (t1.object_id = t2.id)
                HAVING t1.tagger_id = 1
       ORDER BY tagged_on DESC LIMIT 0, 20


SELECT t1.object_id,t1.tagger_id, t2.url,t1.name,
       t1.description,t1.tagged_on, t1.rating,t1.is_favorite,t1.is_private
       FROM object_details AS t1
            LEFT JOIN objects AS t2
                ON (t1.object_id = t2.id)
                HAVING t1.tagger_id = 1
       ORDER BY tagged_on DESC;

/* Retrieve the details of all objects for tagger #1 */
SELECT t1.object_id,t1.tagger_id, t2.url,t1.name,
       t1.description,t1.tagged_on, t1.rating,t1.is_favorite,t1.is_private
       FROM object_details AS t1
            LEFT JOIN objects AS t2
                ON (t1.object_id = t2.id)
       WHERE t1.tagger_id = 1
       ORDER BY tagged_on DESC;

/* Retrieve the statistics for object #83. */
SELECT taggers, votes, avgRating
        FROM (SELECT COUNT(*) as votes, AVG(rating) as avgRating
                FROM object_details
                WHERE object_id = 83 AND rating > 0) AS t1
            INNER JOIN (SELECT COUNT(*) AS taggers
                            FROM object_details
                            WHERE object_id = 83) AS t2;

/* Number of taggers */
SELECT COUNT(*) AS taggers
        FROM object_details
        WHERE object_id = 83;

/* Number of votes and average rating */
SELECT COUNT(*) as votes, AVG(rating) as avgRating
        FROM object_details
        WHERE object_id = 83 AND rating > 0;


SELECT COUNT(DISTINCT objSet.tagger_id) AS taggers
    FROM (SELECT object_id,tagger_id,rating
            FROM object_details
            WHERE object_id = 83) AS objSet;


SELECT taggers, votes, avgRating
        FROM (SELECT COUNT(*) as votes, AVG(rating) as avgRating
                FROM object_details
                WHERE object_id = 163 AND rating > 0) AS t1
            INNER JOIN (SELECT COUNT(*) AS taggers
                            FROM object_details
                            WHERE object_id = 163) AS t2;




/* All object details for a given tagger */
SELECT t1.object_id,t1.tagger_id,
       t2.url,t1.name,t1.description,t1.tagged_on,
       t1.rating,t1.is_favorite,t1.is_private,
       (SELECT count(DISTINCT tagger_id)
            FROM object_details
            WHERE object_id=t1.object_id) as taggers, 
       (SELECT COUNT(*) AS votes
          FROM object_details
          WHERE object_id=t1.object_id AND rating>0),
       (SELECT AVG(rating)
          FROM object_details
          WHERE object_id=t1.object_id AND rating>0)
   FROM object_details AS t1
        LEFT JOIN objects AS t2
            ON (t1.object_id = t2.id)
   WHERE 1 AND t1.tagger_id = 1
   ORDER BY taggers DESC
   LIMIT 0, 20;


SELECT  t1.object_id,t1.tagger_id,
        t2.url,t1.name,t1.description,t1.tagged_on,
        t1.rating,t1.is_favorite,t1.is_private,
        COUNT(DISTINCT t1.tagger_id) AS taggers,
        COUNT(t3.rating) AS votes,
        AVG(t3.rating) AS avgRating
   FROM object_details AS t1
       INNER JOIN objects AS t2
            ON (t1.object_id = t2.id)
       INNER JOIN object_details AS t3
            ON (t1.object_id = t3.object_id AND t3.rating>0)
   WHERE 1 AND t1.tagger_id = 1
   GROUP BY t1.object_id
   ORDER BY taggers DESC
   LIMIT 0, 20;





SELECT  t1.object_id,
        t2.url,
        t1.rating,
        COUNT(DISTINCT t1.tagger_id) AS taggers,
        COUNT(t3.rating) AS votes,
        AVG(t3.rating) AS avgRating
   FROM object_details AS t1
       INNER JOIN objects AS t2
            ON (t1.object_id = t2.id)
       LEFT JOIN object_details AS t3
            ON (t1.object_id = t3.object_id AND
                t1.tagger_id = t3.tagger_id AND
                t3.rating    > 0)
   WHERE 1 AND t1.tagger_id = 1
   GROUP BY t1.object_id
   ORDER BY taggers DESC
   LIMIT 0, 20;

SELECT t1.object_id,
       t2.url,
       t1.rating,
       (SELECT count(DISTINCT tagger_id)
            FROM object_details
            WHERE object_id=t1.object_id) as taggers, 
       (SELECT COUNT(*)
          FROM object_details
          WHERE object_id=t1.object_id AND rating>0) AS votes,
       (SELECT AVG(rating)
          FROM object_details
          WHERE object_id=t1.object_id AND rating>0) AS avgRating
   FROM object_details AS t1
        LEFT JOIN objects AS t2
            ON (t1.object_id = t2.id)
   WHERE 1 AND t1.tagger_id = 1
   ORDER BY taggers DESC
   LIMIT 0, 20;





SELECT  t1.object_id,t1.tagger_id,t2.url,t1.rating,t3.rating,
        (SELECT tagger_id,rating
            FROM object_details
            WHERE object_id=t1.object_id)
   FROM object_details AS t1
       INNER JOIN objects AS t2
            ON (t1.object_id = t2.id)
   WHERE 1 AND t1.tagger_id = 1
   ORDER BY t1.rating DESC
   LIMIT 0, 20;

;-----------------------------------------------------------------------------
/*
 * Retrieve the identifiers of all objects that are tagged with
 * BOTH 'xml' and 'ajax'
 */
SELECT freetagged_objects.object_id, tag, COUNT(DISTINCT tag) AS uniques
        FROM freetagged_objects 
            INNER JOIN freetags
                ON (freetagged_objects.tag_id = freetags.id)
        WHERE freetags.tag IN ('xml', 'ajax') AND tagger_id = 1
        GROUP BY freetagged_objects.object_id
        HAVING uniques = 2
        LIMIT 0,100;

/* Cannot limit to a single user. */
SELECT it.itemid FROM itemtag it, tag t
        WHERE (it.tagid = t.tagid) AND
            t.tag IN ('xml','ajax')
        GROUP BY it.itemid HAVING COUNT(DISTINCT t.tag) = 2;

/* Inefficient if userid is unrestricted */
SELECT uti.itemid FROM usertagitem uti, tag t
        WHERE (uti.tagid = t.tagid) AND
            t.tag IN ('xml','ajax') AND userid = 1
        GROUP BY uti.itemid HAVING COUNT(DISTINCT t.tag) = 2;


SELECT u.*, i.*, ui.* FROM user u, item i, useritem ui, usertagitem uti
	WHERE (u.userid IN (1, 2, 3, 4))	-- depeele, sabusbe, jgechev, cgwagne
	  AND (u.userid = ui.userid)
	  AND (u.userid = uti.userid)
	  AND (i.itemid = ui.itemid)
	  AND (i.itemid = uti.itemid)
	  AND (uti.tagid IN (590, 725, 884))	-- ajax, javascript, prototype
	  	GROUP BY u.userid,i.itemid
			HAVING (COUNT(DISTINCT i.itemid) = 3);
