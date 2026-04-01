<?php
/**
 * The projectreleases entry point of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2021 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     entries
 * @version     1
 * @link        http://www.zentao.net
 */
class chreleasesEntry extends entry
{
     /**
     * GET method.
     *
     * @param  string $projectIdList
     * @access public
     * @return string
     */
    public function get($projectIdList = '')
    {
        if(empty($projectIdList)) return $this->sendError(400, 'Need project id list.');

        $releases = $this->loadModel('projectrelease')->getByProjectIdList($projectIdList);

        return $this->send(200, array('releases' => $releases));
    }

     /**
     * POST method.
     *
     * @access public
     * @return string
     */
    public function post()
    {
        $releaseIdList = $this->param('releaseIdList');
        if(!$releaseIdList) return $this->sendError(400, 'Need release id list.');

        $status = $this->param('status');
        if(!$status) return $this->sendError(400, 'Need status.');

        $date = $this->param('date');
        if(!$date) return $this->sendError(400, 'Need date.');

        $this->loadModel('projectrelease')->updateStatusAndDate($releaseIdList, $status, $date);

        return $this->send(200, array('result' => 'success', 'message' => $this->lang->saveSuccess));
    }
}
