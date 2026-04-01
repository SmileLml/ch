<?php
$html  = '';
$html .= "<div class='col-sm-12'>";

$children = array();
foreach($fields as $field)
{
    if(!$field->show) continue;
    if($field->position != 'info') continue;

    if(isset($childFields[$field->field]))
    {
        $children[$field->field] = $field->name;
        continue;
    }
}

foreach($children as $child => $childName)
{
    if(empty($childDatas[$child])) continue;

    $html .= "<div class='panel'>";
    $html .= "<div class='panel-body'>";
    $html .= "<div class='panel panel-block'>";
    $html .= "<div class='panel-heading'><strong>{$childName}</strong></div>";
    $html .= "<div class='panel-body scroll'>";
    $html .= "<table class='table table-hover table-fixed'>";
    $html .= "<thead>";
    $html .= "<tr>";

    foreach($childFields[$child] as $childField)
    {
        if(!$childField->show) continue;
        $childWidth = ($childField->width && $childField->width != 'auto' ? $childField->width . 'px' : 'auto');
        $html .= "<th style='width: {$childWidth}'>{$childField->name}</th>";
    }

    $html .= "</tr>";
    $html .= "</thead>";
    $html .= "<tbody>";

    foreach($childDatas[$child] as $childData)
    {
        $html .= "<tr>";

        foreach($childFields[$child] as $childField)
        {
            if(!$childField->show) continue;

            if(strpos(',date,datetime,', ",$childField->control,") !== false)
            {
                $childValue = formatTime($childData->{$childField->field});
            }
            else
            {
                if(is_array($childData->{$childField->field}))
                {
                    $childValues = array();
                    foreach($childData->{$childField->field} as $value)
                    {
                        if(!empty($value)) $childValues[] = zget($childField->options, $value);
                    }
                    $childValue = implode(',', $childValues);
                }
                else
                {
                    $childValue = zget($childField->options, $childData->{$childField->field});
                }
            }

            $html .= "<td title='{$childValue}'>{$childValue}</td>";
        }

        $html .= "</tr>";
    }

    $html .= "</tbody>";
    $html .= "</table>";
    $html .= "</div>";
    $html .= "</div>";
    $html .= "</div>";
    $html .= "</div>";
}

$html .= "</div>";

$fileHtml  = '';

if($project->files)
{
    $fileHtml .= '<div class="col-sm-12">';
    $fileHtml .= '<div class="panel">';
    $fileHtml .= $this->fetch('file', 'printFiles', array('files' => $project->files, 'fieldset' => 'true', 'object' => $project, 'method' => 'view', 'showDelete' => true));
    $fileHtml .= '</div>';
    $fileHtml .= '</div>';
}
?>

<script>
var html = <?php echo json_encode($html);?>;
$('#mainContent > div.col-8.main-col > div.row > div:nth-child(2)').after(html);

var fileHtml = <?php echo json_encode($fileHtml);?>;
$('.histories').parent().before(fileHtml);
</script>
