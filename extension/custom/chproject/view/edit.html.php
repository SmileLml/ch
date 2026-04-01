<?php
/**
 * The edit view of execution module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     execution
 * @version     $Id: edit.html.php 4728 2013-05-03 06:14:34Z chencongzhi520@gmail.com $
 * @link        http://www.zentao.net
 */
?>
<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/datepicker.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/kindeditor.html.php';?>
<?php js::import($jsRoot . 'misc/date.js');?>
<?php js::set('isWaterfall', '');?>
<?php js::set('executionAttr', $project->attribute);?>
<?php js::set('manageProductsLang', $lang->project->manageProducts);?>
<?php js::set('manageProductPlanLang', $lang->project->manageProductPlan);?>
<div id='mainContent' class='main-content'>
  <div class='center-block'>
    <div class='main-header'>
      <h2>
        <span class='prefix label-id'><strong><?php echo $project->id;?></strong></span>
        <?php echo $project->name;?>
        <small><?php echo $lang->arrow . ' ' . $lang->execution->edit;?></small>
      </h2>
    </div>
    <form class='load-indicator main-form form-ajax' method='post' target='hiddenwin' id='dataform'>
      <table class='table table-form'>
        <tr>
          <th class='c-method'><?php echo $lang->execution->project;?></th>
          <td><?php echo $project->projectName?></td><td></td>
        </tr>
        <tr>
          <th class='c-method'><?php echo $lang->chproject->newProject;?></th>
          <td colspan='2'><?php echo html::select('newProject[]', $newProjects, '', "class='form-control chosen' multiple");?></td>
        </tr>
        <tr>
          <th class='c-method'><?php echo $lang->execution->method;?></th>
          <td><?php echo zget($lang->execution->typeList, $project->type);?></td><td></td>
        </tr>
        <tr>
          <th class='c-name'><?php echo $lang->execution->name;?></th>
          <td><?php echo html::input('name', $project->name, "class='form-control' required");?></td><td></td>
        </tr>
        <?php if(isset($config->setCode) and $config->setCode == 1):?>
        <tr>
          <th><?php echo $lang->execution->code;?></th>
          <td><?php echo html::input('code', $project->code, "class='form-control' required");?></td>
        </tr>
        <?php endif;?>
        <tr>
          <th id="dateRange"><?php echo $lang->execution->dateRange;?></th>
          <td>
            <div class='input-group'>
              <?php echo html::input('begin', $project->begin, "class='form-control form-date' onchange='computeWorkDays()' required placeholder='" . $lang->execution->begin . "'");?>
              <span class='input-group-addon fix-border'><?php echo $lang->execution->to;?></span>
              <?php echo html::input('end', $project->end, "class='form-control form-date' onchange='computeWorkDays()' required placeholder='" . $lang->execution->end . "'");?>
              <div class='input-group-btn'>
                <button type='button' class='btn dropdown-toggle' data-toggle='dropdown'><?php echo $lang->execution->byPeriod;?> <span class='caret'></span></button>
                <ul class='dropdown-menu'>
                  <?php foreach ($lang->execution->endList as $key => $name):?>
                  <li><a href='javascript:computeEndDate("<?php echo $key;?>")'><?php echo $name;?></a></li>
                  <?php endforeach;?>
                </ul>
              </div>
            </div>
          </td>
        </tr>
        <tr>
          <th><?php echo $lang->execution->days;?></th>
          <td>
            <div class='input-group'>
              <?php echo html::input('days', $project->days, "class='form-control'");?>
              <span class='input-group-addon'><?php echo $lang->execution->day;?></span>
            </div>
          </td>
        </tr>
        <tr>
          <th><?php echo $lang->execution->type;?></th>
          <td>
          <?php echo html::select('lifetime', $lang->execution->lifeTimeList, $project->lifetime, "class='form-control' onchange='showLifeTimeTips()'"); ?>
          </td>
        </tr>
        <tr>
          <th><?php echo $lang->execution->teamname;?></th>
          <td><?php echo html::input('team', $project->team, "class='form-control'");?></td>
        </tr>
        <tr>
          <th rowspan='2'><?php echo $lang->execution->owner;?></th>
          <td>
            <div class='input-group'>
              <span class='input-group-addon'><?php echo $lang->execution->PO;?></span>
              <?php echo html::select('PO', $poUsers, $project->PO, "class='form-control chosen'");?>
            </div>
          </td>
          <td>
            <div class='input-group'>
              <span class='input-group-addon'><?php echo $lang->execution->QD;?></span>
              <?php echo html::select('QD', $qdUsers, $project->QD, "class='form-control chosen'");?>
            </div>
          </td>
        </tr>
        <tr>
          <td>
            <div class='input-group'>
              <span class='input-group-addon'><?php echo $lang->execution->PM;?></span>
              <?php echo html::select('PM', $pmUsers, $project->PM, "class='form-control chosen'");?>
            </div>
          </td>
          <td>
            <div class='input-group'>
              <span class='input-group-addon'><?php echo $lang->execution->RD;?></span>
              <?php echo html::select('RD', $rdUsers, $project->RD, "class='form-control chosen'");?>
            </div>
          </td>
        </tr>
        <?php if($project->type == 'stage' and isset($config->setPercent) and $config->setPercent == 1):?>
        <tr>
          <th><?php echo $lang->stage->percent;?></th>
          <td>
            <div class='input-group'>
              <?php echo html::input('percent', $project->percent, "class='form-control'");?>
              <span class='input-group-addon'>%</span>
            </div>
          </td>
        </tr>
        <?php endif;?>
        <?php $i = 0;?>
        <?php if($linkedProducts):?>
        <?php foreach($linkedProducts as $product):?>
        <tr class="<?php echo $hidden;?>">
          <th id='productTitle'><?php if($i == 0) echo $lang->project->manageProductPlan;?></th>
          <td class='text-left productsBox' colspan="3">
            <div class='row'>
              <div class="col-sm-6 productBox">
                <div class='table-row'>
                  <div class='table-col'>
                    <?php $hasBranch = $product->type != 'normal' and isset($branchGroups[$product->id]);?>
                    <div class='input-group <?php if($hasBranch) echo ' has-branch';?>'>
                      <span class='input-group-addon'><?php echo $lang->productCommon;?></span>
                      <?php $disabled = ($project->type == 'stage' and !$project->division) ? "disabled='disabled'" : '';?>
                      <?php echo html::select("products[$i]", $allProducts, $product->id, "class='form-control chosen' $disabled onchange='loadBranches(this)' data-last='" . $product->id . "' data-type='" . $product->type . "'");?>
                      <?php if($project->type == 'stage' and !$project->division) echo html::hidden("products[$i]", $product->id);?>
                    </div>
                  </div>
                  <div class='table-col <?php if(!$hasBranch) echo 'hidden';?>'>
                    <div class='input-group required'>
                      <span class='input-group-addon fix-border'><?php echo $lang->product->branchName['branch'];?></span>
                      <?php $branchIdList = join(',', $product->branches);?>
                      <?php echo html::select("branch[$i][]", isset($branchGroups[$product->id]) ? $branchGroups[$product->id] : array(), $branchIdList, "class='form-control chosen' multiple onchange=\"loadPlans('#products{$i}', this)\"");?>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-sm-6 planBox">
                <div class='input-group' <?php echo "id='plan$i'";?>>
                  <span class='input-group-addon'><?php echo $lang->product->plan;?></span>
                  <?php echo html::select("plans[$product->id][]", isset($productPlans[$product->id]) ? $productPlans[$product->id] : array(), $product->plans, "class='form-control chosen' multiple");?>
                  <?php if(!($project->type == 'stage' and !$project->division)):?>
                  <div class='input-group-btn'>
                    <a href='javascript:;' onclick='addNewLine(this)' class='btn btn-link addLine'><i class='icon-plus'></i></a>
                    <a href='javascript:;' onclick='removeLine(this)' class='btn btn-link removeLine' <?php if($i == 0) echo "style='visibility: hidden'";?>><i class='icon-close'></i></a>
                  </div>
                  <?php endif;?>
                </div>
              </div>
            </div>
          </td>
        </tr>
        <?php $i ++;?>
        <?php endforeach;?>
        <?php else:?>
        <tr>
          <th id='productTitle'><?php echo $lang->project->manageProductPlan;?></th>
          <td class='text-left productsBox' colspan='3'>
            <div class='row'>
              <div class="col-sm-6 productBox">
                <div class='table-row'>
                  <div class='table-col'>
                    <div class='input-group'>
                      <span class='input-group-addon'><?php echo $lang->productCommon;?></span>
                      <?php echo html::select("products[0]", $allProducts, '', "class='form-control chosen' onchange='loadBranches(this)'");?>
                    </div>
                  </div>
                  <div class='table-col hidden'>
                    <div class='input-group required'>
                      <span class='input-group-addon fix-border'><?php echo $lang->product->branchName['branch'];?></span>
                      <?php echo html::select("branch", '', '', "class='form-control chosen' multiple");?>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-sm-6 planBox">
                <div class='input-group' id='plan0'>
                  <span class='input-group-addon'><?php echo $lang->product->plan;?></span>
                  <?php echo html::select("plans[][]", '', '', "class='form-control chosen' multiple");?>
                  <div class='input-group-btn'>
                    <a href='javascript:;' onclick='addNewLine(this)' class='btn btn-link addLine'><i class='icon-plus'></i></a>
                    <a href='javascript:;' onclick='removeLine(this)' class='btn btn-link removeLine' style='visibility: hidden'><i class='icon-close'></i></a>
                  </div>
                </div>
              </div>
            </div>
          </td>
        </tr>
        <?php endif;?>
        <tr>
          <th><?php echo $lang->execution->team;?></th>
          <td colspan='2'><?php echo html::select('teamMembers[]', $users, array_keys($teamMembers), "class='form-control picker-select' multiple"); ?></td>
        </tr>
        <tr>
          <th><?php echo $lang->execution->desc;?></th>
          <td colspan='2'><?php echo html::textarea('desc', htmlSpecialString($project->desc), "rows='6' class='form-control kindeditor' hidefocus='true'");?></td>
        </tr>
        <?php $this->printExtendFields($project, 'table');?>
        <tr>
          <th><?php echo $lang->execution->acl;?></th>
          <?php $class = $project->grade == 2 ? "disabled='disabled'" : '';?>
          <td colspan='2'><?php echo nl2br(html::radio('acl', $lang->execution->aclList, $project->acl, "onclick='setWhite(this.value);' $class", 'block'));?></td>
        </tr>
        <tr class="<?php if($project->acl == 'open') echo 'hidden';?>" id="whitelistBox">
          <th><?php echo $lang->whitelist;?></th>
          <td colspan='2'>
            <div class='input-group'>
              <?php echo html::select('whitelist[]', $users, $project->whitelist, 'class="form-control picker-select" multiple');?>
              <?php echo $this->fetch('my', 'buildContactLists', "dropdownName=whitelist");?>
            </div>
          </td>
        </tr>
        <tr><td colspan='3' class='text-center form-actions'><?php echo html::submitButton() . ' ' . html::backButton();?></td></tr>
      </table>
    </form>
  </div>
</div>
<?php js::set('weekend', $config->execution->weekend);?>
<?php js::set('errorSameProducts', $lang->execution->errorSameProducts);?>
<?php js::set('errorSameBranches', $lang->execution->errorSameBranches);?>
<?php js::set('unmodifiableProducts',$unmodifiableProducts);?>
<?php js::set('unmodifiableBranches', $unmodifiableBranches)?>
<?php js::set('multiBranchProducts', $multiBranchProducts);?>
<?php js::set('confirmSync', $lang->execution->confirmSync);?>
<?php js::set('allProducts', $allProducts);?>
<?php js::set('branchGroups', $branchGroups);?>
<?php js::set('unLinkProductTip', $lang->project->unLinkProductTip);?>
<?php js::set('projectID', 0);?>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
