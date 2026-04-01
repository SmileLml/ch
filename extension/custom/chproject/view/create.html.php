<?php
/**
 * The create view of chproject module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     chproject
 * @version     $Id: create.html.php 4728 2013-05-03 06:14:34Z chencongzhi520@gmail.com $
 * @link        http://www.zentao.net
 */
?>
<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<?php include $app->getModuleRoot() . 'common/view/kindeditor.html.php';?>
<?php js::import($jsRoot . 'misc/date.js');?>
<?php js::set('weekend', $config->execution->weekend);?>
<?php js::set('holders', $lang->execution->placeholder);?>
<?php js::set('errorSameProducts', $lang->execution->errorSameProducts);?>
<?php js::set('errorSameBranches', $lang->execution->errorSameBranches);?>
<?php js::set('productID', 0);?>
<?php js::set('isStage', $isStage);?>
<?php js::set('projectCommon', $lang->project->common);?>
<?php js::set('multiBranchProducts', $multiBranchProducts);?>
<?php js::set('cancelCopy', $lang->execution->cancelCopy);?>
<?php js::set('copyNoExecution', $lang->execution->copyNoExecution);?>
<?php js::set('model', isset($project->model) ? $project->model : '');?>
<?php js::set('manageProductsLang', $lang->project->manageProducts);?>
<?php js::set('manageProductPlanLang', $lang->project->manageProductPlan);?>
<div id='mainContent' class='main-content'>
  <div class='center-block'>
    <div class='main-header'>
      <h2><?php echo $lang->chproject->create;?></h2>
    </div>
    <form class='form-indicator main-form form-ajax' method='post' target='hiddenwin' id='dataform'>
      <table class='table table-form'>
        <tr>
          <th class='w-120px'><?php echo $lang->chproject->project;?></th>
          <td colspan='2'><?php echo html::select('project[]', $allProjects, '', "class='form-control chosen' multiple required");?></td>
          <td></td>
        </tr>
        <tr>
          <th class='w-120px'><?php echo $lang->execution->name;?></th>
          <td><?php echo html::input('name', '', "class='form-control' required");?></td>
          <td colspan='2'></td>
        </tr>
        <?php if(isset($config->setCode) and $config->setCode == 1):?>
        <tr>
          <th><?php echo $lang->execution->code;?></th>
          <td><?php echo html::input('code', '', "class='form-control' required");?></td><td></td><td></td>
        </tr>
        <?php endif;?>
        <tr>
          <th id='dateRange'><?php echo $lang->execution->dateRange;?></th>
          <td>
            <div class='input-group'>
              <?php echo html::input('begin', date('Y-m-d'), "class='form-control form-date' onchange='computeWorkDays()' placeholder='" . $lang->execution->begin . "' required");?>
              <span class='input-group-addon'><?php echo $lang->execution->to;?></span>
              <?php echo html::input('end', '', "class='form-control form-date' onchange='computeWorkDays()' placeholder='" . $lang->execution->end . "' required");?>
            </div>
          </td>
          <td id='dateRangeOption' colspan='2'><?php echo html::radio('delta', $lang->execution->endList , '', "onclick='computeEndDate(this.value)'");?></td>
        </tr>
        <tr>
          <th><?php echo $lang->execution->days;?></th>
          <td>
            <div class='input-group'>
              <?php echo html::input('days', '', "class='form-control'");?>
              <span class='input-group-addon'><?php echo $lang->execution->day;?></span>
            </div>
          </td>
          <td colspan='2'></td>
        </tr>
        <tr>
          <th><?php echo $lang->execution->type;?></th>
          <td><?php echo html::select('lifetime', $lang->execution->lifeTimeList, '', "class='form-control' onchange='showLifeTimeTips()'");?></td>
          <td class='muted' colspan='2'><div id='lifeTimeTips'><?php echo $lang->execution->typeDesc;?></div></td>
        </tr>
        <tr class='hide'>
          <th><?php echo $lang->execution->status;?></th>
          <td><?php echo html::hidden('status', 'wait');?></td>
          <td colspan='2'></td>
        </tr>
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
                      <span class='input-group-addon fix-border'><?php echo $lang->project->branch;?></span>
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
        <tr>
          <th><?php echo $lang->execution->teamname;?></th>
          <td><?php echo html::input('team', $team ? $team->name : '', "class='form-control'");?></td>
          <td colspan='2'></td>
        </tr>
        <tr>
          <th><?php echo $lang->execution->team;?></th>
          <td colspan='3'><?php echo html::select('teamMembers[]', $users, $team ? $team->members : [], "class='form-control picker-select' multiple"); ?></td>
        </tr>
        <tr>
          <th rowspan='2'><?php echo $lang->execution->owner;?></th>
          <td>
            <div class='input-group'>
              <span class='input-group-addon'><?php echo $lang->execution->PO;?></span>
              <?php echo html::select('PO', $poUsers, '', "class='form-control chosen'");?>
            </div>
          </td>
          <td>
            <div class='input-group'>
              <span class='input-group-addon'><?php echo $lang->execution->QD;?></span>
              <?php echo html::select('QD', $qdUsers, '', "class='form-control chosen'");?>
            </div>
          </td>
        </tr>
        <tr>
          <td>
            <div class='input-group'>
              <span class='input-group-addon'><?php echo $lang->execution->PM;?></span>
              <?php echo html::select('PM', $pmUsers, '', "class='form-control chosen'");?>
            </div>
          </td>
          <td>
            <div class='input-group'>
              <span class='input-group-addon'><?php echo $lang->execution->RD;?></span>
              <?php echo html::select('RD', $rdUsers, '', "class='form-control chosen'");?>
            </div>
          </td>
        </tr>
        <tr>
          <th><?php echo $lang->execution->desc;?></th>
          <td colspan='3'>
            <?php echo $this->fetch('user', 'ajaxPrintTemplates', 'type=execution&link=desc');?>
            <?php echo html::textarea('desc', '', "rows='6' class='form-control kindeditor' hidefocus='true'");?>
          </td>
        </tr>
        <tr>
          <th><?php echo $lang->execution->acl;?></th>
          <td colspan='3'><?php echo nl2br(html::radio('acl', $lang->execution->aclList, 'private', "onclick='setWhite(this.value);'", 'block'));?></td>
        </tr>
        <tr class="hidden" id="whitelistBox">
          <th><?php echo $lang->whitelist;?></th>
          <td colspan='2'>
            <div class='input-group'>
              <?php echo html::select('whitelist[]', $users, '', 'class="form-control picker-select" multiple');?>
              <?php echo $this->fetch('my', 'buildContactLists', "dropdownName=whitelist");?>
            </div>
          </td>
        </tr>
        <tr>
          <td colspan='4' class='text-center form-actions'>
            <?php echo html::submitButton();?>
            <?php echo html::backButton();?>
          </td>
        </tr>
      </table>
    </form>
  </div>
</div>
<?php js::set('projectID', 0);?>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
