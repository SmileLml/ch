<?php
class mytestreport extends testreport
{
    /**
     * Delete report.
     *
     * @param  int    $reportID
     * @param  string $confirm
     * @access public
     * @return void
     */
    public function delete($reportID, $confirm = 'no', $project = 0)
    {
        if($confirm == 'no')
        {
            return print(js::confirm($this->lang->testreport->confirmDelete, inlink('delete', "reportID=$reportID&confirm=yes&project={$project}")));
        }
        else
        {
            $testreport = $this->testreport->getById($reportID);
            $locateLink = $this->session->reportList ? $this->session->reportList : inlink('browse', "productID={$testreport->product}");

            if($this->app->tab == 'chteam')   $locateLink = $this->createLink('chproject', 'testreport', "project=$project");
            $this->testreport->delete(TABLE_TESTREPORT, $reportID);
            return print(js::locate($locateLink, 'parent'));
        }
    }
}
