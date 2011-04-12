CREATE TABLE user (
  userId        INT(10)     UNSIGNED    NOT NULL AUTO_INCREMENT,
  name          VARCHAR(30)             NOT NULL UNIQUE DEFAULT '',

  fullName      VARCHAR(255)            NOT NULL DEFAULT '',
  email         VARCHAR(63)             NOT NULL DEFAULT '',
  apiKey        char(10)                NOT NULL DEFAULT '',
  pictureUrl    TEXT                    NOT NULL DEFAULT '',
  profile       TEXT                    NOT NULL DEFAULT '',

  -- statistics about this user
  lastVisit     TIMESTAMP               NOT NULL DEFAULT CURRENT_TIMESTAMP,
  lastVisitFor  TIMESTAMP               NOT NULL DEFAULT 0,

  -- Statistics about this user:
  --     SELECT COUNT(DISTINCT tagId)  AS totalTags,
  --            COUNT(DISTINCT itemId) AS totalItems
  --        FROM  userTagItem
  --        WHERE userId=?;
  totalTags     INT(10)     UNSIGNED    NOT NULL DEFAULT 0,
  totalItems    INT(10)     UNSIGNED    NOT NULL DEFAULT 0,

  PRIMARY KEY       (`userId`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE item (
  itemId        INT(10)     UNSIGNED    NOT NULL AUTO_INCREMENT,
  url           TEXT                    NOT NULL DEFAULT '',
  urlHash       VARCHAR(64)             NOT NULL UNIQUE DEFAULT '',

  -- Statistics about this item
  --    SELECT COUNT(DISTINCT userId)                            AS userCount,
  --           SUM(CASE WHEN rating > 0 THEN 1 ELSE 0 END)       AS ratingCount,
  --           SUM(CASE rating WHEN null THEN 0 ELSE rating END) AS ratingSum
  --        FROM  userItem
  --        WHERE itemId=?;
  userCount     INT(10)     UNSIGNED    NOT NULL DEFAULT 0,
  ratingCount   INT(10)     UNSIGNED    NOT NULL DEFAULT 0,
  ratingSum     INT(10)     UNSIGNED    NOT NULL DEFAULT 0,

  PRIMARY KEY       (`itemId`),
  KEY `i_urlHash`   (`urlHash`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE tag (
  tagId         INT(10)     UNSIGNED    NOT NULL AUTO_INCREMENT,
  tag           VARCHAR(30)             NOT NULL UNIQUE DEFAULT '',

  -- Statistics about this tag:
  --     SELECT COUNT(DISTINCT userId) AS userCount,
  --            COUNT(DISTINCT itemId) AS itemCount
  --        FROM  userTagItem
  --        WHERE tagId=?;
  PRIMARY KEY   (`tagId`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- A Grouping of user, item, or tag with an associated Membership
-- (set of users), control, and visibility settings;
--
--     groupType      - what type of items are being grouped (user, item, tag);
--
--     controlMembers - who can add/delete Members (user or any member);
--     controlItems   - who can add/delete Items   (user or any member);
--
--     visibility     - who can view Items in this group?
--                            private == owner only,
--                            group   == any member of the group,
--                            public  == any user
--
--     canTransfer    - is the owner allowed to transfer ownership?
--      
CREATE TABLE memberGroup (
  groupId           INT(10)     UNSIGNED            NOT NULL AUTO_INCREMENT,
  name              VARCHAR(128)                    NOT NULL DEFAULT '',

  groupType         ENUM('user',  'item',  'tag')   NOT NULL DEFAULT 'tag',

  controlMembers    ENUM('owner',  'group')         NOT NULL DEFAULT 'owner',
  controlItems      ENUM('owner',  'group')         NOT NULL DEFAULT 'owner',

  visibility        ENUM('private','group',
                         'public')                  NOT NULL DEFAULT 'private',

  canTransfer       TINYINT(1)  UNSIGNED            NOT NULL DEFAULT 0,
  ownerId           INT(10)     UNSIGNED            NOT NULL DEFAULT 0,

  PRIMARY KEY           (`groupId`),
  KEY `mg_name`         (`name`),
  KEY `mg_groupType`    (`groupType`),
  KEY `mg_ownerId`      (`ownerId`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- association tables
--

-- UserItem is a User's Bookmark
CREATE TABLE userItem (
  userId        INT(10)     UNSIGNED    NOT NULL DEFAULT 0,
  itemId        INT(10)     UNSIGNED    NOT NULL DEFAULT 0,

  name          VARCHAR(255)            NOT NULL DEFAULT '',
  description   TEXT                    NOT NULL DEFAULT '',

  rating        TINYINT(1)  UNSIGNED    NOT NULL DEFAULT 0,
  isFavorite    TINYINT(1)  UNSIGNED    NOT NULL DEFAULT 0,
  isPrivate     TINYINT(1)  UNSIGNED    NOT NULL DEFAULT 0,

  -- Can only have one TIMESTAMP field that uses CURRENT_TIMESTAMP
  taggedOn      TIMESTAMP               NOT NULL DEFAULT '0000-00-00 00:00:00',
  updatedOn     TIMESTAMP               NOT NULL DEFAULT CURRENT_TIMESTAMP
                                               ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY               (`userId`, `itemId`),
  KEY       `ui_userId`     (`userId`),
  KEY       `ui_itemId`     (`itemId`),
  KEY       `ui_rating`     (`rating`),
  KEY       `ui_isPrivate`  (`isPrivate`),
  KEY       `ui_taggedOn`   (`taggedOn`),
  KEY       `ui_updatedOn`  (`updatedOn`),
  FULLTEXT  `ui_fullText`   (`name`, `description`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Members of a memberGroup
CREATE TABLE groupMember (
  groupId       INT(10)     UNSIGNED    NOT NULL DEFAULT 0,
  userId        INT(10)     UNSIGNED    NOT NULL DEFAULT 0,

  PRIMARY KEY       (`userId`, `groupId`),
  KEY `ut_userId`   (`userId`),
  KEY `ut_groupId`  (`groupId`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Items within a memberGroup
--      The table targetd by 'itemId' depends upon the 'groupType' of the
--      memberGroup (user, item, or tag).
CREATE TABLE groupItem (
  groupId       INT(10)     UNSIGNED    NOT NULL DEFAULT 0,
  itemId        INT(10)     UNSIGNED    NOT NULL DEFAULT 0,

  PRIMARY KEY       (`itemId`, `groupId`),
  KEY `ut_itemId`   (`itemId`),
  KEY `ut_groupId`  (`groupId`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- User Authentication methods
CREATE TABLE userAuth (
  userAuthId    INT(10)     UNSIGNED    NOT NULL AUTO_INCREMENT,
  userId        INT(10)     UNSIGNED    NOT NULL DEFAULT 0,
  authType      VARCHAR(30)             NOT NULL DEFAULT 'password',

  credential    VARCHAR(255)            NOT NULL DEFAULT '',

  -- User-selected name for this credential
  name          VARCHAR(32)             NOT NULL DEFAULT '',

  PRIMARY KEY           (`userAuthId`),
  KEY `ua_triple`       (`userId`, `authType`, `credential`),
  KEY `ua_userId`       (`userId`),
  KEY `ua_credential`   (`credential`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- fact table
--

CREATE TABLE userTagItem (
  userId        INT(10)     UNSIGNED    NOT NULL DEFAULT 0,
  tagId         INT(10)     UNSIGNED    NOT NULL DEFAULT 0,
  itemId        INT(10)     UNSIGNED    NOT NULL DEFAULT 0,

  PRIMARY KEY       (`userId`, `tagId`, `itemId`),
  KEY `uti_ut`      (`userId`,`tagId`),
  KEY `uti_ui`      (`userId`,`itemId`),
  KEY `uti_it`      (`itemId`,`tagId`),
  KEY `uti_userId`  (`userId`),
  KEY `uti_tagId`   (`tagId`),
  KEY `uti_itemId`  (`itemId`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- activity table (for activity streams)
--
CREATE TABLE activity (
  activityId    INT(10)     UNSIGNED    NOT NULL AUTO_INCREMENT,

  -- The userId of the user initiating this activity
  userId        INT(10)     UNSIGNED    NOT NULL DEFAULT 0,

  -- An objectType is the lower-case name of the target Model instance
  -- (bookmark, group, item, tag, user, userauth) though we don't
  -- explicitly limit (via ENUM) here.
  objectType    VARCHAR(16)            	NOT NULL DEFAULT 'bookmark',

  -- objectId is the string representation of an object identifier
  -- For a 'bookmark', this would be '%userId%:%itemId%'
  objectId      VARCHAR(32)             NOT NULL DEFAULT '',

  -- The activity/operation - maps to an activity stream verb
  -- 	'save'	 => 'post' | 'save'
  --	'update' => 'update'
  --	'delete' => 'delete'
  operation		ENUM('save', 'update',
  					 'delete')			NOT NULL DEFAULT 'save',

  -- The time of the activity
  time          TIMESTAMP               NOT NULL DEFAULT CURRENT_TIMESTAMP,

  -- A JSON-encoded string of object properties
  properties    TEXT                    NOT NULL DEFAULT '',

  PRIMARY KEY               (`activityId`),
  KEY       `a_userId`      (`userId`),
  KEY       `a_objectId`    (`objectType`, `objectId`)

) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
