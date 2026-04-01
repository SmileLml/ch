<?php
helper::importControl('misc');
class myMisc extends misc 
{
    public function printFiles($files, $fieldset, $object = null)
    {
        $this->view->files    = $files;
        $this->view->fieldset = $fieldset;
        $this->view->object   = $object;

        $this->display();
    }
}
