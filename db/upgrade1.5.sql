CREATE TABLE `zt_flow_businessstakeholder`  (
  `id` mediumint(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent` mediumint(0) UNSIGNED NOT NULL,
  `createdBy` varchar(30) NOT NULL,
  `createdDate` datetime(0) NOT NULL,
  `dept` varchar(255) NOT NULL,
  `stakeholder` varchar(255) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb3;

alter table zt_story add business mediumint not null default 0;

update `zt_story` set `estimate` = `estimate`/8;

ALTER TABLE `zt_story` MODIFY COLUMN status ENUM('', 'changing', 'active', 'draft', 'closed', 'reviewing', 'launched', 'developing', 'PRDReviewing', 'PRDReviewed', 'confirming') DEFAULT '';
