<?php
helper::importControl('dev');
class mydev extends dev
{
    /**
     * Get schema of database.
     *
     * @param  string $table
     * @access public
     * @return void
     */
    public function db($table = '')
    {
        $showFlowTables  = $this->config->dev->showFlowTables;

        foreach($showFlowTables as $tableName) {
            $subTableName = substr($tableName, strpos($tableName, '_') + 1);
            $this->config->dev->group[$subTableName] = 'redev';
        }

        $this->view->title         = $this->lang->dev->db;         $this->view->position[]    = html::a(inlink('api'), $this->lang->dev->common);
        $this->view->position[]    = $this->lang->dev->db;

        $this->view->tableTree     = $this->dev->getTree($table, 'table');
        $this->view->selectedTable = $table;
        $this->view->tab           = 'db';
        $this->view->fields        = $table ? $this->dev->getFields($table) : array();
        $this->display();
    }
}
