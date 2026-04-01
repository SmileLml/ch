ALTER TABLE `zt_copyflow_business` ADD `operator` varchar(255) NOT NULL DEFAULT '';

ALTER TABLE `zt_objectversion` ADD INDEX `idx_objectID` (objectID);
ALTER TABLE `zt_objectversion` ADD INDEX `idx_objectType` (objectType);
ALTER TABLE `zt_objectversion` MODIFY COLUMN `element` MEDIUMTEXT NOT NULL;
