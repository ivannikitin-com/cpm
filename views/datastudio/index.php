<?php 
$dataStudio = CPM_DataStudio::getInstance();
$report = $dataStudio->getReport();
$reportTabName = ( isset( $report['title'] ) ) ? $report['title'] : 'DataStudio';
cpm_get_header( $reportTabName, $dataStudio->getProjectId() ); ?>

<?php if ( $report ): ?>
    
<iframe 
    src="<?php echo $report['code']?>" 
    width="<?php echo $report['width']?>" 
    height="<?php echo $report['height']?>" 
    frameborder="0" 
    style="border:0" 
    allowfullscreen></iframe>    

<?php else: ?>

    <h1>Настройки отчета не найдены!</h1>

<?php endif ?>
