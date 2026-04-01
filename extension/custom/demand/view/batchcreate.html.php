<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<div id='mainContent' class='main-content fade'>
  <div class='main-header'>
    <h2>
      <?php echo $lang->demand->batchCreate;?>
    </h2>
  </div>

  <form class='main-form form-ajax' method='post' id='batchCreateForm' style='overflow-x: scroll'>
    <div class="table-responsive">
      <table class='table table-form'>
        <thead>
          <tr class='text-center'>
            <th class='c-id text-center'><?php echo $lang->idAB;?></th>
            <th class='w-150px'><?php echo $lang->demand->dept;?></th>
            <th class='w-150px required'><?php echo $lang->demand->name;?></th>
            <th class='w-150px required'><?php echo $lang->demand->businessDesc;?></th>
            <th class='w-200px required'><?php echo $lang->demand->desc;?></th>
            <th class='w-150px required'><?php echo $lang->demand->businessObjective;?></th>
            <!-- <th class='w-120px'><?php echo $lang->demand->reviewer;?></th> -->
            <th class='w-140px required'><?php echo $lang->demand->deadline;?></th>
            <?php
            $extendFields = $this->demand->getFlowExtendFields();
            foreach($extendFields as $extendField)
            {
              $required = strpos(",$extendField->rules,", ',1,') !== false ? 'required' : '';
              echo "<th class='w-200px $required'>{$extendField->name}</th>";
            }
            ?>
          </tr>
        </thead>
        <?php
        $priList = $lang->demand->priList;
        $priList['ditto'] = $lang->demand->ditto;

        $severityList          = $lang->demand->severityList;
        $severityList['ditto'] = $lang->demand->ditto;

        $assigee = $users;
        $assigee['ditto'] = $lang->demand->ditto;

        $modules = $moduleOption;
        $modules['ditto'] = $lang->demand->ditto;
        ?>
        <tbody>
          <?php for($i = 1; $i <= 10; $i ++):?>
          <tr>
            <td class='text-center'><?php echo $i;?></td>
            <td><?php echo html::select("dept[$i][]", $depts, '', 'class="form-control chosen" multiple');?></td>
            <td><?php echo html::input("name[$i]", '', 'class="form-control"');?></td>
            <td><?php echo html::textarea("businessDesc[$i]", '', 'class="form-control" rows=1');?></td>
            <td><?php echo html::textarea("desc[$i]", '', 'class="form-control" rows=1');?></td>
            <td><?php echo html::textarea("businessObjective[$i]", '', 'class="form-control" rows=1');?></td>
            <!-- <td><?php echo html::select("reviewer[$i]", $users, $pool->owner, 'class="form-control chosen"');?></td> -->
            <td><?php echo html::input("deadline[$i]", '', 'class="form-control form-date"');?></td>
            <?php
            $this->loadModel('flow');
            foreach($extendFields as $extendField) echo "<td" . (($extendField->control == 'select' or $extendField->control == 'multi-select') ? " style='overflow:visible'" : '') . ">" . $this->flow->getFieldControl($extendField, '', $extendField->field . "[$i]") . "</td>";
            ?>
          </tr>
          <?php endfor;?>
        <tbody>
        <tfoot>
          <tr>
            <td colspan='9' class='text-center form-actions'>
              <?php echo html::submitButton();?>
              <?php echo html::backButton();?>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  </form>
</div>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
