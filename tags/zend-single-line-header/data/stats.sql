-- Averages per user (0.51 sec)
 SELECT
    MIN(`uti`.`itemCount`) AS `minItems`,
    AVG(`uti`.`itemCount`) AS `items`,
    MAX(`uti`.`itemCount`) AS `maxItems`,
    STDDEV(`uti`.`itemCount`) AS `sdItems`,

    MIN(`uti`.`userItemCount`) AS `minBookmarks`,
    AVG(`uti`.`userItemCount`) AS `bookmarks`,
    MAX(`uti`.`userItemCount`) AS `maxBookmarks`,
    STDDEV(`uti`.`userItemCount`) AS `sdBookmarks`,

    MIN(`uti`.`tagCount`) AS `minTags`,
    AVG(`uti`.`tagCount`) AS `tags`,
    MAX(`uti`.`tagCount`) AS `maxTags`,
    STDDEV(`uti`.`tagCount`) AS `sdTags`,

    (SUM(CASE WHEN b.isPrivate > 0 THEN 1 ELSE 0 END) /
        COUNT(DISTINCT uti.userId)) AS `privates`,

    (SUM(CASE WHEN b.isPrivate > 0 THEN 0 ELSE 1 END) /
       COUNT(DISTINCT uti.userId)) AS `publics`,

    (SUM( b.isFavorite ) /
       COUNT(DISTINCT uti.userId)) AS `favorites`,

    (SUM(CASE WHEN b.rating > 0 THEN 1 ELSE 0 END) /
       COUNT(DISTINCT uti.userId)) AS `rated`,

    (SUM(b.rating) /
       SUM(CASE WHEN b.rating > 0 THEN 1 ELSE 0 END)) AS `rating`
  FROM `userItem` AS `b`
  INNER JOIN (
    SELECT
      `uti`.userId,
      COUNT(DISTINCT uti.tagId) AS `tagCount`,
      COUNT(DISTINCT uti.itemId) AS `itemCount`,
      COUNT(DISTINCT uti.userId,uti.itemId) AS `userItemCount`
    FROM `userTagItem` AS `uti`
    GROUP BY uti.userId) AS `uti`
  ON b.userId=uti.userId;


-- Consolidated Averages (2.36 sec)
 SELECT
    COUNT(DISTINCT utiu.userId) AS users,
    COUNT(DISTINCT utib.itemId) AS items,
    COUNT(DISTINCT utib.tagId) AS tags,
    AVG(`utiu`.`itemCount`) AS `avgItems`,
    AVG(`utiu`.`userItemCount`) AS `avgBookmarks`,
    AVG(`utiu`.`tagCount`) AS `avgTags`,
    AVG(`utib`.`tagCount`) AS `avgTagsPerBookmark`,
    (SUM(CASE WHEN b.isPrivate > 0 THEN 1 ELSE 0 END) /
        COUNT(DISTINCT utiu.userId)) AS `avgPrivates`,
    (SUM(CASE WHEN b.isPrivate > 0 THEN 0 ELSE 1 END) /
       COUNT(DISTINCT utiu.userId)) AS `avgPublics`,
    (SUM( b.isFavorite ) /
       COUNT(DISTINCT utiu.userId)) AS `avgFavorites`,
    (SUM(CASE WHEN b.rating > 0 THEN 1 ELSE 0 END) /
       COUNT(DISTINCT utiu.userId)) AS `avgRated`
  FROM `userItem` AS `b`
  INNER JOIN (
    SELECT
      `utiu`.*,
      COUNT(DISTINCT utiu.userId) AS `userCount`,
      COUNT(DISTINCT utiu.tagId) AS `tagCount`,
      COUNT(DISTINCT utiu.itemId) AS `itemCount`,
      COUNT(DISTINCT utiu.userId,utiu.itemId) AS `userItemCount`
    FROM `userTagItem` AS `utiu`
    GROUP BY utiu.userId) AS `utiu`
  ON b.userId=utiu.userId
  INNER JOIN (
    SELECT
      `utib`.*,
      COUNT(DISTINCT utib.tagId) AS `tagCount`
    FROM `userTagItem` AS `utib`
    GROUP BY utib.userId,utib.itemId) AS `utib`
  ON ((b.userId=utib.userId) AND (b.itemId=utib.itemId));

 SELECT
    COUNT(DISTINCT utib.userId) AS users,
    COUNT(DISTINCT utib.itemId) AS items,
    COUNT(DISTINCT utib.userId,b.itemId) AS bookmarks,
    AVG(`utiu`.`userItemCount`) AS `avgBookmarksPerUser`,
    AVG(`utiu`.`tagCount`) AS `avgTagsPerUser`,
    AVG(`utib`.`tagCount`) AS `avgTagsPerBookmarkPerUser`,
    (SUM(CASE WHEN b.isPrivate > 0 THEN 1 ELSE 0 END) /
        COUNT(DISTINCT utiu.userId)) AS `avgPrivatesPerUser`,
    (SUM(CASE WHEN b.isPrivate > 0 THEN 0 ELSE 1 END) /
       COUNT(DISTINCT utiu.userId)) AS `avgPublicsPerUser`,
    (SUM( b.isFavorite ) /
       COUNT(DISTINCT utiu.userId)) AS `avgFavoritesPerUser`,
    (SUM(CASE WHEN b.rating > 0 THEN 1 ELSE 0 END) /
       COUNT(DISTINCT utiu.userId)) AS `avgRatedPerUser`,
    (SUM(b.rating) /
       SUM(CASE WHEN b.rating > 0 THEN 1 ELSE 0 END)) AS avgRating
  FROM `userItem` AS `b`
  INNER JOIN (
    SELECT
      `utiu`.*,
      COUNT(DISTINCT utiu.userId) AS `userCount`,
      COUNT(DISTINCT utiu.tagId) AS `tagCount`,
      COUNT(DISTINCT utiu.userId,utiu.itemId) AS `userItemCount`
    FROM `userTagItem` AS `utiu`
    GROUP BY utiu.userId) AS `utiu`
  ON b.userId=utiu.userId
  INNER JOIN (
    SELECT
      `utib`.*,
      COUNT(DISTINCT utib.tagId) AS `tagCount`
    FROM `userTagItem` AS `utib`
    GROUP BY utib.userId,utib.itemId) AS `utib`
  ON ((b.userId=utib.userId) AND (b.itemId=utib.itemId));




-- Per user statitics (0.32 sec)
 SELECT
    `b`.`userId`,
    COUNT( DISTINCT uti.userId ) AS `users`,
    COUNT( DISTINCT uti.itemId ) AS `items`,
    COUNT( DISTINCT uti.userId,uti.itemId ) AS `bookmarks`,
    SUM(CASE WHEN b.isPrivate > 0 THEN 1 ELSE 0 END) AS `privates`,
    SUM(CASE WHEN b.isPrivate > 0 THEN 0 ELSE 1 END) AS `publics`,
    SUM( b.isFavorite ) AS `favorites`,
    SUM(CASE WHEN b.rating > 0 THEN 1 ELSE 0 END) AS `rated`
  FROM `userItem` AS `b`
  INNER JOIN (
    SELECT
        `uti`.*
      FROM `userTagItem` AS `uti`
      GROUP BY `userId`) AS `uti`
  ON b.userId=uti.userId
  GROUP BY `userId`;

-- Per item statistics (0.42 sec)
 SELECT
    `b`.`itemId`,
    COUNT( DISTINCT uti.userId ) AS `users`,
    COUNT( DISTINCT uti.itemId ) AS `items`,
    COUNT( DISTINCT uti.userId,uti.itemId ) AS `bookmarks`,
    SUM(CASE WHEN b.isPrivate > 0 THEN 1 ELSE 0 END) AS `privates`,
    SUM(CASE WHEN b.isPrivate > 0 THEN 0 ELSE 1 END) AS `publics`,
    SUM( b.isFavorite ) AS `favorites`,
    SUM(CASE WHEN b.rating > 0 THEN 1 ELSE 0 END) AS `rated`
  FROM `userItem` AS `b`
  INNER JOIN (
    SELECT
        `uti`.*
      FROM `userTagItem` AS `uti`
      GROUP BY `itemId`) AS `uti`
  ON b.itemId=uti.itemId
  GROUP BY `itemId`;


 -- *************************************************************************
 -- User Statistics
 SELECT ui.userId,
        COUNT(DISTINCT ui.userId) as users,
        COUNT(DISTINCT ui.itemId) as items,
        COUNT(DISTINCT ui.userId,ui.itemId) as bookmarks,
        SUM( CASE WHEN ui.isPrivate > 0 THEN 1 ELSE 0 END ) as privates,
        SUM( CASE WHEN ui.isPrivate > 0 THEN 0 ELSE 1 END ) as publics,
        SUM( ui.isFavorite ) as favorites,
        SUM( CASE WHEN ui.rating > 0 THEN 1 ELSE 0 END) as rated
     FROM userItem AS ui
     GROUP BY ui.userId;

-- With privacy filter
 SELECT ui.userId,
        COUNT(DISTINCT ui.userId,ui.itemId) as bookmarks,
        SUM( CASE WHEN ui.isPrivate > 0 THEN 1 ELSE 0 END ) as privates,
        SUM( CASE WHEN ui.isPrivate > 0 THEN 0 ELSE 1 END ) as publics,
        SUM( ui.isFavorite ) as favorites,
        SUM( CASE WHEN ui.rating > 0 THEN 1 ELSE 0 END) as rated
     FROM userItem AS ui
     WHERE ((ui.isPrivate = 0) OR (ui.userId = 1))   -- Privacy filter
     GROUP BY ui.userId;


-- With tag statistics
 SELECT ui.userId,
        uti.tags,
        COUNT(DISTINCT ui.userId,ui.itemId) as bookmarks,
        SUM( CASE WHEN ui.isPrivate > 0 THEN 1 ELSE 0 END ) as privates,
        SUM( CASE WHEN ui.isPrivate > 0 THEN 0 ELSE 1 END ) as publics,
        SUM( ui.isFavorite ) as favorites,
        SUM( CASE WHEN ui.rating > 0 THEN 1 ELSE 0 END) as rated
     FROM userItem AS ui
     INNER JOIN(
         SELECT uti.userId,
                COUNT(DISTINCT uti.tagId) AS tags
             FROM userTagItem AS uti
             GROUP BY userId) AS uti
     ON ui.userId=uti.userId
     GROUP BY ui.userId;

-- With tag statistics AND privacy filter
 SELECT ui.userId,
        uti.tags,
        COUNT(DISTINCT ui.userId,ui.itemId) as bookmarks,
        SUM( CASE WHEN ui.isPrivate > 0 THEN 1 ELSE 0 END ) as privates,
        SUM( CASE WHEN ui.isPrivate > 0 THEN 0 ELSE 1 END ) as publics,
        SUM( ui.isFavorite ) as favorites,
        SUM( CASE WHEN ui.rating > 0 THEN 1 ELSE 0 END) as rated
     FROM userItem AS ui
     INNER JOIN(
         SELECT uti.userId,
                COUNT(DISTINCT uti.tagId) AS tags
             FROM userTagItem AS uti
             GROUP BY userId) AS uti
     ON ui.userId=uti.userId
     WHERE ((ui.isPrivate = 0) OR (ui.userId = 1))  -- Privacy filter
     GROUP BY ui.userId;


 -- *************************************************************************
 -- Item Statistics
 --     simply change ui.userId to ui.itemId in SELECT and GROUP BY
 SELECT ui.itemId,
        COUNT(DISTINCT ui.userId) as users,
        COUNT(DISTINCT ui.itemId) as items,
        COUNT(DISTINCT ui.userId,ui.itemId) as bookmarks,
        SUM( CASE WHEN ui.isPrivate > 0 THEN 1 ELSE 0 END ) as privates,
        SUM( CASE WHEN ui.isPrivate > 0 THEN 0 ELSE 1 END ) as publics,
        SUM( ui.isFavorite ) as favorites,
        SUM( CASE WHEN ui.rating > 0 THEN 1 ELSE 0 END) as rated
     FROM userItem AS ui
     GROUP BY ui.itemId;


-- With privacy filter
 SELECT ui.itemId,
        COUNT(DISTINCT ui.userId) as users,
        COUNT(DISTINCT ui.itemId) as items,
        COUNT(DISTINCT ui.userId,ui.itemId) as bookmarks,
        SUM( CASE WHEN ui.isPrivate > 0 THEN 1 ELSE 0 END ) as privates,
        SUM( CASE WHEN ui.isPrivate > 0 THEN 0 ELSE 1 END ) as publics,
        SUM( ui.isFavorite ) as favorites,
        SUM( CASE WHEN ui.rating > 0 THEN 1 ELSE 0 END) as rated
     FROM userItem AS ui
     WHERE ((ui.isPrivate = 0) OR (ui.userId = 1))   -- Privacy filter
     GROUP BY ui.itemId;


-- With tag statistics
 SELECT ui.itemId,
        uti.tags,
        COUNT(DISTINCT ui.userId) as users,
        COUNT(DISTINCT ui.itemId) as items,
        COUNT(DISTINCT ui.userId,ui.itemId) as bookmarks,
        SUM( CASE WHEN ui.isPrivate > 0 THEN 1 ELSE 0 END ) as privates,
        SUM( CASE WHEN ui.isPrivate > 0 THEN 0 ELSE 1 END ) as publics,
        SUM( ui.isFavorite ) as favorites,
        SUM( CASE WHEN ui.rating > 0 THEN 1 ELSE 0 END) as rated
     FROM userItem AS ui
     INNER JOIN(
         SELECT uti.itemId,
                COUNT(DISTINCT uti.tagId) AS tags
             FROM userTagItem AS uti
             GROUP BY itemId) AS uti
     ON ui.itemId=uti.itemId
     GROUP BY ui.itemId;


 -- *************************************************************************
 -- Tag Statistics
 SELECT uti.tagId,
        uti.tags,
        COUNT(DISTINCT ui.userId) as users,
        COUNT(DISTINCT ui.itemId) as items,
        COUNT(DISTINCT ui.userId,ui.itemId) as bookmarks,
        SUM( CASE WHEN ui.isPrivate > 0 THEN 1 ELSE 0 END ) as privates,
        SUM( CASE WHEN ui.isPrivate > 0 THEN 0 ELSE 1 END ) as publics,
        SUM( ui.isFavorite ) as favorites,
        SUM( CASE WHEN ui.rating > 0 THEN 1 ELSE 0 END) as rated
     FROM userItem AS ui
     INNER JOIN(
         SELECT uti.itemId,
                COUNT(DISTINCT uti.tagId) AS tags
             FROM userTagItem AS uti
             GROUP BY itemId) AS uti
     ON ui.itemId=uti.itemId
     GROUP BY ui.itemId;

 SELECT uti.tagId,
        COUNT(DISTINCT uti.userId) as users,
        COUNT(DISTINCT uti.itemId) as items,
        COUNT(DISTINCT uti.userId,uti.itemId) as bookmarks,
        SUM( CASE WHEN ui.isPrivate > 0 THEN 1 ELSE 0 END ) as privates,
        SUM( CASE WHEN ui.isPrivate > 0 THEN 0 ELSE 1 END ) as publics,
        SUM( ui.isFavorite ) as favorites,
        SUM( CASE WHEN ui.rating > 0 THEN 1 ELSE 0 END) as rated
     FROM userItem AS ui
     INNER JOIN(
         SELECT uti.*
             FROM userTagItem AS uti
             GROUP BY userId,itemId) AS uti
     ON (ui.userId=uti.userId) AND (ui.itemId=uti.itemId)
     GROUP BY uti.tagId;

-- General statistics (0.12 sec)
 SELECT
    COUNT(DISTINCT uti.userId) AS users,
    COUNT(DISTINCT uti.itemId) AS items,
    COUNT(DISTINCT uti.userId,uti.itemId) AS bookmarks,

    MIN(uti.tagCount) AS minTagsPerBookmark,
    AVG(uti.tagCount) AS avgTagsPerBookmark,
    MAX(uti.tagCount) AS maxTagsPerBookmark,
    STDDEV(uti.tagCount) AS sdTagsPerBookmark
  FROM (
    SELECT
      `uti`.*,
      COUNT(DISTINCT uti.tagId) AS `tagCount`
    FROM `userTagItem` AS `uti`
    GROUP BY uti.userId,uti.itemId) AS `uti`;


