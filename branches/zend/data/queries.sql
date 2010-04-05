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
                 GROUP BY tagId) uti
     ON t.tagId=uti.tagId
 ORDER BY uti.userItemCount DESC
 LIMIT 50;

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
