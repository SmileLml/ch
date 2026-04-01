<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<div class='panel-body main-table'>
    <form method='post' enctype='multipart/form-data' class="form-ajax" style='padding: 5% 20%'>
        <?php foreach($needUserOperateList as $table => $operate):?>
        <table class="table"><tr><th><?php echo $table;?></th></tr></table>
        <?php $diffs = $operate['newField']; $oldDiffs = array('' => '') + $operate['mapping'];?>
        <table class='table'>
            <tr>
                <th>名称</th>
                <th>数据类型</th>
                <th>替换</th>
            </tr>
            <?php foreach($diffs as $diff):?>
                <tr>
                    <td><?php echo $diff['name'];?></td>
                    <td><?php echo $diff['field'] . '/' . $diff['type'];?></td>
                    <td><?php echo html::select("mapping[" . $table ."][" . $diff['field'] . "]", $oldDiffs, $diff['field'], "class='form-control chosen'");?></td>
                </tr>
            <?php endforeach;?>
        </table>
        <br>
        <?php endforeach;?>
        <br>
        <table class="table"><tr><th><?php echo '';?></th></tr></table>
        <table class="table">
        <?php foreach($actions as $action):?>
            <tr>
                <td><?php echo $action['name'];?></td>
                <td><?php echo $action['action'];?></td>
                <td><?php echo html::select("actionMapping[{$action['action']}]", array('0' => '保留', '1' => '覆盖'), '0', "class='form-control chosen'");?></td>
            </tr>
        <?php endforeach;?>
        </table>
        <tr>
        <?php echo html::submitButton('提交');?>
    </form>
</div>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>