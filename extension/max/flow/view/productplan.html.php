<?php $productPlans = json_decode($data->productPlan, true);?>
<?php if($productPlans):?>
<?php $productPlans  = array_column($productPlans, null, 'products');?>
<?php $productIdList = array_column($productPlans, 'products');?>
<?php $productPairs  = $this->dao->select('id, name')->from(TABLE_PRODUCT)->where('id')->in($productIdList)->fetchPairs();?>
<div class="detail" style="padding-top: 20px; border-top: 1px solid rgb(238, 238, 238);"?>
  <div class="detail-title">
    <strong><?php echo $lang->project->manageProducts;?></strong>
  </div>
  <div class="detail-content">
    <div class="row row-grid">
      <?php $branchGroups  = $this->loadModel('branch')->getByProducts($productIdList, 'noclosed');?>
      <?php foreach($productPairs as $productID => $productName):?>
        <?php if(isset($productPlans[$productID]['branch'])):?>
          <?php foreach(explode(',' , $productPlans[$productID]['branch']) as $branchID):?>
          <?php $branchName = isset($branchGroups[$productID][$branchID]) ? '/' . $branchGroups[$productID][$branchID] : '';?>
          <div class="col-xs-6">
            <?php echo $productName . $branchName;?>
          </div>
          <?php endforeach;?>
        <?php else:?>
          <div class="col-xs-6">
            <?php echo $productName;?>
          </div>
        <?php endif;?>
      <?php endforeach;?>
    </div>
  </div>
</div>
<div class="detail" style="padding-top: 20px; border-bottom: 1px solid rgb(238, 238, 238);">
  <div class="detail-title"><strong><?php echo $lang->execution->linkPlan;?></strong></div>
  <div class="detail-content">
    <div class="row row-grid">
      <?php foreach($productPairs as $productID => $productName):?>
        <?php if(!isset($productPlans[$productID]['plans']) || empty($productPlans[$productID]['plans'])) continue;?>
        <?php $planGroup = $this->loadModel('productplan')->getPairs($productID, '', 'noclosed', true);?>
        <?php $planIDList = explode(',', $productPlans[$productID]['plans']);?>
        <?php foreach($planIDList as $planID):?>
        <div class="col-xs-12"><?php echo $productName . '/' . $planGroup[$planID];?></div>
        <?php endforeach;?>
      <?php endforeach;?>
    </div>
  </div>
</div>
<?php endif;?>
