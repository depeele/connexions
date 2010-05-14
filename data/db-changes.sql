-- Can only have one TIMESTAMP field per table that uses CURRENT_TIMESTAMP
ALTER TABLE user CHANGE lastVisit
            lastVisit  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE userItem CHANGE taggedOn
            taggedOn   TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00';

ALTER TABLE userItem ADD
            updatedOn  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                                        ON UPDATE CURRENT_TIMESTAMP
                                        AFTER taggedOn;
            
-- ALTER TABLE userItem CHANGE updatedOn
--             updatedOn  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
--                                         ON UPDATE CURRENT_TIMESTAMP;



ALTER TABLE user DROP COLUMN password;
ALTER TABLE user DROP COLUMN networkShared;
ALTER TABLE user DROP COLUMN lastVisitFor;
ALTER TABLE user MODIFY COLUMN apiKey CHAR(10) NOT NULL DEFAULT '';

ALTER TABLE userAuth ADD PRIMARY KEY (userId,authType);

DROP  TABLE userTag,itemTag,network;


-- Alter collation to allow case-insensitive matches
ALTER TABLE user        DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE item        DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE tag         DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE memberGroup DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE userItem    DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE groupMember DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE groupItem   DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE userAuth    DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE userTagItem DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;


ALTER TABLE user        CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE item        CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE tag         CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE memberGroup CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE userItem    CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE groupMember CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE groupItem   CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE userAuth    CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE userTagItem CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;

