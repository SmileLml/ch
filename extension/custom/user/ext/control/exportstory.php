<?php
helper::importControl('user');
class myuser extends user
{
    public function exportStory()
    {
        $product = $this->dao->select('id,name')->from(TABLE_PRODUCT)->where('name')->eq('默认产品')->limit(1)->fetch();

        $this->loadModel('story');

        $this->post->fileType = 'xlsx';
        $this->post->num      = 1000;

        $this->config->story->templateFields = $this->config->user->migrateFields->story;
        $this->post->set('product', $product->name);
        $this->fetch('transfer', 'exportTemplate', 'model=story&params=productID='. $product->id);
    }
}
