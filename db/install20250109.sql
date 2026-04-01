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

INSERT INTO `zt_workflowdatasource`(`type`, `name`, `code`, `datasource`, `view`, `keyField`, `valueField`, `buildin`, `vision`, `createdBy`, `createdDate`, `editedBy`, `editedDate`) VALUES ('system', '层级部门', 'deptMenu', '{\"app\":\"system\",\"module\":\"dept\",\"method\":\"getOptionMenu\",\"methodDesc\":\"Get option menu of departments.\",\"params\":[]}', '', '0', '0', 1, 'rnd', 'admin', '2024-06-30 09:00:00', '', NULL);
INSERT INTO `zt_workflowdatasource`(`type`, `name`, `code`, `datasource`, `view`, `keyField`, `valueField`, `buildin`, `vision`, `createdBy`, `createdDate`, `editedBy`, `editedDate`) VALUES ('system', '三级部门', 'gradeDeptMenu', '{\"app\":\"system\",\"module\":\"dept\",\"method\":\"getOptionMenuByGrade\",\"methodDesc\":\"Get option menu of departments by grade.\",\"params\":[{\"name\":\"rootDeptID\",\"type\":\"int\",\"desc\":\"\",\"value\":\"\"},{\"name\":\"grade\",\"type\":\"int\",\"desc\":\"\",\"value\":\"3\"}]}', '', '0', '0', 1, 'rnd', 'admin', '2024-06-30 09:00:00', '', NULL);

ALTER TABLE `zt_dept` ADD `leaders` text NOT NULL;

ALTER TABLE `zt_approvalflowobject` ADD `condition` longtext NOT NULL AFTER `objectID`;

INSERT INTO `zt_workflowdatasource` (`type`, `name`, `code`, `datasource`, `view`, `keyField`, `valueField`, `buildin`, `vision`, `createdBy`, `createdDate`, `editedBy`, `editedDate`) VALUES ('system', '预立项列表', 'projectApprovalPairs', '{\"app\":\"system\",\"module\":\"project\",\"method\":\"getProjectApprovalPairs\",\"methodDesc\":\"Get project approval list and filter by method.\",\"params\":[]}', '', '0', '0', 1, 'rnd', 'admin', '2024-07-23 14:32:41', '', NULL);

INSERT INTO `zt_workflowdatasource`(`type`, `name`, `code`, `datasource`, `view`, `keyField`, `valueField`, `buildin`, `vision`, `createdBy`, `createdDate`, `editedBy`, `editedDate`) VALUES ('system', '关联原始需求(需求池-原始需求)', 'demandMenuForBusiness', '{\"app\":\"system\",\"module\":\"demand\",\"method\":\"getDemandMenuForBusiness\",\"methodDesc\":\"Get demand menu for business.\",\"params\":[]}', 'view_datasource_82', '0', '0', 1, 'rnd', 'admin', '2024-07-26 10:33:02', 'admin', '2024-07-26 11:27:08');

CREATE TABLE `zt_objectversion` (
  `objectID` int UNSIGNED NOT NULL,
  `objectType` varchar(30) NOT NULL,
  `version` int UNSIGNED NOT NULL,
  `element` text NOT NULL,
  `createdBy` varchar(30) NOT NULL,
  `createdDate` datetime NOT NULL,
  UNIQUE KEY `object_version_unique` (`objectID`, `objectType`, `version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `zt_workflowdatasource`(`type`, `name`, `code`, `datasource`, `view`, `keyField`, `valueField`, `buildin`, `vision`, `createdBy`, `createdDate`, `editedBy`, `editedDate`) VALUES ('system', '业务需求归属项目', 'businessProject', '{\"app\":\"system\",\"module\":\"demand\",\"method\":\"getBusinessProject\",\"methodDesc\":\"Get business project by projectapproval.\",\"params\":[]}', '', '0', '0', 1, 'rnd', 'admin', '2024-08-05 09:00:00', '', NULL);

update `zt_workflowfield` set `type` = 'varchar', `length` = '255', `options` = '{"1":"\\u7d27\\u6025","2":"\\u4e2d\\u7b49","3":"\\u4e00\\u822c","4":"\\u4e0d\\u91cd\\u8981"}', `readonly` = '0', `buildin` = '0', `role` = 'custom', `order` = '0', `rules` = '1', `name` = '业务重要程度' where `module` = 'demand' and `field` = 'severity';

INSERT INTO `zt_workflowdatasource`(`type`, `name`, `code`, `datasource`, `view`, `keyField`, `valueField`, `buildin`, `vision`, `createdBy`, `createdDate`, `editedBy`, `editedDate`) VALUES ('system', '项目管理业务需求', 'projectapprovalBusiness', '{\"app\":\"system\",\"module\":\"projectapproval\",\"method\":\"getProjectapprovalBusiness\",\"methodDesc\":\"Get business menu for projectapproval\",\"params\":[]}', '', '0', '0', 1, 'rnd', 'admin', '2024-08-07 16:13:01', '', NULL);

CREATE TABLE `zt_flow_businessstakeholder`  (
  `id` mediumint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent` mediumint(0) UNSIGNED NOT NULL,
  `createdBy` varchar(30) NOT NULL,
  `createdDate` datetime(0) NOT NULL,
  `dept` varchar(255) NOT NULL,
  `stakeholder` varchar(255) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb3;

CREATE TABLE `zt_copyflow_business` (
  `id` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `mailto` text NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'projectchange',
  `severity` varchar(255) NOT NULL,
  `createdDept` varchar(255) NOT NULL,
  `deadline` date NOT NULL,
  `reasonType` varchar(255) NOT NULL,
  `desc` text NOT NULL,
  `name` varchar(255) NOT NULL,
  `demand` text NOT NULL,
  `dept` text NOT NULL,
  `businessDesc` text NOT NULL,
  `businessObjective` text NOT NULL,
  `project` varchar(255) DEFAULT NULL COMMENT '项目',
  `developmentBudget` varchar(255) NOT NULL,
  `outsourcingBudget` varchar(255) NOT NULL,
  `processChange` varchar(255) NOT NULL DEFAULT '2',
  `processName` varchar(255) NOT NULL,
  `businessUnit` varchar(255) NOT NULL,
  `business` int(8) NOT NULL,
  `version` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

ALTER TABLE `zt_case` ADD `tstestID` int(8) NOT NULL AFTER `id`;
ALTER TABLE `zt_case` ADD `callCaseID` text AFTER `tstestID`;

ALTER TABLE `zt_casestep` ADD `qcStepID` int(8) NOT NULL AFTER `id`;
ALTER TABLE `zt_casestep` ADD `dslinktest` int(8) NOT NULL AFTER `qcStepID`;
ALTER TABLE `zt_casestep` ADD `linkLibID` int(8) NOT NULL AFTER `qcStepID`;

INSERT INTO `zt_cron`(`m`, `h`, `dom`, `mon`, `dow`, `command`, `remark`, `type`, `buildin`, `status`) VALUES ('10', '0', '*', '*', '*', 'moduleName=monitoring&methodName=batchUpdateOverdueWarning', '更新逾期天数', 'zentao', '0', 'normal');
INSERT INTO `zt_cron`(`m`, `h`, `dom`, `mon`, `dow`, `command`, `remark`, `type`, `buildin`, `status`) VALUES ('0',  '1', '*', '*', '*', 'moduleName=monitoring&methodName=PRDoverdueReminder', 'PRD逾期预警', 'zentao', '0', 'normal');
INSERT INTO `zt_cron`(`m`, `h`, `dom`, `mon`, `dow`, `command`, `remark`, `type`, `buildin`, `status`) VALUES ('10', '1', '*', '*', '*', 'moduleName=monitoring&methodName=goLiveOverdueReminder', '上线逾期预警', 'zentao', '0', 'normal');
INSERT INTO `zt_cron`(`m`, `h`, `dom`, `mon`, `dow`, `command`, `remark`, `type`, `buildin`, `status`) VALUES ('20', '1', '*', '*', '*', 'moduleName=monitoring&methodName=acceptanceOverdueReminder', '验收逾期预警', 'zentao', '0', 'normal');
INSERT INTO `zt_cron`(`m`, `h`, `dom`, `mon`, `dow`, `command`, `remark`, `type`, `buildin`, `status`) VALUES ('30', '1', '*', '*', '*', 'moduleName=monitoring&methodName=terminationOverdueReminder', '项目结项逾期预警', 'zentao', '0', 'normal');

alter table zt_copyflow_business add `isCancel` varchar(255) NOT NULL DEFAULT 'N';

ALTER TABLE `zt_project` ADD COLUMN `chteam` VARCHAR(100) NULL;

alter table `zt_demand` add `stage` enum('0','1') NOT NULL DEFAULT '0';

ALTER TABLE `zt_story` ADD `fromDemand` mediumint(8) unsigned NOT NULL DEFAULT '0' AFTER `module`;

ALTER TABLE `zt_project` ADD `instance` int(8) NOT NULL DEFAULT '0';

alter table zt_story add `business` mediumint not null default '0';

alter table zt_story modify column `status` enum('', 'changing', 'active', 'draft', 'closed', 'reviewing', 'launched', 'developing', 'PRDReviewing', 'PRDReviewed', 'confirming', 'devInProgress', 'beOnline', 'cancelled') default '';

ALTER TABLE `zt_copyflow_business` ADD `operator` varchar(255) NOT NULL DEFAULT '';
