ALTER TABLE `zt_dept` ADD `leaders` text NOT NULL;

ALTER TABLE `zt_approvalflowobject` ADD `condition` longtext NOT NULL AFTER `objectID`;
