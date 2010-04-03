-- Runs in 0.97 seconds
SELECT t.*,
       COUNT(DISTINCT uti.itemid,uti.userId) AS userItemCount,
       COUNT(DISTINCT uti.itemId) AS itemCount,
       COUNT(DISTINCT uti.userId) AS userCount
 FROM tag AS t
    LEFT JOIN userTagItem AS uti
    ON t.tagId=uti.tagId
 GROUP BY `t`.`tagId`
 ORDER BY userItemCount DESC
 LIMIT 50;

-- Runs in 0.15 seconds with same result set as above
SELECT t.*,
       uti.userItemCount,
       uti.itemCount,
       uti.userCount
 FROM tag AS t
     INNER JOIN (
         SELECT tagId,
                COUNT(DISTINCT itemId, userId) AS userItemCount,
                COUNT(DISTINCT itemId) AS itemCount,
                COUNT(DISTINCT userId) AS userCount
                 FROM userTagItem
                 GROUP BY tagId) uti
     ON t.tagId=uti.tagId
 ORDER BY uti.userItemCount DESC
 LIMIT 50;

