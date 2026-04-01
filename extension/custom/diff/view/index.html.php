<?php include $this->app->getModuleRoot() . 'common/view/header.lite.html.php'; ?>
<!-- 修复 Bootstrap 链接 -->
<link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
<style>
    body {
        font-size: 14px;
    }
    /* 新增完整的样式定义 */
    .container {
        max-width: 1000px;
        padding: 20px;
    }

    .diff-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        margin: 20px 0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .diff-table th {
        background: #f8f9fa;
        padding: 12px;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
    }

    .diff-table td {
        padding: 12px;
        border-bottom: 1px solid #eee;
        vertical-align: top;
    }

    .diff-table tr:last-child td {
        border-bottom: none;
    }

    .version-header {
        color: #6c757d;
        margin-bottom: 15px;
    }

    .diff-change {
        padding: 2px 4px;
        border-radius: 3px;
        display: inline-block;
    }

    .del {
        background: #ffeef0 !important;
        color: #dc3545;
        text-decoration: line-through;
    }

    .add {
        background: #e6ffec !important;
        color: #28a745;
    }

    .test-case-item {
        margin: 8px 0;
        padding: 10px;
        border: 1px solid #dee2e6;
        border-radius: 6px;
    }

    .badge {
        font-weight: 500;
        padding: 6px 10px;
        border-radius: 12px;
        margin-right: 12px;
    }

    .changed-field {
        margin-top: 5px;
        padding: 5px;
        border-radius: 4px;
        background-color: #f8f9fa;
    }

    .field-label {
        color: #6c757d;
        font-size: 0.85em;
        margin-left: 5px;
    }

    /* 子表样式 */
    .child-table-container {
        margin-bottom: 30px;
    }
    
    .added-row {
        background-color: rgba(40, 167, 69, 0.05);
    }
    
    .deleted-row {
        background-color: rgba(220, 53, 69, 0.05);
        text-decoration: line-through;
        color: #6c757d;
    }
    
    .changed-row {
        background-color: rgba(255, 193, 7, 0.05);
    }
    
    .changed-cell {
        background-color: rgba(255, 193, 7, 0.1);
    }
    
    .field-change {
        padding: 3px;
        border-radius: 3px;
    }

    /* 响应式处理 */
    @media (max-width: 768px) {
        .diff-table {
            display: block;
            overflow-x: auto;
        }
    }

    .child-table-section {
        margin-bottom: 30px;
    }
    
    .child-table-title {
        margin: 15px 0;
        font-size: 18px;
        color: #333;
    }
    
    .child-diff-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .child-diff-table th {
        background-color: #f5f5f5;
        padding: 8px;
        text-align: left;
        font-weight: bold;
    }
    
    .child-diff-table td {
        padding: 8px;
        border: 1px solid #ddd;
    }
    
    /* 状态样式 */
    .status-added {
        background-color: rgba(40, 167, 69, 0.1);
    }
    
    .status-changed {
        background-color: rgba(255, 193, 7, 0.1);
    }
    
    .status-deleted {
        background-color: rgba(220, 53, 69, 0.05);
        text-decoration: line-through;
        color: #999;
    }
    
    .changed-field {
        background-color: rgba(220, 53, 69, 0.1);
    }
    
    .section-divider {
        height: 1px;
        background-color: #eee;
        margin: 30px 0;
    }
    
    .section-title {
        margin-bottom: 20px;
        font-size: 22px;
    }
</style>
<div class="container">
    <!-- 版本头 -->
    <div class="version-header">
        <span>ID: <?php echo $objectID;?></span>
        <span class="mx-2">|</span>
        <span>对比版本: v<?php echo $secondVersion; ?> → v<?php echo $firstVersion; ?></span>
    </div>

    <!-- 基本信息对比表格 -->
    <h4 class="mt-4 mb-3">字段变更</h4>
    <table class="diff-table">
        <thead>
            <tr>
                <th style="width: 10%">属性</th>
                <th style="width: 40%">版本 <?php echo $secondVersion; ?></th>
                <th style="width: 40%">版本 <?php echo $firstVersion; ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($diffData as $field => $data): ?>
            <?php if($data['diff']): ?>
            <tr>
              <td style="width: 10%;">
                <?php echo isset($fields[$field]) ? $fields[$field]->name : $data['name'];?>
              </td>
              <td style="width: 40%;">
                <span class="diff-change del"><?php echo $data['second']; ?></span>
              </td>
              <td style="width: 40%;">
                <span class="diff-change add"><?php echo $data['first']; ?></span>
              </td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- 子表变更 -->
    <?php if(!empty($childrenDiff)): ?>
    <div class='panel'>
      <div class='panel-heading'><strong><?php echo $lang->diff->childrenTitle;?></strong></div>
      <div class='panel-body'>
        <?php foreach($childrenDiff as $key => $changes): ?>
        <?php $moduleType = str_replace('sub_', '', $key); ?>
        <?php $moduleFields = $childFields[$key]; ?>
        <div class='table-responsive'>
          <h3><?php echo $lang->diff->$moduleType;?></h3>
          <?php if(!empty($changes['added']) || !empty($changes['modified']) || !empty($changes['deleted']) || !empty($changes['unchanged'])): ?>
          <table class='table table-bordered table-hover'>
            <thead>
              <tr>
                <?php foreach($moduleFields as $field): ?>
                <?php if(!$field->show) continue; ?>
                <th><?php echo $field->name; ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <!-- 显示未变化的数据 -->
              <?php foreach($changes['unchanged'] as $item): ?>
              <tr class="unchanged">
                <?php foreach($moduleFields as $fieldName => $childField): ?>
                <?php if(!$childField->show) continue; ?>
                <td>
                <?php
                if(strpos(',date,datetime,', ",$childField->control,") !== false)
                {
                    $childValue = formatTime($item->{$childField->field});
                }
                else
                {
                    if(is_array($item->{$childField->field}))
                    {
                        $childValues = array();
                        foreach($item->{$childField->field} as $value)
                        {
                            if(!empty($value)) $childValues[] = zget($childField->options, $value);
                        }
                        $childValue = implode(',', $childValues);
                    }
                    else
                    {
                      $childValue = '';
                      if(is_array($item->{$childField->field}))
                      {
                        foreach($item->{$childField->field} as $multItem)
                        {
                          $childValue .= zget($childField->options, $multItem). '    ';
                        }
                      }
                      else
                      {
                        $childValue = zget($childField->options, $item->{$childField->field});
                      }
                    }
                }
                echo $childValue;
                ?>
                </td>
                <?php endforeach; ?>
              </tr>
              <?php endforeach; ?>
              <!-- 显示修改的数据 -->
              <?php foreach($changes['modified'] as $item): ?>
              <tr class="modified">
                <?php foreach($moduleFields as $fieldName => $childField): ?>
                <?php if(!$childField->show) continue; ?>
                <td class="text-danger">
                  <?php
                  if(strpos(',date,datetime,', ",$childField->control,") !== false)
                  {
                      $childValue = formatTime($item->{$childField->field});
                  }
                  else
                  {
                      if(is_array($item->{$childField->field}))
                      {
                          $childValues = array();
                          foreach($item->{$childField->field} as $value)
                          {
                              if(!empty($value)) $childValues[] = zget($childField->options, $value);
                          }
                          $childValue = implode(',', $childValues);
                      }
                      else
                      {
                        $childValue = '';
                        if(is_array($item->{$childField->field}))
                        {
                            foreach($item->{$childField->field} as $multItem)
                            {
                            $childValue .= zget($childField->options, $multItem). '    ';
                            }
                        }
                        else
                        {
                            $childValue = zget($childField->options, $item->{$childField->field});
                        }
                      }
                  }
                  ?>
                  <?php echo $childValue;?>
                </td>
                <?php endforeach; ?>
              </tr>
              <?php endforeach; ?>
              
              <!-- 显示新增的数据 -->
              <?php foreach($changes['added'] as $item): ?>
              <tr class="added">
                <?php foreach($moduleFields as $fieldName => $childField): ?>
                <?php if(!$childField->show) continue; ?>
                <td class="text-success">
                    <?php
                  if(strpos(',date,datetime,', ",$childField->control,") !== false)
                  {
                      $childValue = formatTime($item->{$childField->field});
                  }
                  else
                  {
                      if(is_array($item->{$childField->field}))
                      {
                          $childValues = array();
                          foreach($item->{$childField->field} as $value)
                          {
                              if(!empty($value)) $childValues[] = zget($childField->options, $value);
                          }
                          $childValue = implode(',', $childValues);
                      }
                      else
                      {
                        $childValue = '';
                        if(is_array($item->{$childField->field}))
                        {
                            foreach($item->{$childField->field} as $multItem)
                            {
                            $childValue .= zget($childField->options, $multItem). '    ';
                            }
                        }
                        else
                        {
                            $childValue = zget($childField->options, $item->{$childField->field});
                        }
                      }
                  }
                  ?>
                  <?php echo $childValue;?>
                </td>
                <?php endforeach; ?>
              </tr>
              <?php endforeach; ?>
              
              <!-- 显示删除的数据 -->
              <?php foreach($changes['deleted'] as $item): ?>
              <tr class="deleted">
                <?php foreach($moduleFields as $fieldName => $childField): ?>
                <?php if(!$childField->show) continue; ?>
                <td>
                  <del>
                  <?php 
                  if(strpos(',date,datetime,', ",$childField->control,") !== false)
                  {
                      $childValue = formatTime($item->{$childField->field});
                  }
                  else
                  {
                      $childValue = '';
                      if(is_array($item->{$childField->field}))
                      {
                        foreach($item->{$childField->field} as $multItem)
                        {
                          $childValue .= zget($childField->options, $multItem). '    ';
                        }
                      }
                      else
                      {
                        $childValue = zget($childField->options, $item->{$childField->field});
                      }
                  }
                  ?>
                  <?php echo $childValue;?>
                  </del>
                </td>
                <?php endforeach; ?>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif;?>
        </div>
        <?php endforeach;?>
      </div>
    </div>
    <?php endif; ?>
</div>
<?php include $this->app->getModuleRoot() . 'common/view/footer.lite.html.php'; ?>