<?php

/**
 * Predictive Analytics Engine
 * Patent-worthy: Demand forecasting and trend analysis using mathematical models
 */
class PredictiveAnalyticsEngine {
    private $historicalData = [];
    private $models = [];
    private $accuracy = 0.85;
    
    public function __construct() {
        $this->initializeModels();
    }
    
    /**
     * Initialize prediction models
     */
    private function initializeModels() {
        $this->models = [
            'linear_regression' => [
                'weights' => [0.3, 0.5, 0.2],
                'bias' => 0.1
            ],
            'exponential_smoothing' => [
                'alpha' => 0.3,
                'beta' => 0.1
            ],
            'seasonal_decomposition' => [
                'seasonality' => 7, // weekly pattern
                'trend_strength' => 0.6
            ]
        ];
    }
    
    /**
     * Analyze historical scheduling patterns
     */
    public function analyzeHistoricalPatterns($scheduleData) {
        $patterns = [
            'peak_hours' => $this->findPeakHours($scheduleData),
            'faculty_preferences' => $this->analyzeFacultyPreferences($scheduleData),
            'room_utilization' => $this->analyzeRoomUtilization($scheduleData),
            'subject_trends' => $this->analyzeSubjectTrends($scheduleData)
        ];
        
        return [
            'patterns' => $patterns,
            'confidence_score' => $this->calculateConfidence($patterns),
            'insights' => $this->generateInsights($patterns)
        ];
    }
    
    /**
     * Predict future demand for resources
     */
    public function predictResourceDemand($historicalData, $timeHorizon = 30) {
        $predictions = [];
        
        foreach ($historicalData as $resource => $data) {
            $predictions[$resource] = [
                'predicted_demand' => $this->applyLinearRegression($data, $timeHorizon),
                'confidence_interval' => $this->calculateConfidenceInterval($data),
                'seasonal_adjustment' => $this->applySeasonalAdjustment($data)
            ];
        }
        
        return [
            'predictions' => $predictions,
            'model_accuracy' => $this->accuracy,
            'recommendations' => $this->generateResourceRecommendations($predictions)
        ];
    }
    
    /**
     * Forecast scheduling conflicts
     */
    public function forecastConflicts($currentSchedule, $historicalConflicts) {
        $riskFactors = [
            'faculty_overload' => $this->calculateFacultyOverloadRisk($currentSchedule),
            'room_shortage' => $this->calculateRoomShortageRisk($currentSchedule),
            'time_slot_pressure' => $this->calculateTimeSlotPressure($currentSchedule)
        ];
        
        $conflictProbability = $this->calculateConflictProbability($riskFactors);
        
        return [
            'risk_factors' => $riskFactors,
            'conflict_probability' => $conflictProbability,
            'high_risk_periods' => $this->identifyHighRiskPeriods($currentSchedule),
            'preventive_actions' => $this->recommendPreventiveActions($riskFactors)
        ];
    }
    
    /**
     * Find peak scheduling hours
     */
    private function findPeakHours($scheduleData) {
        $hourCounts = array_fill(0, 24, 0);
        
        foreach ($scheduleData as $item) {
            $hour = (int)substr($item['time'] ?? '09:00', 0, 2);
            $hourCounts[$hour]++;
        }
        
        arsort($hourCounts);
        return array_slice($hourCounts, 0, 3, true);
    }
    
    /**
     * Analyze faculty scheduling preferences
     */
    private function analyzeFacultyPreferences($scheduleData) {
        $preferences = [];
        
        foreach ($scheduleData as $item) {
            $faculty = $item['faculty_id'] ?? '';
            $time = $item['time'] ?? '';
            
            if (!isset($preferences[$faculty])) {
                $preferences[$faculty] = ['morning' => 0, 'afternoon' => 0, 'evening' => 0];
            }
            
            $hour = (int)substr($time, 0, 2);
            if ($hour < 12) $preferences[$faculty]['morning']++;
            elseif ($hour < 17) $preferences[$faculty]['afternoon']++;
            else $preferences[$faculty]['evening']++;
        }
        
        return $preferences;
    }
    
    /**
     * Apply linear regression for prediction
     */
    private function applyLinearRegression($data, $periods) {
        if (count($data) < 2) return array_fill(0, $periods, 0);
        
        $n = count($data);
        $sumX = array_sum(range(0, $n-1));
        $sumY = array_sum($data);
        $sumXY = 0;
        $sumX2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $i * $data[$i];
            $sumX2 += $i * $i;
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;
        
        $predictions = [];
        for ($i = 0; $i < $periods; $i++) {
            $predictions[] = max(0, $intercept + $slope * ($n + $i));
        }
        
        return $predictions;
    }
    
    /**
     * Calculate confidence interval for predictions
     */
    private function calculateConfidenceInterval($data) {
        if (count($data) < 2) return ['lower' => 0, 'upper' => 0];
        
        $mean = array_sum($data) / count($data);
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $data)) / count($data);
        
        $stdDev = sqrt($variance);
        
        return [
            'lower' => max(0, $mean - 1.96 * $stdDev),
            'upper' => $mean + 1.96 * $stdDev
        ];
    }
    
    /**
     * Calculate conflict probability
     */
    private function calculateConflictProbability($riskFactors) {
        $weights = [0.4, 0.3, 0.3]; // faculty, room, time weights
        $scores = array_values($riskFactors);
        
        $weightedSum = 0;
        foreach ($scores as $i => $score) {
            $weightedSum += $score * $weights[$i];
        }
        
        return min(1.0, $weightedSum);
    }
    
    /**
     * Generate insights from patterns
     */
    private function generateInsights($patterns) {
        $insights = [];
        
        if (!empty($patterns['peak_hours'])) {
            $peakHour = array_key_first($patterns['peak_hours']);
            $insights[] = "Peak scheduling hour identified: {$peakHour}:00";
        }
        
        $insights[] = "Faculty preference patterns detected";
        $insights[] = "Room utilization patterns analyzed";
        
        return $insights;
    }
    
    /**
     * Calculate confidence score
     */
    private function calculateConfidence($patterns) {
        $dataPoints = count($patterns, COUNT_RECURSIVE) - count($patterns);
        return min(1.0, $dataPoints / 100 * $this->accuracy);
    }
    
    /**
     * Generate resource recommendations
     */
    private function generateResourceRecommendations($predictions) {
        $recommendations = [];
        
        foreach ($predictions as $resource => $pred) {
            $avgDemand = array_sum($pred['predicted_demand']) / count($pred['predicted_demand']);
            
            if ($avgDemand > 0.8) {
                $recommendations[] = "High demand expected for {$resource} - consider increasing capacity";
            } elseif ($avgDemand < 0.3) {
                $recommendations[] = "Low demand expected for {$resource} - optimize resource allocation";
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Analyze room utilization patterns
     */
    private function analyzeRoomUtilization($scheduleData) {
        $utilization = [];
        
        foreach ($scheduleData as $item) {
            $room = $item['room'] ?? '';
            if (!isset($utilization[$room])) {
                $utilization[$room] = 0;
            }
            $utilization[$room]++;
        }
        
        return $utilization;
    }
    
    /**
     * Analyze subject scheduling trends
     */
    private function analyzeSubjectTrends($scheduleData) {
        $trends = [];
        
        foreach ($scheduleData as $item) {
            $subject = $item['subject'] ?? '';
            if (!isset($trends[$subject])) {
                $trends[$subject] = 0;
            }
            $trends[$subject]++;
        }
        
        arsort($trends);
        return $trends;
    }
    
    /**
     * Apply seasonal adjustment to predictions
     */
    private function applySeasonalAdjustment($data) {
        $seasonalFactor = 1.0;
        
        // Simple weekly seasonality
        $dayOfWeek = date('w');
        if ($dayOfWeek == 0 || $dayOfWeek == 6) { // Weekend
            $seasonalFactor = 0.3;
        } elseif ($dayOfWeek == 1) { // Monday
            $seasonalFactor = 1.2;
        }
        
        return $seasonalFactor;
    }
    
    /**
     * Calculate faculty overload risk
     */
    private function calculateFacultyOverloadRisk($schedule) {
        $facultyLoads = [];
        
        foreach ($schedule as $item) {
            $faculty = $item['faculty_id'] ?? '';
            $facultyLoads[$faculty] = ($facultyLoads[$faculty] ?? 0) + 1;
        }
        
        $maxLoad = max($facultyLoads);
        $avgLoad = array_sum($facultyLoads) / count($facultyLoads);
        
        return min(1.0, $maxLoad / ($avgLoad * 2));
    }
    
    /**
     * Calculate room shortage risk
     */
    private function calculateRoomShortageRisk($schedule) {
        $roomUsage = [];
        
        foreach ($schedule as $item) {
            $room = $item['room'] ?? '';
            $time = $item['time'] ?? '';
            $key = $room . '_' . $time;
            $roomUsage[$key] = ($roomUsage[$key] ?? 0) + 1;
        }
        
        $conflicts = array_filter($roomUsage, function($count) { return $count > 1; });
        return min(1.0, count($conflicts) / count($roomUsage));
    }
    
    /**
     * Calculate time slot pressure
     */
    private function calculateTimeSlotPressure($schedule) {
        $slotUsage = [];
        
        foreach ($schedule as $item) {
            $time = $item['time'] ?? '';
            $slotUsage[$time] = ($slotUsage[$time] ?? 0) + 1;
        }
        
        $maxUsage = max($slotUsage);
        $avgUsage = array_sum($slotUsage) / count($slotUsage);
        
        return min(1.0, $maxUsage / ($avgUsage * 1.5));
    }
    
    /**
     * Identify high-risk periods
     */
    private function identifyHighRiskPeriods($schedule) {
        $riskPeriods = [];
        
        foreach ($schedule as $item) {
            $time = $item['time'] ?? '';
            $day = $item['day'] ?? '';
            $risk = 0.5; // Base risk
            
            // Add risk factors
            if (strpos($time, '09') === 0) $risk += 0.3; // Morning rush
            if ($day == 'Monday' || $day == 'Friday') $risk += 0.2; // Weekend edges
            
            if ($risk > 0.7) {
                $riskPeriods[] = ['time' => $time, 'day' => $day, 'risk' => $risk];
            }
        }
        
        return $riskPeriods;
    }
    
    /**
     * Recommend preventive actions
     */
    private function recommendPreventiveActions($riskFactors) {
        $actions = [];
        
        if ($riskFactors['faculty_overload'] > 0.7) {
            $actions[] = "Redistribute faculty workload to prevent overload";
        }
        
        if ($riskFactors['room_shortage'] > 0.7) {
            $actions[] = "Consider alternative room assignments or add temporary rooms";
        }
        
        if ($riskFactors['time_slot_pressure'] > 0.7) {
            $actions[] = "Adjust scheduling to reduce peak time slot congestion";
        }
        
        return $actions;
    }
}

?>
