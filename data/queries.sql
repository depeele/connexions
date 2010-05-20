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
       uti.userCount,
       uti.tagCount
 FROM tag AS t
     INNER JOIN (
         SELECT tagId,
                COUNT(DISTINCT itemId, userId) AS userItemCount,
                COUNT(DISTINCT itemId)         AS itemCount,
                COUNT(DISTINCT userId)         AS userCount,
                COUNT(DISTINCT tagId)          AS tagCount
                 FROM userTagItem
                 GROUP BY tagId) AS uti
     ON t.tagId=uti.tagId
 ORDER BY uti.userItemCount DESC
 LIMIT 50;

-- **************************************************************************
-- **** Sorting bookmarks by rating
-- ****
SELECT b.*,
       uti.userItemCount,
       uti.userCount,
       uti.itemCount,
       uti.tagCount,
	   i.ratingAvg
  FROM userItem AS b
    INNER JOIN (
        SELECT userTagItem.*,
               COUNT(DISTINCT userId,itemId) AS userItemCount,
               COUNT(DISTINCT userId) AS userCount,
               COUNT(DISTINCT itemId) AS itemCount,
               COUNT(DISTINCT tagId) AS tagCount
          FROM userTagItem
          WHERE (userId=1)
          GROUP BY userId, itemId) AS uti
      ON b.userId=uti.userId AND b.itemId=uti.itemId
    INNER JOIN (
        SELECT itemId,
               (CASE WHEN ratingCount > 0
                     THEN ratingSum / ratingCount
                     ELSE 0 END) as ratingAvg
          FROM item
          GROUP BY itemId) AS i
      ON b.itemId=i.itemId
  WHERE (( (b.isPrivate=0) OR (b.userId=1) ))
  ORDER BY i.ratingAvg DESC LIMIT 50;

-- 2.85 seconds
SELECT b.*,
       uti.userItemCount,
       uti.userCount,
       uti.itemCount,
       uti.tagCount,
       i.ratingAvg
  FROM userItem AS b
    INNER JOIN (
        SELECT userTagItem.*,
               COUNT(DISTINCT userId) AS userCount,
               COUNT(DISTINCT tagId) AS tagCount,
               COUNT(DISTINCT itemId) AS itemCount,
               COUNT(DISTINCT userId,itemId) AS userItemCount
          FROM userTagItem
          GROUP BY userId, itemId) AS uti
      ON b.userId=uti.userId AND b.itemId=uti.itemId
    INNER JOIN (
        SELECT item.itemId,
               (CASE WHEN ratingCount > 0
                     THEN ratingSum / ratingCount
                     ELSE 0 END) AS ratingAvg
          FROM item
          GROUP BY itemId) AS i
      ON i.itemId=b.itemId
  WHERE (( (b.isPrivate=0) OR (b.userId=1) ))
  ORDER BY i.ratingAvg DESC LIMIT 50

-- 0.85 seconds
SELECT b.*,
       uti.userItemCount,
       uti.userCount,
       uti.itemCount,
       uti.tagCount,
       uti.ratingAvg
  FROM userItem AS b
    INNER JOIN (
        SELECT utii.*,
               i.ratingAvg,
               COUNT(DISTINCT utii.userId) AS userCount,
               COUNT(DISTINCT utii.tagId) AS tagCount,
               COUNT(DISTINCT utii.itemId) AS itemCount,
               COUNT(DISTINCT utii.userId,utii.itemId) AS userItemCount
          FROM userTagItem as utii
            INNER JOIN (
                SELECT i.itemId,
                       (CASE WHEN ratingCount > 0
                             THEN ratingSum / ratingCount
                             ELSE 0 END) AS ratingAvg
                  FROM item AS i
                  GROUP BY itemId) AS i
              ON i.itemId=utii.itemId
          GROUP BY userId, itemId) AS uti
      ON b.userId=uti.userId AND b.itemId=uti.itemId
  WHERE (( (b.isPrivate=0) OR (b.userId=1) ))
  ORDER BY uti.ratingAvg DESC LIMIT 50;

-- ************************ Broken ****************
SELECT b.*,
       uti.userItemCount,
       uti.userCount,
       uti.itemCount,
       uti.tagCount
 FROM userItem AS b
    INNER JOIN (
        SELECT uti.*,
               COUNT(DISTINCT uti.userId) AS uti.userCount,
               COUNT(DISTINCT uti.tagId) AS uti.tagCount,
               COUNT(DISTINCT uti.itemId) AS uti.itemCount,
               COUNT(DISTINCT userId,itemId) AS uti.userItemCount,
               i.ratingAvg
          FROM userTagItem AS uti
            INNER JOIN (
                SELECT item.itemId,
                       (CASE WHEN i.ratingCount > 0
                             THEN i.ratingSum / i.ratingCount
                             ELSE 0 END) AS i.ratingAvg
                  FROM item) AS i
              ON i.itemId=b.itemId GROUP BY userId, itemId) AS uti
      ON b.userId=uti.userId AND b.itemId=uti.itemId
  WHERE (( (b.isPrivate=0) )) ORDER BY i.ratingAvg;

-- Broken
SELECT b.*,
       uti.userItemCount,
       uti.userCount,
       uti.itemCount,
       uti.tagCount
  FROM userItem AS b
    INNER JOIN (
        SELECT uti.*,
               COUNT(DISTINCT uti.userId) AS userCount,
               COUNT(DISTINCT uti.tagId) AS tagCount,
               COUNT(DISTINCT uti.itemId) AS itemCount,
               COUNT(DISTINCT userId,itemId) AS userItemCount, i.ratingAvg FROM userTagItem AS uti
 INNER JOIN (SELECT item.itemId, (CASE WHEN ratingCount > 0 THEN ratingSum / ratingCount ELSE 0 END) AS ratingAvg FROM item) AS i ON i.itemId=uti.itemId GROUP BY userId,
	itemId) AS uti ON b.userId=uti.userId AND b.itemId=uti.itemId WHERE (( (b.isPrivate=0) OR (b.userId=1) ))

-- WORKS
SELECT b.*,
       uti.userItemCount,
       uti.userCount,
       uti.itemCount,
       uti.tagCount
 FROM userItem AS b
    INNER JOIN (
        SELECT uti.*,
               COUNT(DISTINCT uti.userId) AS userCount,
               COUNT(DISTINCT uti.tagId) AS tagCount,
               COUNT(DISTINCT uti.itemId) AS itemCount,
               COUNT(DISTINCT uti.userId,uti.itemId) AS userItemCount,
               i.ratingAvg
          FROM userTagItem AS uti
            INNER JOIN (
                SELECT i.itemId,
                       (CASE WHEN i.ratingCount > 0
                             THEN i.ratingSum / i.ratingCount
                             ELSE 0 END) AS ratingAvg
                  FROM item AS i) AS i
              ON i.itemId=uti.itemId GROUP BY userId, itemId) AS uti
      ON b.userId=uti.userId AND b.itemId=uti.itemId
  WHERE (( (b.isPrivate=0) ))
  ORDER BY uti.ratingAvg DESC LIMIT 50;

-- **************************************************************************
-- **** Tag Queries
-- ****
-- User-related tags (0.02 seconds)
SELECT t.*,
       uti.userItemCount,
       uti.itemCount,
       uti.userCount,
       uti.tagCount
  FROM tag as t
  	INNER JOIN (
		SELECT tagId,
                COUNT(DISTINCT itemId, userId) AS userItemCount,
                COUNT(DISTINCT itemId)         AS itemCount,
                COUNT(DISTINCT userId)         AS userCount,
                COUNT(DISTINCT tagId)          AS tagCount
			FROM userTagItem
			WHERE userId IN (1, 2, 3, 4)
			GROUP BY tagId) uti
	ON t.tagId=uti.tagId
 ORDER BY uti.userItemCount DESC
 LIMIT 50;

-- Item-related tags
SELECT t.*,
       uti.userItemCount,
       uti.itemCount,
       uti.userCount,
       uti.tagCount
  FROM tag as t
  	INNER JOIN (
		SELECT tagId,
                COUNT(DISTINCT itemId, userId) AS userItemCount,
                COUNT(DISTINCT itemId)         AS itemCount,
                COUNT(DISTINCT userId)         AS userCount,
                COUNT(DISTINCT tagId)          AS tagCount
			FROM userTagItem
			WHERE itemId IN (1, 2, 3, 4)
			GROUP BY tagId) uti
	ON t.tagId=uti.tagId
 ORDER BY uti.userItemCount DESC
 LIMIT 50;

-- UserItem-related tags
SELECT t.*,
       uti.userItemCount,
       uti.itemCount,
       uti.userCount,
       uti.tagCount
  FROM tag as t
  	INNER JOIN (
		SELECT tagId,
                COUNT(DISTINCT itemId, userId) AS userItemCount,
                COUNT(DISTINCT itemId)         AS itemCount,
                COUNT(DISTINCT userId)         AS userCount,
                COUNT(DISTINCT tagId)          AS tagCount
			FROM userTagItem
			WHERE userId=1 AND itemId=1
			GROUP BY tagId) uti
	ON t.tagId=uti.tagId
 ORDER BY uti.userItemCount DESC
 LIMIT 50;

-- **************************************************************************
-- **** User Queries
-- ****
-- Tag-related users (0.01 seconds)
SELECT u.*,
       uti.userItemCount,
       uti.itemCount,
       uti.userCount,
       uti.tagCount
  FROM user as u
  	INNER JOIN (
		SELECT userId,
                COUNT(DISTINCT itemId, userId) AS userItemCount,
                COUNT(DISTINCT itemId)         AS itemCount,
                COUNT(DISTINCT userId)         AS userCount,
                COUNT(DISTINCT tagId)          AS tagCount
			FROM userTagItem
			WHERE tagId IN (1, 2, 3, 4)
			GROUP BY userId) uti
	ON u.userId=uti.userId
 ORDER BY uti.userItemCount DESC
 LIMIT 50;

-- Item-related users (0.01 seconds)
SELECT u.*,
       uti.userItemCount,
       uti.itemCount,
       uti.userCount,
       uti.tagCount
  FROM user as u
  	INNER JOIN (
		SELECT userId,
                COUNT(DISTINCT itemId, userId) AS userItemCount,
                COUNT(DISTINCT itemId)         AS itemCount,
                COUNT(DISTINCT userId)         AS userCount,
                COUNT(DISTINCT tagId)          AS tagCount
			FROM userTagItem
			WHERE itemId IN (1, 2, 3, 4)
			GROUP BY userId) uti
	ON u.userId=uti.userId
 ORDER BY uti.userItemCount DESC
 LIMIT 50;

-- UserItem-related users
SELECT u.*,
       uti.userItemCount,
       uti.itemCount,
       uti.userCount,
       uti.tagCount
  FROM user as u
  	INNER JOIN (
		SELECT userId,
                COUNT(DISTINCT itemId, userId) AS userItemCount,
                COUNT(DISTINCT itemId)         AS itemCount,
                COUNT(DISTINCT userId)         AS userCount,
                COUNT(DISTINCT tagId)          AS tagCount
			FROM userTagItem
			WHERE userId=1 OR itemId=1
			GROUP BY userId) uti
	ON u.userId=uti.userId
 ORDER BY uti.userItemCount DESC
 LIMIT 50;

-- **************************************************************************
-- **** Item Queries
-- ****
-- Item rating information
SELECT i.*,
       ui.ratingCount,
       ui.ratingSum,
       ui.userCount,
       (CASE WHEN ui.ratingCount > 0
             THEN ui.ratingSum / ui.ratingCount
             ELSE 0 END) as ratingAvg
  FROM item as i
  	INNER JOIN (
		SELECT itemId,
                SUM(CASE WHEN rating>0 THEN 1      ELSE 0 END) AS ratingCount,
                SUM(CASE WHEN rating>0 THEN rating ELSE 0 END) AS ratingSum,
                COUNT(DISTINCT userId)                         AS userCount
			FROM userItem
			GROUP BY itemId) ui
	ON i.itemId=ui.itemId
  ORDER BY ui.ratingCount DESC
  LIMIT 50;

-- User-related items (0.02 seconds)
SELECT i.*,
       uti.userItemCount,
       uti.itemCount,
       uti.userCount,
       uti.tagCount
  FROM item as i
  	INNER JOIN (
		SELECT itemId,
                COUNT(DISTINCT itemId, userId) AS userItemCount,
                COUNT(DISTINCT itemId)         AS itemCount,
                COUNT(DISTINCT userId)         AS userCount,
                COUNT(DISTINCT tagId)          AS tagCount
			FROM userTagItem
			WHERE userId IN (1, 2, 3, 4)
			GROUP BY itemId) uti
	ON i.itemId=uti.itemId
 ORDER BY uti.userItemCount DESC
 LIMIT 50;

-- Tag-related items
SELECT i.*,
       uti.userItemCount,
       uti.itemCount,
       uti.userCount,
       uti.tagCount
  FROM item as i
  	INNER JOIN (
		SELECT itemId,
                COUNT(DISTINCT itemId, userId) AS userItemCount,
                COUNT(DISTINCT itemId)         AS itemCount,
                COUNT(DISTINCT userId)         AS userCount,
                COUNT(DISTINCT tagId)          AS tagCount
			FROM userTagItem
			WHERE tagId IN (1, 2, 3, 4)
			GROUP BY itemId) uti
	ON i.itemId=uti.itemId
 ORDER BY uti.userItemCount DESC
 LIMIT 50;

-- UserItem-related items
SELECT i.*,
       uti.userItemCount,
       uti.itemCount,
       uti.userCount,
       uti.tagCount
  FROM item as i
  	INNER JOIN (
		SELECT itemId,
                COUNT(DISTINCT itemId, userId) AS userItemCount,
                COUNT(DISTINCT itemId)         AS itemCount,
                COUNT(DISTINCT userId)         AS userCount,
                COUNT(DISTINCT tagId)          AS tagCount
			FROM userTagItem
			WHERE userId=1 OR itemId=1
			GROUP BY itemId) uti
	ON i.itemId=uti.itemId
 ORDER BY uti.userItemCount DESC
 LIMIT 50;

-- **************************************************************************
-- **** UserItem Queries
-- ****
-- Tag-related UserItems
SELECT ui.*,
       uti.userItemCount,
       uti.itemCount,
       uti.userCount,
       uti.tagCount
  FROM userItem as ui
  	INNER JOIN (
		SELECT userId,itemId,
                COUNT(DISTINCT itemId, userId) AS userItemCount,
                COUNT(DISTINCT itemId)         AS itemCount,
                COUNT(DISTINCT userId)         AS userCount,
                COUNT(DISTINCT tagId)          AS tagCount
			FROM userTagItem
			WHERE tagId IN (1, 2, 3, 4)
			GROUP BY userId,itemId) uti
	ON ui.userId=uti.userId AND ui.itemId=uti.itemId
 ORDER BY uti.userItemCount DESC
 LIMIT 50;

-- Ordered by item-userCount
SELECT ui.*,
       uti.userItemCount,
       uti.itemCount,
       uti.userCount,
       uti.tagCount,
       i.ratingCount,
       i.ratingSum,
       i.userCount,
       (CASE WHEN i.ratingCount > 0
             THEN i.ratingSum / i.ratingCount
             ELSE 0 END) as ratingAvg
  FROM userItem as ui
  	INNER JOIN (
		SELECT userId,itemId,
                COUNT(DISTINCT itemId, userId) AS userItemCount,
                COUNT(DISTINCT itemId)         AS itemCount,
                COUNT(DISTINCT userId)         AS userCount,
                COUNT(DISTINCT tagId)          AS tagCount
			FROM userTagItem
			GROUP BY userId,itemId) uti
	ON ui.userId=uti.userId AND ui.itemId=uti.itemId
  	INNER JOIN (
		SELECT itemId,
                SUM(CASE WHEN rating>0 THEN 1      ELSE 0 END) AS ratingCount,
                SUM(CASE WHEN rating>0 THEN rating ELSE 0 END) AS ratingSum,
                COUNT(DISTINCT userId)                         AS userCount
			FROM userItem
			GROUP BY itemId) i
	ON ui.itemId=i.itemId
 ORDER BY i.userCount DESC
 LIMIT 50;

-- Ordered by item-userCount, with tags 1, 2, 3, or 4
SELECT ui.*,
       uti.userItemCount,
       uti.itemCount,
       uti.userCount,
       uti.tagCount,
       i.ratingCount,
       i.ratingSum,
       i.userCount,
       (CASE WHEN i.ratingCount > 0
             THEN i.ratingSum / i.ratingCount
             ELSE 0 END) as ratingAvg
  FROM userItem as ui
  	INNER JOIN (
		SELECT userId,itemId,
                COUNT(DISTINCT itemId, userId) AS userItemCount,
                COUNT(DISTINCT itemId)         AS itemCount,
                COUNT(DISTINCT userId)         AS userCount,
                COUNT(DISTINCT tagId)          AS tagCount
			FROM userTagItem
			WHERE tagId IN (1, 2, 3, 4)
			GROUP BY userId,itemId) uti
	ON ui.userId=uti.userId AND ui.itemId=uti.itemId
  	INNER JOIN (
		SELECT itemId,
                SUM(CASE WHEN rating>0 THEN 1      ELSE 0 END) AS ratingCount,
                SUM(CASE WHEN rating>0 THEN rating ELSE 0 END) AS ratingSum,
                COUNT(DISTINCT userId)                         AS userCount
			FROM userItem
			GROUP BY itemId) i
	ON ui.itemId=i.itemId
 ORDER BY i.userCount DESC
 LIMIT 50;

-- **************************************************************************
