<?php
/**
 * The control file of componant module of chandao.net.
 *
 * @copyright   Copyright 2009-2022 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZPL (http://zpl.pub/page/zplv12.html)
 * @author      wangxiaomeng <wangxiaomeng@easycorp.ltd>
 * @package     xxx
 * @version     $Id$
 * @link        https://www.chandao.net
 */
class component extends control
{
    /**
     * Product plan.
     *
     * @param  array  $products
     * @param  array  $plans
     * @param  string $defaults
     * @param  int    $colspan
     * @access public
     * @return string
     */
    public function productPlan($products = [], $plans = [], $defaults = '', $colspan = 2)
    {
        $products            = ['' => ''] + $this->loadModel('product')->getPairs();
        $multiBranchProducts = $this->product->getMultiBranchPairs();

        $html = '';

        if($defaults)
        {
            $defaults     = json_decode($defaults, true);
            $branchGroups = $this->loadModel('branch')->getByProducts(array_column($defaults, 'products'), 'noclosed');

            $i = 0;
            foreach($defaults as $default)
            {
                $product      = $this->product->getById($default['products']);
                $productPlans = $this->loadModel('productplan')->getPairs($product->id, '', 'noclosed', true);

                $html .= '<tr>';
                $html .= '<th>';

                if($i == 0) $html .= $this->lang->project->manageProductPlan;

                $html .= '</th>';
                $html .= '<td class="text-left productsBox" colspan="' . $colspan . '">';
                $html .= '<div class="row">';
                $html .= '<div class="col-sm-6">';
                $html .= '<div class="table-row">';
                $html .= '<div class="table-col">';

                $hasBranch = !empty($product) && $product->type != 'normal' && isset($branchGroups[$product->id]) ? ' has-branch' : '';

                $html .= '<div class="input-group' . $hasBranch . '">';
                $html .= '<span class="input-group-addon">' . $this->lang->productCommon . '</span>';

                $productID   = !empty($product) ? $product->id   : '';
                $productType = !empty($product) ? $product->type : '';

                $html .= html::select("products[$i]", $products, $productID, "class='form-control chosen' onchange='loadBranches(this)' data-last='" . $productID . "' data-type='" . $productType . "'");
                $html .= '</div>';
                $html .= '</div>';
                $html .= '<div class="table-col ' . ($hasBranch ? '' : 'hidden') . '">';
                $html .= '<div class="input-group required">';
                $html .= '<span class="input-group-addon fix-border">' . $this->lang->product->branchName['branch'] . '</span>';

                $branchIdList = isset($default['branch']) ? $default['branch'] : '';

                $html .= html::select("branch[$i][]", isset($branchGroups[$productID]) ? $branchGroups[$productID] : [], $branchIdList, "class='form-control chosen' multiple onchange=\"loadPlans('#products{$i}', this)\"");
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '<div class="col-sm-6">';
                $html .= '<div class="input-group" id="plan' . $i . '">';
                $html .= '<span class="input-group-addon">' . $this->lang->product->plan . '</span>';
                $html .= html::select("plans[$product->id][]", $productPlans, isset($default['plans']) ? $default['plans'] : [], "class='form-control chosen' multiple");
                $html .= '<div class="input-group-btn">';
                $html .= '<a href="javascript:;" onclick="addNewLine(this)" class="btn btn-link addLine"><i class="icon-plus"></i></a>';

                $visibility = $i == 0 ? 'style="visibility: hidden"' : '';
                $html .= '<a href="javascript:;" onclick="removeLine(this)" class="btn btn-link removeLine"' . $visibility . '><i class="icon-close"></i></a>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';

                $html .= '</td>';
                $html .= '</tr>';
                $i++;
            }
        }
        else
        {
            $html .= '<tr>';
            $html .= '<th id="productTitle">' . $this->lang->project->manageProductPlan . '</th>';
            $html .= '<td class="text-left productsBox" colspan="' . $colspan . '">';
            $html .= '<div class="row">';
            $html .= '<div class="col-sm-6">';
            $html .= '<div class="table-row">';
            $html .= '<div class="table-col">';
            $html .= '<div class="input-group required">';
            $html .= '<span class="input-group-addon">' . $this->lang->productCommon . '</span>';
            $html .= html::select('products[0]', $products, '', "class='form-control chosen' onchange='loadBranches(this)'");
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="table-col hidden">';
            $html .= '<div class="input-group required">';
            $html .= '<span class="input-group-addon fix-border">' . $this->lang->project->branch . '</span>';
            $html .= html::select('branch[0][]', '', '', "class='form-control chosen' multiple");
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="col-sm-6">';
            $html .= '<div class="input-group" id="plan0">';
            $html .= '<span class="input-group-addon">' . $this->lang->product->plan . '</span>';
            $html .= html::select('plans[][]', '', '', "class='form-control chosen' multiple");
            $html .= '<div class="input-group-btn">';
            $html .= '<a href="javascript:;" onclick="addNewLine(this)" class="btn btn-link addLine"><i class="icon-plus"></i></a>';
            $html .= '<a href="javascript:;" onclick="removeLine(this)" class="btn btn-link removeLine" style="visibility: hidden"><i class="icon-close"></i></a>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= <<<EOT
<script>
var multiBranchProducts = $multiBranchProducts;
</script>
EOT;
        $html .= <<<'EOT'
<script>
function loadBranches(product)
{
    /* When selecting a product, delete a plan that is empty by default. */
    $("#planDefault").remove();

    var chosenProducts = [];
    $(".productsBox select[name^='products']").each(function()
    {
        var $product  = $(product);
        var productID = $(this).val();
        if(productID > 0 && chosenProducts.indexOf(productID) == -1) chosenProducts.push(productID);
        if($product.val() != 0 && $product.val() == $(this).val() && $product.attr('id') != $(this).attr('id') && !multiBranchProducts[$product.val()])
        {
            bootbox.alert(errorSameProducts);
            $product.val(0);
            $product.trigger("chosen:updated");
            return false;
        }
    });

    (chosenProducts.length > 1 && (model == 'waterfall' || model == 'waterfallplus')) ? $('.division').removeClass('hide') : $('.division').addClass('hide');

    var $tableRow = $(product).closest('.table-row');
    var index     = $tableRow.find('select:first').attr('id').replace('products' , '');
    var oldBranch = $(product).attr('data-branch') !== undefined ? $(product).attr('data-branch') : 0;
    if($(product).val() != 0)
    {
        $(product).closest('tr').find('.newProduct').addClass('hidden')
    }
    else
    {
        $(product).closest('tr').find('.newProduct').removeClass('hidden')
    }

    if(!multiBranchProducts[$(product).val()])
    {
        $tableRow.find('.table-col:last select').val('').trigger('chosen:updated');
        $tableRow.find('.table-col:last').addClass('hidden');
    }

    $.get(createLink('branch', 'ajaxGetBranches', "productID=" + $(product).val() + "&oldBranch=" + oldBranch + "&param=active"), function(data)
    {
        if(data)
        {
            $tableRow.find("select[name^='branch']").replaceWith(data);
            $tableRow.find('.table-col:last .chosen-container').remove();
            $tableRow.find('.table-col:last').removeClass('hidden');
            $tableRow.find("select[name^='branch']").attr('multiple', '').attr('name', 'branch[' + index + '][]').attr('id', 'branch' + index).attr('onchange', "loadPlans('#products" + index + "', this)").chosen();

            disableSelectedProduct();
        }

        var branch = $('#branch' + index);
        loadPlans(product, branch);
    });
}
function loadPlans(product, branch)
{
    var productID = $(product).val();
    var branchID  = $(branch).val() == null ? 0 : '0,' + $(branch).val();
    var planID    = $(product).attr('data-plan') !== undefined ? $(product).attr('data-plan') : 0;
    var index     = $(product).attr('id').replace('products', '');

    $.get(createLink('product', 'ajaxGetPlans', "productID=" + productID + '&branch=' + branchID + '&planID=' + planID + '&fieldID&needCreate=&expired=unexpired,noclosed&param=skipParent,multiple'), function(data)
    {
        if(data)
        {
            $("div#plan" + index).find("select[name^='plans']").replaceWith(data);
            $("div#plan" + index).find('.chosen-container').remove();
            $("div#plan" + index).find('select').attr('name', 'plans[' + productID + ']' + '[]').attr('id', 'plans' + productID).chosen();
        }
    });
}
function loadPlans(product, branch)
{
    var productID = $(product).val();
    var branchID  = $(branch).val() == null ? 0 : '0,' + $(branch).val();
    var planID    = $(product).attr('data-plan') !== undefined ? $(product).attr('data-plan') : 0;
    var index     = $(product).attr('id').replace('products', '');

    $.get(createLink('product', 'ajaxGetPlans', "productID=" + productID + '&branch=' + branchID + '&planID=' + planID + '&fieldID&needCreate=&expired=unexpired,noclosed&param=skipParent,multiple'), function(data)
    {
        if(data)
        {
            $("div#plan" + index).find("select[name^='plans']").replaceWith(data);
            $("div#plan" + index).find('.chosen-container').remove();
            $("div#plan" + index).find('select').attr('name', 'plans[' + productID + ']' + '[]').attr('id', 'plans' + productID).chosen();
        }
    });
}
function addNewLine(obj)
{
    var newLine = $(obj).closest('tr').clone();
    var index   = 0;
    $(".productsBox select[name^='products']").each(function()
    {
        var id = $(this).attr('id').replace('products' , '');

        id = parseInt(id);
        id ++;

        index = id > index ? id : index;
    })

    newLine.find('.newProduct').remove();
    newLine.find('.addProduct').remove();
    newLine.addClass('newLine');
    newLine.find('th').html('');
    newLine.find('.removeLine').css('visibility', 'visible');
    newLine.find('.chosen-container').remove();
    newLine.find('.productsBox .table-col:last').addClass('hidden');
    newLine.find("select[name^='products']").attr('name', 'products[' + index + ']').attr('id', 'products' + index).val('').chosen();
    newLine.find("select[name^='plans']").attr('name', 'plans[' + index + '][' + 0 + '][]').chosen();
    newLine.find("div[id^='plan']").attr('id', 'plan' + index);

    $(obj).closest('tr').after(newLine);
    var product = newLine.find("select[name^='products']");
    var branch  = newLine.find("select[name^='branch']");
    loadPlans(product, branch);
    disableSelectedProduct();
}
function removeLine(obj)
{
    $(obj).closest('tr').remove();
    disableSelectedProduct();
    if($("select[name^='products']").length < 2) $('.division').addClass('hide');
}
</script>
EOT;

        echo $html;
    }
}
