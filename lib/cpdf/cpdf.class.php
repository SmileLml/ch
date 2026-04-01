<?php
require dirname(__FILE__) . '/autoload.php';
use Mpdf\Mpdf;

/**
 * mpdf class for generating PDF documents
 */
class cpdf
{
    /**
     * Constructor
     * Initializes mPDF object with default settings
     */
    public function __construct()
    {
        global $app;
        // $this->mpdf = new Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'orientation' => 'P', 'tempDir' => $app->getTmpRoot(), 'autoScriptToLang' => true, 'autoLangToFont' => true]);
        $this->mpdf = new Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'default_font' => 'msyh','memoryLimit' => '512M','tempDir' => '/tmp/to/tempdir' ]);
    }

    public function getMpdf()
    {
        return $this->mpdf;
    }
}

// $mpdf->SetAutoPageBreak(true, 10); // 设置自动分页，边距为10毫米

// foreach ($data as $table) {
//     // 添加表格标题
//     $mpdf->WriteHTML('<h2 style="text-align:center;">' . $table['title'] . '</h2>');
//     //$mpdf->AddPage(); // 如果需要在每个表格前都分页，可以取消注释这行代码（注意：这会导致每个表格都在新页上）

//     // 开始表格
//     $mpdf->WriteHTML('<table border="1" cellpadding="5" cellspacing="0" style="width:100%;">');

//     // 表格头部（如果有的话）
//     if (!empty($table['rows']) && is_array($table['rows'][0])) {
//         $headerRow = $table['rows'][0];
//         $mpdf->WriteHTML('<tr>');
//         foreach ($headerRow as $headerCell) {
//             $mpdf->WriteHTML('<th>' . htmlspecialchars($headerCell) . '</th>');
//         }
//         $mpdf->WriteHTML('</tr>');

//         // 表格数据行
//         array_shift($table['rows']); // 移除已经作为头部的第一行
//         foreach ($table['rows'] as $row) {
//             $mpdf->WriteHTML('<tr style="height:auto;">'); // 设置行高为自适应
//             foreach ($row as $cell) {
//                 $mpdf->WriteHTML('<td>' . htmlspecialchars($cell) . '</td>');
//             }
//             $mpdf->WriteHTML('</tr>');
//         }
//     }

//     // 结束表格
//     $mpdf->WriteHTML('</table>');

//     // 如果不希望每个表格都在新页上，可以注释掉上面的 AddPage() 调用，并在这里添加分页逻辑（例如，根据表格数量或数据大小）
//     // $mpdf->AddPage(); // 根据需要添加分页逻辑
// }

// $mpdf->Output('tables.pdf', 'I'); // 输出PDF文件
