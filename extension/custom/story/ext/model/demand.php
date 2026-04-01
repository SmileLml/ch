<?php
public function getStoryProjects($storyID, $type = 'project')
{
    return $this->dao->select('t1.project, t2.name, t2.status')->from(TABLE_PROJECTSTORY)->alias('t1')
        ->leftJoin(TABLE_PROJECT)->alias('t2')->on('t1.project = t2.id')
        ->where('t2.deleted')->eq('0')
        ->beginIF($type == 'project')->andWhere('t2.type')->eq('project')->fi()
        ->beginIF($type != 'project')->andWhere('t2.type')->ne('project')->fi()
        ->andWhere('t1.story')->eq($storyID)
        ->fetchAll('project');
}

public function getStoryBuilds($storyID, $productID)
{
    return $this->dao->select('id, name')->from(TABLE_BUILD)
        ->where('product')->eq($productID)
        ->andWhere('deleted')->eq('0')
        ->andWhere("FIND_IN_SET('{$storyID}', stories)")
        ->fetchPairs();
}

public function getStoryTesttasks($buildIdList, $productID)
{
    return $this->dao->select('id, name, status')->from(TABLE_TESTTASK)
        ->where('product')->eq($productID)
        ->andWhere('build')->in($buildIdList)
        ->andWhere('deleted')->eq('0')
        ->fetchAll('id');
}

public function getStoryTestreports($taskIdList, $productID)
{
    return $this->dao->select('id, title')->from(TABLE_TESTREPORT)
        ->where('product')->eq($productID)
        ->andWhere('tasks')->in($taskIdList)
        ->andWhere('deleted')->eq('0')
        ->fetchPairs();
}

public function getStoryReleases($storyID, $productID)
{
    return $this->dao->select('id, name, status')->from(TABLE_RELEASE)
        ->where('product')->eq($productID)
        ->andWhere('deleted')->eq('0')
        ->andWhere("FIND_IN_SET('{$storyID}', stories)")
        ->fetchAll('id');
}
