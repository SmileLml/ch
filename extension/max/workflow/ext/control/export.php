<?php
class workflow extends control
{
    /**
     * Delete a flow or table.
     *
     * @param  int    $id
     * @access public
     * @return void
     */
    public function export($id)
    {
        return $this->send(array('result' => 'success', "path" => $this->workflow->exportSerivce($id)));
    }
}