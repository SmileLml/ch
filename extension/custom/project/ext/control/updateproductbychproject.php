<?php
class myProject extends project
{
    public function updateProductByChProject()
    {
        $intances = $this->dao->select('zentao')->from(TABLE_CHPROJECTINTANCES)->alias('t1')
            ->leftJoin(TABLE_PROJECT)->alias('t2')->on('t1.zentao = t2.id')
            ->where('t2.deleted')->eq(0)
            ->andWhere('t2.hasProduct')->eq(1)
            ->andWhere('t2.type')->ne('project')
            ->fetchPairs();

        $projects = $this->dao->select('t1.project')->from(TABLE_PROJECTPRODUCT)->alias('t1')
            ->leftJoin(TABLE_PROJECT)->alias('t2')->on('t1.project = t2.id')
            ->where('t2.deleted')->eq(0)
            ->andWhere('t2.hasProduct')->eq(1)
            ->andWhere('t2.type')->ne('project')
            ->fetchPairs();

        $diffProjects = array_diff($intances, $projects);

        $projectProduct = $this->dao->select('objectID, product')->from(TABLE_ACTION)
            ->where('objectType')->eq('execution')
            ->andWhere('product')->ne(',0,')
            ->andWhere('product')->ne(',,')
            ->andWhere('action')->eq('opened')
            ->andWhere('objectID')->in($diffProjects)
            ->fetchPairs();

        foreach($projectProduct as $objectID => $product)
        {
            $products = explode(',', trim($product, ','));
            foreach($products as $productID)
            {
                $this->dao->insert(TABLE_PROJECTPRODUCT)->data(array('project' => $objectID, 'product' => $productID))->exec();
            }
        }
    }
}
