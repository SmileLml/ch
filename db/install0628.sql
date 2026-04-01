ALTER TABLE `zt_demandpool` ADD `participant` text NOT NULL AFTER `owner`;

ALTER TABLE `zt_story` ADD `fromDemand` mediumint(8) unsigned NOT NULL DEFAULT '0' AFTER `module`;

ALTER TABLE `zt_workflowlayout` ADD `colspan` tinyint(2) default '1' AFTER `vision`;
ALTER TABLE `zt_workflowlayout` ADD `titleWidth` char(10) default 'auto' AFTER `colspan`;
ALTER TABLE `zt_workflowlayout` ADD `titleColspan` tinyint(2) default '1' AFTER `titleWidth`;

ALTER TABLE `zt_workflowaction` ADD `columns` tinyint(2) default '2' AFTER `editedDate`;

INSERT INTO `zt_workflowdatasource`(`type`, `name`, `code`, `datasource`, `view`, `keyField`, `valueField`, `buildin`, `vision`, `createdBy`, `createdDate`, `editedBy`, `editedDate`) VALUES ('lang', '需求池状态', 'demandpoolStatus', 'demandpoolStatus', '', '', '', 1, 'rnd', 'admin', '2024-06-18 09:00:00', '', NULL);
INSERT INTO `zt_workflowdatasource`(`type`, `name`, `code`, `datasource`, `view`, `keyField`, `valueField`, `buildin`, `vision`, `createdBy`, `createdDate`, `editedBy`, `editedDate`) VALUES ('lang', '原始需求类别', 'demandCategory', 'demandCategory', '', '', '', 1, 'rnd', 'admin', '2024-06-18 09:00:00', '', NULL);
INSERT INTO `zt_workflowdatasource`(`type`, `name`, `code`, `datasource`, `view`, `keyField`, `valueField`, `buildin`, `vision`, `createdBy`, `createdDate`, `editedBy`, `editedDate`) VALUES ('lang', '原始需求来源', 'demandSource', 'demandSource', '', '', '', 1, 'rnd', 'admin', '2024-06-18 09:00:00', '', NULL);
INSERT INTO `zt_workflowdatasource`(`type`, `name`, `code`, `datasource`, `view`, `keyField`, `valueField`, `buildin`, `vision`, `createdBy`, `createdDate`, `editedBy`, `editedDate`) VALUES ('lang', '原始需求状态', 'demandStatus', 'demandStatus', '', '', '', 1, 'rnd', 'admin', '2024-06-20 11:54:04', '', NULL);
INSERT INTO `zt_workflowdatasource`(`type`, `name`, `code`, `datasource`, `view`, `keyField`, `valueField`, `buildin`, `vision`, `createdBy`, `createdDate`, `editedBy`, `editedDate`) VALUES ('system', '用户为项目负责人项目', 'PMProject', '{\"app\":\"system\",\"module\":\"project\",\"method\":\"getPairsByPM\",\"methodDesc\":\"Get project pairs by PM.\",\"params\":[]}', '', '0', '0', 1, 'rnd', 'admin', '2024-06-20 18:37:37', '', NULL);
INSERT INTO `zt_workflowdatasource`(`type`, `name`, `code`, `datasource`, `view`, `keyField`, `valueField`, `buildin`, `vision`, `createdBy`, `createdDate`, `editedBy`, `editedDate`) VALUES ('lang', '原始需求所属阶段', 'demandStage', 'demandStage', '', '0', '0', 1, 'rnd', 'admin', '2024-06-21 00:12:22', '', NULL);

ALTER TABLE `zt_flow_projectapproval` ALTER COLUMN status SET DEFAULT 'draft';

ALTER TABLE `zt_flow_business` ALTER COLUMN status SET DEFAULT 'draft';

ALTER TABLE `zt_demand` modify dept varchar(255) not null default '';

UPDATE `zt_workflowfield` set `type` = 'varchar', `length` = '255', `control` = 'multi-select', `options` = (select `id` from `zt_workflowdatasource` where `code` = 'deptMenu') where `module` = 'demand' and `field` = 'dept';

INSERT INTO `zt_workflowdatasource`(`type`, `name`, `code`, `datasource`, `view`, `keyField`, `valueField`, `buildin`, `vision`, `createdBy`, `createdDate`, `editedBy`, `editedDate`) VALUES ('system', '层级部门', 'deptMenu', '{\"app\":\"system\",\"module\":\"dept\",\"method\":\"getOptionMenu\",\"methodDesc\":\"Get option menu of departments.\",\"params\":[]}', '', '0', '0', 1, 'rnd', 'admin', '2024-06-30 09:00:00', '', NULL);
INSERT INTO `zt_workflowdatasource`(`type`, `name`, `code`, `datasource`, `view`, `keyField`, `valueField`, `buildin`, `vision`, `createdBy`, `createdDate`, `editedBy`, `editedDate`) VALUES ('system', '三级部门', 'gradeDeptMenu', '{\"app\":\"system\",\"module\":\"dept\",\"method\":\"getOptionMenuByGrade\",\"methodDesc\":\"Get option menu of departments by grade.\",\"params\":[{\"name\":\"rootDeptID\",\"type\":\"int\",\"desc\":\"\",\"value\":\"\"},{\"name\":\"grade\",\"type\":\"int\",\"desc\":\"\",\"value\":\"3\"}]}', '', '0', '0', 1, 'rnd', 'admin', '2024-06-30 09:00:00', '', NULL);

ALTER TABLE `zt_demand` ALTER COLUMN stage SET DEFAULT '0';
ALTER TABLE `zt_demand` MODIFY project VARCHAR(255) NOT NULL DEFAULT '';
