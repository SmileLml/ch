<?php
/**
 * Trans the approval to other reviewers(Only add reviewer(s) but not remove the old reviewer(s)).
 *
 * @param  string $objectType
 * @param  object $object
 * @param  string $extra
 * @access public
 * @return boolean
 */
public function trans($objectType, $object, $extra = '')
{
    $result = array('result' => 'fail');

    $approvalID = $this->dao->select('approval')->from(TABLE_APPROVALOBJECT)
        ->where('objectType')->eq($objectType)
        ->andWhere('objectID')->eq($object->id)
        ->orderBy('id_desc')
        ->fetch('approval');

    $nodes = $this->dao->select('*')->from(TABLE_APPROVALNODE)
        ->where('approval')->eq($approvalID)
        ->andWhere('status')->eq('doing')
        ->fetchAll();

    if(empty($nodes)) return $result;

    $existReviewers = array();
    $nodeTemplate   = current($nodes);
    $transToList    = $this->post->transTo;
    foreach($nodes as $node)
    {
        $existReviewers[] = $node->account;
    }

    unset($nodeTemplate->id);
    foreach($transToList as $transTo)
    {
        if(in_array($transTo, $existReviewers)) continue;

        $node = $nodeTemplate;
        $node->account  = $transTo;
        $this->dao->insert(TABLE_APPROVALNODE)->data($node)->exec();

        if(dao::isError()) return $result;
    }
    $oldUser = new stdclass();
    $newUser = new stdclass();
    $oldUser->approvalReviewers = implode(',', $existReviewers);
    $newUser->approvalReviewers = implode(',', $transToList);

    $result['result']  = 'success';
    $result['changes'] = common::createChanges($oldUser, $newUser);

    if($this->post->reviewResult == 'noReview')
    {
        $this->dao->update(TABLE_APPROVALNODE)
            ->set('status')->eq('done')
            ->set('result')->eq('noReview')
            ->set('date')->eq(helper::now())
            ->set('reviewedBy')->eq($this->app->user->account)
            ->set('reviewedDate')->eq(helper::now())
            ->where('approval')->eq($approvalID)
            ->andWhere('account')->eq($this->app->user->account)
            ->andWhere('status')->eq('doing')
            ->exec();
    }

    return $result;
}
