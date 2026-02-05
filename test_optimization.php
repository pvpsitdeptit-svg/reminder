<?php
require_once 'includes/AdvancedResourcePoolManagement.php';

$rpm = new AdvancedResourcePoolManagement();
$result = $rpm->optimizeResourcePools();

echo "Resource Optimization Test Results:\n";
echo "Optimizations Applied: " . count($result['optimizations_applied']) . "\n";
echo "Improvement Metrics: " . count($result['improvement_metrics']) . "\n";
echo "Status: " . $result['status'] . "\n";

if (!empty($result['optimizations_applied'])) {
    echo "\nApplied Optimizations:\n";
    foreach ($result['optimizations_applied'] as $opt) {
        echo "- " . $opt['description'] . "\n";
    }
}

if (!empty($result['improvement_metrics'])) {
    echo "\nImprovement Metrics:\n";
    foreach ($result['improvement_metrics'] as $resource => $metrics) {
        echo "- {$resource}: " . json_encode($metrics) . "\n";
    }
}
?>
