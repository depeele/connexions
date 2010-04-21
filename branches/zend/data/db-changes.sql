alter table user drop column password;
alter table userAuth add PRIMARY KEY (userId,authType);
drop table userTag,itemTag;
