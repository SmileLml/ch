<?php
$executionProductPairs = [];
if($this->app->tab == 'chteam')
{
    $executionIdList = array_keys($executions);
    $executionList   = $this->loadModel('execution')->getByIdList($executionIdList);
    $productList     = $this->loadModel('product')->getProducts($executionIdList);

    $executionProductList = [];
    foreach($productList as $product)
    {
        $executionProductList[$product->id] = $product;
    }

    $executionProductPairs = $this->dao->select('project,product')->from(TABLE_PROJECTPRODUCT)->where('project')->in($executionIdList)->fetchPairs('project', 'product');

    $productList = [];
    foreach($products as $key => $val)
    {
        $productList[] = [
            'id'   => $key,
            'name' => $val
        ];
    }
    js::set('productList', $productList);
    js::set('chprojectID', $chprojectID);
}
?>

<?php if($this->app->tab == 'chteam'):?>
<script>
var executionProductList  = <?php echo json_encode($executionProductList);?>;
var executionProductPairs = <?php echo json_encode($executionProductPairs);?>;

function getProductSelectHtml(productID)
{
    var html = '<select name="product" id="product" class="form-control chosen chosen-controled" onchange="loadProductRelated()">';
    $.each(productList, function(i, n){
        var selectHtml = productID == n.id ? ' selected="selected" ' : '';
        html += '<option value="' + n.id + '" ' + selectHtml + ' title="' + n.name + '">' + n.name + '</option>';
    });
    html += '</select>';
    return html;
}

function getProductInputHtml(productID)
{
    return '<input type="text" name="product" id="product" value="' + productID + '" class="form-control" onchange="loadTestReports(this.value)" autocomplete="off">';
}

function loadProductRelated()
{
    loadTestReports($('#product').val());
    loadExecutionBuilds($('#execution').val())
}

$('#execution').change(function(){
    executionID = $(this).val();
    productID   = executionProductPairs[executionID];
    product     = executionProductList[productID];

    if(product.shadow == 1)
    {
        productHtml = getProductInputHtml(productID);
        $('#product_chosen').remove();
        $('#product').replaceWith(productHtml);
        $('#product').parents('tr').addClass('hide');
    }
    else
    {
        productHtml = getProductSelectHtml(productID);
        $('#product_chosen').remove();
        $('#product').replaceWith(productHtml);
        $('#product').chosen();
        $('#product').parents('tr').removeClass('hide');
    }
});

function loadExecutions(productID)
{
    var executionID = $('#execution').val();
    var link        = createLink('product', 'ajaxGetExecutions', 'productID=' + productID + '&projectID=' + projectID + '&branch=&number=&executionID=0&from=&mode=&chprojectID=' + chprojectID);

    $.get(link, function(data)
    {
        if(!data) data = '<select id="execution" name="execution" class="form-control"></select>';
        $('#execution').replaceWith(data);
        $('#execution_chosen').remove();
        $("#execution").val(executionID);
        $("#execution").chosen();
    });
}
</script>
<?php endif;?>
