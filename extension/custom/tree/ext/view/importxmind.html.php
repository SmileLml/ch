<?php include $app->getModuleRoot() . 'common/view/header.lite.html.php';?>
<main id="main">
  <div class="container">
    <div id="mainContent" class='main-content'>
      <div class='main-header'>
        <h2><?php echo $lang->tree->importXmind;?></h2>
      </div>
      <form method='post' enctype='multipart/form-data' target='hiddenwin' style="padding: 0px 3% 20px;">
      <table class='table table-form w-p100'>
        <tr>
          <td align='center'>
            <input type='file' name='file' class='form-control'/>
          </td>
          <td class="w-100px">
            <?php echo html::submitButton();?>
          </td>
        </tr>
      </table>
      </form>
    </div>
  </div>
</main>
<?php include $app->getModuleRoot() . 'common/view/footer.lite.html.php';?>
