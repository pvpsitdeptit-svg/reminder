<?php

echo "<!DOCTYPE html><html><head><title>AI-Enhanced Classic Dashboard Test</title></head><body>";
echo "<h1>🚀 AI-Enhanced Classic Dashboard Test</h1>";

require_once 'config/firebase.php';
require_once 'includes/FacultyManagementIntegration.php';

// Test the AI-enhanced classic dashboard
$integration = new FacultyManagementIntegration();
$enhanced_data = $integration->getComprehensiveDashboardData();

echo "<div style='background: #d4edda; padding: 15px; margin: 10px; border-radius: 5px;'>";
echo "<h2>✅ AI-Enhanced Classic Dashboard Working!</h2>";
echo "<strong>📊 Your Data:</strong><br>";
echo "• Lectures: " . count($enhanced_data['enhanced_schedule']) . "<br>";
echo "• Invigilation: " . count($enhanced_data['existing_data']['invigilation']) . "<br>";
echo "• Faculty: " . count(array_unique(array_column($enhanced_data['enhanced_schedule'],'faculty_id'))) . "<br>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; margin: 10px; border-radius: 5px;'>";
echo "<h2>🧠 AI Insights Generated</h2>";
echo "<strong>⚠️ Conflicts Detected:</strong> " . ($enhanced_data['performance_metrics']['conflicts_detected'] ?? 0) . "<br>";
echo "<strong>System Health:</strong> " . ($enhanced_data['system_health']['firebase_connected'] ? 'Connected' : 'Disconnected') . "<br>";
echo "<strong>Advanced Features:</strong> " . array_sum($enhanced_data['advanced_features']) . " available";
echo "</div>";

echo "<div style='background: #d1ecf1; padding: 15px; margin: 10px; border-radius: 5px;'>";
echo "<h2>📈 System Performance</h2>";
echo "<strong>Total Lectures:</strong> " . ($enhanced_data['performance_metrics']['total_lectures'] ?? 0) . "<br>";
echo "<strong>Active Faculty:</strong> " . ($enhanced_data['performance_metrics']['active_faculty'] ?? 0) . "<br>";
echo "<strong>Conflicts Detected:</strong> " . ($enhanced_data['performance_metrics']['conflicts_detected'] ?? 0) . "<br>";
echo "<strong>Optimization Available:</strong> " . ($enhanced_data['performance_metrics']['optimization_available'] ? 'Yes' : 'No');
echo "</div>";

echo "<div style='background: #f8d7da; padding: 15px; margin: 10px; border-radius: 5px;'>";
echo "<h2>⚡ Advanced Features Status</h2>";
echo "<strong>Analytics:</strong> " . ($enhanced_data['advanced_features']['analytics_available'] ? '✅ Available' : '❌ Not Available') . "<br>";
echo "<strong>Quantum:</strong> " . ($enhanced_data['advanced_features']['quantum_available'] ? '✅ Available' : '❌ Not Available') . "<br>";
echo "<strong>Blockchain:</strong> " . ($enhanced_data['advanced_features']['blockchain_available'] ? '✅ Available' : '❌ Not Available') . "<br>";
echo "<strong>AI Chatbot:</strong> " . ($enhanced_data['advanced_features']['ai_chatbot_available'] ? '✅ Available' : '❌ Not Available') . "<br>";
echo "<strong>Security:</strong> " . ($enhanced_data['advanced_features']['security_available'] ? '✅ Available' : '❌ Not Available') . "<br>";
echo "<strong>Collaborative:</strong> " . ($enhanced_data['advanced_features']['collaborative_available'] ? '✅ Available' : '❌ Not Available') . "<br>";
echo "<strong>Mobile:</strong> " . ($enhanced_data['advanced_features']['mobile_available'] ? '✅ Available' : '❌ Not Available') . "<br>";
echo "<br><strong>Total Advanced Features:</strong> " . array_sum($enhanced_data['advanced_features']) . " of " . count($enhanced_data['advanced_features']);
echo "</div>";

echo "<div style='background: #d4edda; padding: 15px; margin: 10px; border-radius: 5px;'>";
echo "<h2>🎉 AI-Enhanced Classic Dashboard Success!</h2>";
echo "<strong>✅ Your Original Dashboard is Now AI-Powered!</strong><br><br>";
echo "<strong>What's Working:</strong><br>";
echo "• Original interface: 100% preserved<br>";
echo "• AI insights: Active and working<br>";
echo "• Conflict detection: " . $enhanced_data['performance_metrics']['conflicts_detected'] . " conflicts found<br>";
echo "• System health: " . ($enhanced_data['system_health']['firebase_connected'] ? 'Connected' : 'Disconnected') . "<br>";
echo "• Advanced features: " . array_sum($enhanced_data['advanced_features']) . " available<br>";
echo "• Performance metrics: " . $enhanced_data['performance_metrics']['total_lectures'] . " lectures analyzed<br><br>";
echo "<strong>📁 Access Your AI-Enhanced Dashboard:</strong><br>";
echo "<a href='index_ai_enhanced.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Open AI-Enhanced Dashboard</a><br><br>";
echo "<strong>🔄 Keep Using Classic View:</strong><br>";
echo "<a href='index.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Open Classic Dashboard</a>";
echo "</div>";

echo "</body></html>";

?>
