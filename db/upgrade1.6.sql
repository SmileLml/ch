ALTER TABLE `zt_case` ADD `tstestID` int(8) NOT NULL AFTER `id`;
ALTER TABLE `zt_case` ADD `callCaseID` text AFTER `tstestID`;

ALTER TABLE `zt_casestep` ADD `qcStepID` int(8) NOT NULL AFTER `id`;
ALTER TABLE `zt_casestep` ADD `dslinktest` int(8) NOT NULL AFTER `qcStepID`;
ALTER TABLE `zt_casestep` ADD `linkLibID` int(8) NOT NULL AFTER `qcStepID`;
