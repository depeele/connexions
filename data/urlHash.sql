-- ALTER TABLE item ADD COLUMN urlHash VARCHAR(64) NOT NULL default '';
-- ALTER TABLE item ADD KEY i_urlHash (urlHash);
-- ALTER TABLE item DROP KEY i_url;
-- ALTER TABLE item CHANGE url url TEXT NO NULL default '';
UPDATE item SET urlHash=MD5(LOWER(url));
