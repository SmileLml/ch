CREATE TABLE `zt_ch_project` (
  `id` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `project` varchar(255) NOT NULL DEFAULT '0',
  `charter` mediumint NOT NULL DEFAULT '0',
  `model` char(30) NOT NULL DEFAULT '',
  `type` char(30) NOT NULL DEFAULT 'sprint',
  `category` char(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '',
  `lifetime` char(30) NOT NULL DEFAULT '',
  `budget` varchar(30) NOT NULL DEFAULT '0',
  `budgetUnit` char(30) NOT NULL DEFAULT 'CNY',
  `attribute` varchar(30) NOT NULL DEFAULT '',
  `percent` float unsigned NOT NULL DEFAULT '0',
  `milestone` enum('0','1') NOT NULL DEFAULT '0',
  `output` text,
  `auth` char(30) NOT NULL DEFAULT '',
  `parent` mediumint unsigned NOT NULL DEFAULT '0',
  `path` varchar(255) NOT NULL DEFAULT '',
  `grade` tinyint unsigned NOT NULL DEFAULT '0',
  `name` varchar(90) NOT NULL DEFAULT '',
  `code` varchar(45) NOT NULL DEFAULT '',
  `hasProduct` tinyint unsigned NOT NULL DEFAULT '1',
  `begin` date DEFAULT NULL,
  `end` date DEFAULT NULL,
  `firstEnd` date DEFAULT NULL,
  `realBegan` date DEFAULT NULL,
  `realEnd` date DEFAULT NULL,
  `days` smallint unsigned NOT NULL DEFAULT '0',
  `status` varchar(10) NOT NULL DEFAULT '',
  `subStatus` varchar(30) NOT NULL DEFAULT '',
  `pri` enum('1','2','3','4') NOT NULL DEFAULT '1',
  `desc` mediumtext,
  `version` smallint NOT NULL DEFAULT '0',
  `parentVersion` smallint NOT NULL DEFAULT '0',
  `planDuration` int NOT NULL DEFAULT '0',
  `realDuration` int NOT NULL DEFAULT '0',
  `progress` decimal(5,2) NOT NULL DEFAULT '0.00',
  `estimate` float NOT NULL DEFAULT '0',
  `left` float NOT NULL DEFAULT '0',
  `consumed` float NOT NULL DEFAULT '0',
  `teamCount` int NOT NULL DEFAULT '0',
  `market` mediumint NOT NULL DEFAULT '0',
  `openedBy` varchar(30) NOT NULL DEFAULT '',
  `openedDate` datetime DEFAULT NULL,
  `openedVersion` varchar(20) NOT NULL DEFAULT '',
  `lastEditedBy` varchar(30) NOT NULL DEFAULT '',
  `lastEditedDate` datetime DEFAULT NULL,
  `closedBy` varchar(30) NOT NULL DEFAULT '',
  `closedDate` datetime DEFAULT NULL,
  `closedReason` varchar(20) NOT NULL DEFAULT '',
  `canceledBy` varchar(30) NOT NULL DEFAULT '',
  `canceledDate` datetime DEFAULT NULL,
  `suspendedDate` date DEFAULT NULL,
  `PO` varchar(30) NOT NULL DEFAULT '',
  `PM` varchar(30) NOT NULL DEFAULT '',
  `QD` varchar(30) NOT NULL DEFAULT '',
  `RD` varchar(30) NOT NULL DEFAULT '',
  `team` varchar(90) NOT NULL DEFAULT '',
  `acl` char(30) NOT NULL DEFAULT 'open',
  `whitelist` text,
  `order` mediumint unsigned NOT NULL DEFAULT '0',
  `vision` varchar(10) NOT NULL DEFAULT 'rnd',
  `division` enum('0','1') NOT NULL DEFAULT '1',
  `displayCards` smallint NOT NULL DEFAULT '0',
  `fluidBoard` enum('0','1') NOT NULL DEFAULT '0',
  `multiple` enum('0','1') NOT NULL DEFAULT '1',
  `parallel` mediumint NOT NULL DEFAULT '0',
  `colWidth` smallint NOT NULL DEFAULT '264',
  `minColWidth` smallint NOT NULL DEFAULT '264',
  `maxColWidth` smallint NOT NULL DEFAULT '384',
  `deleted` enum('0','1') NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `parent` (`parent`),
  KEY `begin` (`begin`),
  KEY `end` (`end`),
  KEY `status` (`status`),
  KEY `acl` (`acl`),
  KEY `order` (`order`),
  KEY `project` (`project`),
  KEY `type_order` (`type`,`order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `zt_ch_projectteam` (
  `project` mediumint NOT NULL DEFAULT '0',
  `team` mediumint NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `zt_ch_projectintances` (
  `zentao` mediumint NOT NULL DEFAULT '0',
  `ch` mediumint NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `zt_ch_team`(
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `leader` varchar(30) NOT NULL DEFAULT '',
  `members` text NOT NULL DEFAULT '',
  `desc` text NOT NULL DEFAULT '',
  `createdBy` varchar(30) NOT NULL,
  `createdDate` datetime NOT NULL,
  `deleted` enum('0','1') NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `zt_user` ADD `isITDept` VARCHAR(200) DEFAULT NULL COMMENT '是否IT部门';

INSERT INTO `zt_cron` VALUES ('220', '0', '2', '*', '*', '*', 'moduleName=user&methodName=syncallusers', '定时同步用户', 'zentao', '0', 'normal', '2024-04-21 19:31:44');
ALTER TABLE `zt_dept` ADD `departmentCode` VARCHAR(200) DEFAULT('') COMMENT '部门编码';
ALTER TABLE `zt_dept` ADD `parentDepartmentCode` VARCHAR(200) DEFAULT('') COMMENT '父部门编码';

INSERT INTO `zt_cron` (`m`, `h`, `dom`, `mon`, `dow`, `command`, `remark`, `type`, `buildin`, `status`) VALUES('*', '0', '*', '*', '*', 'moduleName=user&methodName=syncAllDepts', '每日同步部门信息', 'zentao', '1', 'normal');

CREATE INDEX idx_objectType_objectID_action ON zt_action (objectType, objectID, action);

CREATE TABLE `zt_requestlog` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `url` varchar(200) DEFAULT NULL,
  `requestType` char(30) DEFAULT NULL,
  `status` char(30) DEFAULT NULL,
  `headers` longtext,
  `params` longtext,
  `response` longtext,
  `requestDate` datetime DEFAULT NULL,
  `extra` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;