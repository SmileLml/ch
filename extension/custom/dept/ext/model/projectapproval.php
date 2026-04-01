<?php
/**
 * Update dept.
 *
 * @param  int    $deptID
 * @access public
 * @return void
 */
public function update($deptID)
{
    $dept = fixer::input('post')
        ->setDefault('leaders', '')
        ->join('leaders', ',')
        ->get();

    $self   = $this->getById($deptID);
    $parent = $this->getById($this->post->parent);
    $childs = $this->getAllChildId($deptID);
    $dept->grade = $parent ? $parent->grade + 1 : 1;
    $dept->path  = $parent ? $parent->path . $deptID . ',' : ',' . $deptID . ',';
    $this->dao->update(TABLE_DEPT)->data($dept)->autoCheck()->check('name', 'notempty')->where('id')->eq($deptID)->exec();
    $this->dao->update(TABLE_DEPT)->set('grade = grade + 1')->where('id')->in($childs)->andWhere('id')->ne($deptID)->exec();
    $this->dao->update(TABLE_DEPT)->set('manager')->eq($this->post->manager)->where('id')->in($childs)->andWhere('manager')->eq('')->exec();
    $this->dao->update(TABLE_DEPT)->set('manager')->eq($this->post->manager)->where('id')->in($childs)->andWhere('manager')->eq($self->manager)->exec();
    $this->fixDeptPath();
}

public function getByName($name, $parent = 0)
{
    return $this->dao->select('*')->from(TABLE_DEPT)
        ->where('name')->eq($name)
        ->beginIF($parent)
        ->andWhere('parent')->eq($parent)
        ->fi()
        ->fetch();
}
