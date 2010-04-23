alter table user drop column password;
alter table user modify column apiKey char(10) NOT NULL default '';
alter table userAuth add PRIMARY KEY (userId,authType);
drop table userTag,itemTag;
