<?php
$config->chproject->customGanttFields = 'id,branch,assignedTo,progress,begin,realBegan,deadline,realEnd,duration,estimate,consumed,left,delay,delayDays,openedBy,finishedBy,project';

$config->chproject->ganttCustom = new stdclass();
$config->chproject->ganttCustom->ganttFields = 'assignedTo,project,progress,begin,deadline,duration';