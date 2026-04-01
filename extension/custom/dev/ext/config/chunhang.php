<?php
$config->dev->showFlowTables = array('zt_flow_business', 'zt_flow_projectapproval', 'zt_flow_projectbusiness', 'zt_flow_projectmembers', 'zt_flow_projectvalue', 'zt_flow_projectcost', 'zt_flow_projectreviewdetails');

$config->dev->group['demandpool'] = 'redev';
$config->dev->group['demand']     = 'redev';
$config->dev->group['ch_team']    = 'redev';
$config->dev->group['ch_project'] = 'redev';

$config->dev->tableMap['ch_team']    = 'chteam';
$config->dev->tableMap['ch_project'] = 'chproject';
