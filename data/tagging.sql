CREATE TABLE user (
  userid        int(10)     unsigned    NOT NULL auto_increment,
  name          varchar(30)             NOT NULL default '',
  fullName      varchar(255)            NOT NULL default '',
  email         varchar(63)             NOT NULL default '',
  pictureUrl    TEXT                    NOT NULL default '',
  profile       TEXT                    NOT NULL default '',

  -- statistics about this user (some cached from more expensive selects)
  lastVisit   	datetime   				NOT NULL default '0000-00-00 00:00:00',
  totalTags     int(10)     unsigned    NOT NULL default 0,
  totalItems    int(10)     unsigned    NOT NULL default 0,

  PRIMARY KEY   	(`userid`),
  KEY `user_name` 	(`name`)
);

CREATE TABLE item (
  itemid        int(10)     unsigned    NOT NULL auto_increment,
  url           varchar(255)            NOT NULL default '',
--url           TEXT                    NOT NULL default '',

  -- statistics about this item (some cached from more expensive selects)
  userCount     int(10)     unsigned    NOT NULL default 0,
  ratingCount   int(10)     unsigned    NOT NULL default 0,
  ratingSum     int(10)     unsigned    NOT NULL default 0,

  PRIMARY KEY  	(`itemid`),
  KEY `i_url` 	(`url`)
);

CREATE TABLE tag (
  tagid         int(10)     unsigned    NOT NULL auto_increment,
  tag           varchar(30)             NOT NULL default '',

  PRIMARY KEY  	(`tagid`),
  KEY `t_tag`	(`tag`)
);

CREATE TABLE watchlist (
  userid        int(10)     unsigned    NOT NULL default 0,
  watchingid    int(10)     unsigned    NOT NULL default 0,
  rating        tinyint(1)  unsigned    NOT NULL default 0,

  PRIMARY KEY           (`userid`, `watchingid`),
  KEY `n_userid`	    (`userid`),
  KEY `n_watchingid`    (`watchingid`)
);

--
-- association tables
--

CREATE TABLE useritem (
  userid        int(10)     unsigned    NOT NULL default 0,
  itemid        int(10)     unsigned    NOT NULL default 0,

  name          varchar(255)            NOT NULL default '',
  description   blob                    NOT NULL default '',

  rating        tinyint(1)  unsigned    NOT NULL default 0,
  is_favorite   tinyint(1)  unsigned    NOT NULL default 0,
  is_private    tinyint(1)  unsigned    NOT NULL default 0,

  tagged_on     datetime                NOT NULL default '0000-00-00 00:00:00',

  PRIMARY KEY  			(`userid`, `itemid`),
  KEY `ui_userid` 		(`userid`),
  KEY `ui_itemid` 		(`itemid`),
  KEY `ui_rating` 		(`rating`),
  KEY `ui_is_private`	(`is_private`),
  KEY `ui_tagged_on`	(`tagged_on`)
);

CREATE TABLE itemtag (
  itemid        int(10)     unsigned    NOT NULL default 0,
  tagid         int(10)     unsigned    NOT NULL default 0,

  PRIMARY KEY   	(`tagid`, `itemid`),
  KEY `it_itemid`	(`itemid`),
  KEY `it_tagid` 	(`tagid`)
);

CREATE TABLE usertag (
  userid        int(10)     unsigned    NOT NULL default 0,
  tagid         int(10)     unsigned    NOT NULL default 0,

  PRIMARY KEY   (`userid`, `tagid`),
  KEY `ut_userid`	(`userid`),
  KEY `ut_tagid` 	(`tagid`)
);

--
-- fact table
--

CREATE TABLE usertagitem (
  userid        int(10)     unsigned    NOT NULL default 0,
  tagid         int(10)     unsigned    NOT NULL default 0,
  itemid        int(10)     unsigned    NOT NULL default 0,

  PRIMARY KEY	(`userid`, `tagid`, `itemid`),
  KEY `uti_ut`	(`userid`,`tagid`),
  KEY `uti_ui`	(`userid`,`itemid`)
);
