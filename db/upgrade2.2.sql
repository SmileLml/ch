CREATE TABLE IF NOT EXISTS `zt_flow_process` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT '',
  `path` text,
  `code` varchar(255) DEFAULT '',
  `parentId` varchar(255) DEFAULT '',
  `order` varchar(45) DEFAULT '0',
  `type` varchar(45) DEFAULT '',
  `version` varchar(45) DEFAULT '',
  `deleted`  enum('0','1') NOT NULL DEFAULT '0',
  `lastUpdateDate` datetime NOT NULL,
  PRIMARY KEY (`id`)
);