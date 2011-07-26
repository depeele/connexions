-- ALTER TABLE userItem ADD COLUMN updatedOn DATETIME NOT NULL default '0000-00-00 00:00:00';
-- ALTER TABLE userItem ADD KEY ui_updatedOn (updatedOn);
UPDATE userItem SET updatedOn=taggedOn;
