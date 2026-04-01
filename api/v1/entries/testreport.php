<?php
/**
 * The testreport entry point of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2021 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @package     entries
 * @version     1
 * @link        http://www.zentao.net
 */
class testreportEntry extends entry
{
    /**
     * GET method.
     *
     * @param  int    $testtaskID
     * @access public
     * @return string
     */
    public function get($testtaskID = 0)
    {

        $testreport = $this->loadModel('testreport')->getByID($testtaskID);

        $control = $this->loadController('testreport', 'export');

        $this->setPost('fileName', $testreport->title);
        $this->setPost('fileType', 'word');

        $control->export($testtaskID, 'api');
    }
}
