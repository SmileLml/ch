CREATE TABLE `zt_yearplan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `desc` text,
  `status` varchar(30) DEFAULT NULL,
  `createdBy` varchar(30) DEFAULT NULL,
  `createdDate` datetime DEFAULT NULL,
  `owner` varchar(30) DEFAULT NULL,
  `participant` text NOT NULL,
  `acl` char(30) DEFAULT NULL,
  `deleted` enum('0','1') NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `zt_yearplandemand` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `parent` int NOT NULL DEFAULT '0',
  `level` tinyint unsigned NOT NULL DEFAULT '0',
  `category` tinyint unsigned NOT NULL DEFAULT '0',
  `initDept` mediumint unsigned NOT NULL DEFAULT '0',
  `dept` text NOT NULL,
  `approvalDate` datetime DEFAULT NULL,
  `planConfirmDate` datetime DEFAULT NULL,
  `goliveDate` datetime DEFAULT NULL,
  `itPlanInto` varchar(255) DEFAULT NULL,
  `itPM` varchar(255) DEFAULT NULL,
  `businessArchitect` varchar(255) DEFAULT NULL,
  `businessManager` text NOT NULL,
  `isPurchased` char(30) DEFAULT NULL,
  `purchasedContents` text NOT NULL,
  `status` varchar(30) DEFAULT NULL,
  `oldStatus` varchar(30) DEFAULT NULL,
  `mergeSources` text NOT NULL,
  `createdBy` varchar(30) DEFAULT NULL,
  `createdDate` datetime DEFAULT NULL,
  `deleted` enum('0','1') NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
CREATE TABLE `zt_yearplanmilestone` (
`id` int NOT NULL AUTO_INCREMENT,
`batch` varchar(255) NOT NULL DEFAULT '',
`parent` int NOT NULL DEFAULT '0',
`planConfirmDate` datetime DEFAULT NULL,
`goliveDate` datetime DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

alter table `zt_yearplandemand` add `desc` text;
alter table `zt_yearplandemand` add `mergeTo` int NOT NULL DEFAULT '0';
alter table `zt_yearplandemand` add `confirmResult` varchar(255) NOT NULL DEFAULT '';
alter table `zt_yearplandemand` add `confirmComment` text;
alter table `zt_yearplanmilestone` add `name` varchar(255) NOT NULL DEFAULT '';

alter table `zt_yearplandemand` modify `initDept` varchar(255) NOT NULL DEFAULT '0';

ALTER TABLE `zt_case` ADD INDEX `idx_deleted_status` (`deleted`, `status`);
