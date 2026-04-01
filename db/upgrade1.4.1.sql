UPDATE zt_workflowfield SET options = '{"pass":"\\u901a\\u8fc7","reject":"\\u4e0d\\u901a\\u8fc7","adjust":"\\u5f85\\u8c03\\u6574"}' WHERE module = 'projectapproval' AND field = 'reviewResult';

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
