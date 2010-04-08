CREATE TABLE user (
  userId        int(10)     unsigned    NOT NULL auto_increment,
  name          varchar(30)             NOT NULL unique default '',
  password      varchar(64)             NOT NULL default '',

  fullName      varchar(255)            NOT NULL default '',
  email         varchar(63)             NOT NULL default '',
  apiKey        char(8)                 NOT NULL default '',
  pictureUrl    TEXT                    NOT NULL default '',
  profile       TEXT                    NOT NULL default '',
  networkShared tinyint(1) unsigned     NOT NULL default 1,

  -- statistics about this user (some cached from more expensive selects)
  lastVisit     datetime                NOT NULL default '0000-00-00 00:00:00',
  lastVisitFor  datetime                NOT NULL default '0000-00-00 00:00:00',
  totalTags     int(10)     unsigned    NOT NULL default 0,
  totalItems    int(10)     unsigned    NOT NULL default 0,

  PRIMARY KEY       (`userId`),
  KEY `u_userName`  (`name`)
);

CREATE TABLE item (
  itemId        int(10)     unsigned    NOT NULL auto_increment,
  url           TEXT                    NOT NULL default '',
  urlHash       varchar(64)             NOT NULL default '',

  -- statistics about this item (some cached from more expensive selects)
  userCount     int(10)     unsigned    NOT NULL default 0,
  ratingCount   int(10)     unsigned    NOT NULL default 0,
  ratingSum     int(10)     unsigned    NOT NULL default 0,

  PRIMARY KEY       (`itemId`),
  KEY `i_urlHash`   (`urlHash`)
);

-- NOTE: There seems to be some issue with short tags:
--          select * from tag where tag='vi'
--              returns tags 'vi' AND 'ui'
--          select * from tag where tag='svn'
--              returns tags 'svn' AND 'sun'
--
--       Seems like:
--          u === v
--          i === j
CREATE TABLE tag (
  tagId         int(10)     unsigned    NOT NULL auto_increment,
  tag           varchar(30)             NOT NULL unique default '',

  PRIMARY KEY   (`tagId`),
  KEY `t_tag`   (`tag`)
);

CREATE TABLE network (
  userId        int(10)     unsigned    NOT NULL default 0,
  memberId      int(10)     unsigned    NOT NULL default 0,
  rating        tinyint(1)  unsigned    NOT NULL default 0,

  PRIMARY KEY           (`userId`, `memberId`),
  KEY `n_userId`        (`userId`),
  KEY `n_memberId`      (`memberId`)
);

CREATE TABLE memberGroup (
  groupId           int(10)     unsigned            NOT NULL auto_increment,
  name              varchar(128)                    NOT NULL unique default '',

  groupType         enum('user',  'item',  'tag')   NOT NULL default 'tag',

  controlMembers    enum('owner',  'group')         NOT NULL default 'owner',
  controlItems      enum('owner',  'group')         NOT NULL default 'owner',

  visibility        enum('private','group',
                         'public')                  NOT NULL default 'private',

  canTransfer       tinyint(1)  unsigned            NOT NULL default 0,
  ownerId           int(10)     unsigned            NOT NULL default 0,

  PRIMARY KEY           (`groupId`),
  KEY `n_groupType`     (`groupType`),
  KEY `n_ownerId`       (`ownerId`)
);

--
-- association tables
--

CREATE TABLE userItem (
  userId        int(10)     unsigned    NOT NULL default 0,
  itemId        int(10)     unsigned    NOT NULL default 0,

  name          varchar(255)            NOT NULL default '',
  description   TEXT                    NOT NULL default '',

  rating        tinyint(1)  unsigned    NOT NULL default 0,
  isFavorite    tinyint(1)  unsigned    NOT NULL default 0,
  isPrivate     tinyint(1)  unsigned    NOT NULL default 0,

  taggedOn      datetime                NOT NULL default '0000-00-00 00:00:00',
  updatedOn     datetime                NOT NULL default '0000-00-00 00:00:00',

  PRIMARY KEY               (`userId`, `itemId`),
  KEY       `ui_userId`     (`userId`),
  KEY       `ui_itemId`     (`itemId`),
  KEY       `ui_rating`     (`rating`),
  KEY       `ui_isPrivate`  (`isPrivate`),
  KEY       `ui_taggedOn`   (`taggedOn`),
  KEY       `ui_updatedOn`  (`updatedOn`),
  FULLTEXT  `ui_fullText`   (`name`, `description`)
);

CREATE TABLE itemTag (
  itemId        int(10)     unsigned    NOT NULL default 0,
  tagId         int(10)     unsigned    NOT NULL default 0,

  PRIMARY KEY       (`tagId`, `itemId`),
  KEY `it_itemId`   (`itemId`),
  KEY `it_tagId`    (`tagId`)
);

CREATE TABLE userTag (
  userId        int(10)     unsigned    NOT NULL default 0,
  tagId         int(10)     unsigned    NOT NULL default 0,

  PRIMARY KEY       (`userId`, `tagId`),
  KEY `ut_userId`   (`userId`),
  KEY `ut_tagId`    (`tagId`)
);

-- Members of a memberGroup
CREATE TABLE groupMember (
  userId        int(10)     unsigned    NOT NULL default 0,
  groupId       int(10)     unsigned    NOT NULL default 0,

  PRIMARY KEY       (`userId`, `groupId`),
  KEY `ut_userId`   (`userId`),
  KEY `ut_groupId`  (`groupId`)
);

-- Items within a memberGroup
--      The target table depends upon the 'groupType' of the memberGroup
--      (user, item, or tag).
CREATE TABLE groupItem (
  itemId        int(10)     unsigned    NOT NULL default 0,
  groupId       int(10)     unsigned    NOT NULL default 0,

  PRIMARY KEY       (`itemId`, `groupId`),
  KEY `ut_itemId`   (`itemId`),
  KEY `ut_groupId`  (`groupId`)
);

-- User Authentication methods
CREATE TABLE userAuth (
  userId        int(10)     unsigned    NOT NULL default 0,
  authType      varchar(30)             NOT NULL default 'password',

  credential    varchar(255)            NOT NULL default '',

  KEY `ua_userId`       (`userId`),
  KEY `ua_credential`   (`credential`)
);

--
-- fact table
--

CREATE TABLE userTagItem (
  userId        int(10)     unsigned    NOT NULL default 0,
  tagId         int(10)     unsigned    NOT NULL default 0,
  itemId        int(10)     unsigned    NOT NULL default 0,

  PRIMARY KEY       (`userId`, `tagId`, `itemId`),
  KEY `uti_ut`      (`userId`,`tagId`),
  KEY `uti_ui`      (`userId`,`itemId`),
  KEY `uti_it`      (`itemId`,`tagId`),
  KEY `uti_userId`  (`userId`),
  KEY `uti_tagId`   (`tagId`),
  KEY `uti_itemId`  (`itemId`)
);
