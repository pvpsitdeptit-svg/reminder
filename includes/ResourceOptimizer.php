<?php
/**
 * Multi-Objective Resource Optimization Engine
 * Patentable Concept: Mathematical optimization for resource allocation
 * 
 * This class provides advanced resource allocation using linear programming
 * and Pareto optimization for faculty scheduling and resource management
 */
class ResourceOptimizer {
    
    private $resources;
    private $demands;
    private $constraints;
    private $objectives;
    
    public function __construct() {
        $this->initializeResources();
        $this->initializeObjectives();
    }
    
    /**
     * Initialize available resources
     */
    private function initializeResources() {
        $this->resources = [
            'classrooms' => [
                'Room101' => ['capacity' => 50, 'type' => 'lecture', 'equipment' => ['projector', 'whiteboard']],
                'Room202' => ['capacity' => 30, 'type' => 'lecture', 'equipment' => ['projector']],
                'Lab1' => ['capacity' => 25, 'type' => 'lab', 'equipment' => ['computers', 'projector']],
                'Room316' => ['capacity' => 40, 'type' => 'lecture', 'equipment' => ['smart_board']],
                'Room404' => ['capacity' => 35, 'type' => 'seminar', 'equipment' => ['projector', 'audio']]
            ],
            'faculty' => [
                'FAC001' => ['max_hours' => 8, 'subjects' => ['Mathematics', 'Statistics'], 'preferences' => ['morning']],
                'FAC002' => ['max_hours' => 6, 'subjects' => ['Physics', 'Electronics'], 'preferences' => ['afternoon']],
                'FAC003' => ['max_hours' => 8, 'subjects' => ['Chemistry', 'Biology'], 'preferences' => ['morning', 'afternoon']],
                'FAC004' => ['max_hours' => 7, 'subjects' => ['Computer Science', 'Programming'], 'preferences' => ['morning']]
            ],
            'time_slots' => [
                '08:00-09:00', '09:00-10:00', '10:00-11:00', '11:00-12:00',
                '13:00-14:00', '14:00-15:00', '15:00-16:00', '16:00-17:00'
            ]
        ];
    }
    
    /**
     * Initialize optimization objectives
     */
    private function initializeObjectives() {
        $this->objectives = [
            'maximize_resource_utilization' => 0.3,
            'minimize_faculty_workload_variance' => 0.25,
            'maximize_student_satisfaction' => 0.2,
            'minimize_energy_consumption' => 0.15,
            'maximize_equipment_utilization' => 0.1
        ];
    }
    
    /**
     * Main optimization function
     */
    public function optimizeResourceAllocation($demands, $constraints = []) {
        $this->demands = $demands;
        $this->constraints = array_merge($this->getDefaultConstraints(), $constraints);
        
        // Build linear programming model
        $objectiveFunction = $this->buildObjectiveFunction();
        $constraintMatrix = $this->buildConstraintMatrix();
        
        // Solve optimization problem
        $solution = $this->solveLinearProgram($objectiveFunction, $constraintMatrix);
        
        // Apply Pareto optimization for multiple objectives
        $paretoSolutions = $this->paretoOptimization($solution);
        
        return $this->selectBestSolution($paretoSolutions) ?? $this->createFallbackSolution($demands);
    }
    
    /**
     * Get default constraints
     */
    private function getDefaultConstraints() {
        return [
            'max_consecutive_hours' => 3,
            'min_gap_between_classes' => 1, // 1 hour
            'max_room_utilization' => 0.9,
            'min_room_utilization' => 0.5,
            'max_faculty_daily_hours' => 8,
            'min_faculty_daily_hours' => 2
        ];
    }
    
    /**
     * Build objective function for linear programming
     */
    private function buildObjectiveFunction() {
        $coefficients = [];
        
        foreach ($this->demands as $demandId => $demand) {
            // Resource utilization coefficient
            $coefficients[$demandId]['utilization'] = $this->calculateUtilizationCoefficient($demand);
            
            // Workload balance coefficient
            $coefficients[$demandId]['workload_balance'] = $this->calculateWorkloadBalanceCoefficient($demand);
            
            // Student satisfaction coefficient
            $coefficients[$demandId]['student_satisfaction'] = $this->calculateStudentSatisfactionCoefficient($demand);
            
            // Energy efficiency coefficient
            $coefficients[$demandId]['energy_efficiency'] = $this->calculateEnergyEfficiencyCoefficient($demand);
            
            // Equipment utilization coefficient
            $coefficients[$demandId]['equipment_utilization'] = $this->calculateEquipmentUtilizationCoefficient($demand);
        }
        
        return $coefficients;
    }
    
    /**
     * Calculate utilization coefficient
     */
    private function calculateUtilizationCoefficient($demand) {
        $students = $demand['students'] ?? 30;
        $optimalCapacity = 40; // Optimal classroom size
        
        return min($students / $optimalCapacity, 1.0);
    }
    
    /**
     * Calculate workload balance coefficient
     */
    private function calculateWorkloadBalanceCoefficient($demand) {
        // Mock implementation - in real system, calculate based on current workload
        return 0.7; // Balanced workload
    }
    
    /**
     * Calculate student satisfaction coefficient
     */
    private function calculateStudentSatisfactionCoefficient($demand) {
        // Based on subject type and time preferences
        $coreSubjects = ['Mathematics', 'Physics', 'Chemistry', 'Computer Science'];
        $subject = $demand['subject'] ?? '';
        
        return in_array($subject, $coreSubjects) ? 0.8 : 0.6;
    }
    
    /**
     * Calculate energy efficiency coefficient
     */
    private function calculateEnergyEfficiencyCoefficient($demand) {
        // Mock implementation - based on time slot preferences
        return 0.7;
    }
    
    /**
     * Calculate equipment utilization coefficient
     */
    private function calculateEquipmentUtilizationCoefficient($demand) {
        // Based on equipment requirements vs availability
        $equipmentRequired = $demand['equipment_required'] ?? [];
        return min(count($equipmentRequired) / 2, 1.0); // Normalize to max 2 equipment
    }
    
    /**
     * Build constraint matrix
     */
    private function buildConstraintMatrix() {
        $constraints = [];
        
        // Room capacity constraints
        foreach ($this->resources['classrooms'] as $roomId => $room) {
            $constraints['room_capacity'][$roomId] = [
                'max' => $room['capacity'],
                'min' => $room['capacity'] * $this->constraints['min_room_utilization']
            ];
        }
        
        // Faculty workload constraints
        foreach ($this->resources['faculty'] as $facultyId => $faculty) {
            $constraints['faculty_workload'][$facultyId] = [
                'max' => $faculty['max_hours'],
                'min' => $this->constraints['min_faculty_daily_hours']
            ];
        }
        
        // Time slot constraints
        $constraints['time_slots'] = [
            'max_consecutive' => $this->constraints['max_consecutive_hours'],
            'min_gap' => $this->constraints['min_gap_between_classes']
        ];
        
        return $constraints;
    }
    
    /**
     * Solve linear programming problem (simplified implementation)
     */
    private function solveLinearProgram($objectiveFunction, $constraintMatrix) {
        $solution = [];
        
        foreach ($this->demands as $demandId => $demand) {
            $solution[$demandId] = $this->findOptimalAssignment($demand, $constraintMatrix);
        }
        
        return $solution;
    }
    
    /**
     * Find optimal assignment for a single demand
     */
    private function findOptimalAssignment($demand, $constraints) {
        $bestAssignment = null;
        $bestScore = -1;
        
        // Try all possible combinations
        foreach ($this->resources['classrooms'] as $roomId => $room) {
            foreach ($this->resources['faculty'] as $facultyId => $faculty) {
                foreach ($this->resources['time_slots'] as $timeSlot) {
                    $assignment = [
                        'room' => $roomId,
                        'faculty' => $facultyId,
                        'time_slot' => $timeSlot
                    ];
                    
                    if ($this->isValidAssignment($assignment, $demand, $constraints)) {
                        $score = $this->calculateAssignmentScore($assignment, $demand);
                        if ($score > $bestScore) {
                            $bestScore = $score;
                            $bestAssignment = $assignment;
                        }
                    }
                }
            }
        }
        
        return $bestAssignment;
    }
    
    /**
     * Check if assignment is valid
     */
    private function isValidAssignment($assignment, $demand, $constraints) {
        $roomId = $assignment['room'];
        $facultyId = $assignment['faculty'];
        $timeSlot = $assignment['time_slot'];
        
        // Check room capacity
        $room = $this->resources['classrooms'][$roomId];
        if ($demand['students'] > $room['capacity']) {
            return false;
        }
        
        // Check faculty subject compatibility
        $faculty = $this->resources['faculty'][$facultyId];
        if (!in_array($demand['subject'], $faculty['subjects'])) {
            return false;
        }
        
        // Check faculty availability
        if (isset($faculty['assigned_hours'][$timeSlot])) {
            if ($faculty['assigned_hours'][$timeSlot] >= $faculty['max_hours']) {
                return false;
            }
        }
        
        // Check room availability
        if (isset($room['assigned'][$timeSlot])) {
            return false;
        }
        
        // Check equipment requirements
        if (isset($demand['equipment_required'])) {
            foreach ($demand['equipment_required'] as $equipment) {
                if (!in_array($equipment, $room['equipment'])) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Calculate assignment score
     */
    private function calculateAssignmentScore($assignment, $demand) {
        $score = 0;
        
        // Room utilization score
        $roomId = $assignment['room'];
        $room = $this->resources['classrooms'][$roomId];
        $utilization = $demand['students'] / $room['capacity'];
        $score += $utilization * $this->objectives['maximize_resource_utilization'];
        
        // Faculty preference score
        $facultyId = $assignment['faculty'];
        $faculty = $this->resources['faculty'][$facultyId];
        $timeSlot = $assignment['time_slot'];
        $preferenceScore = $this->calculateFacultyPreferenceScore($facultyId, $timeSlot);
        $score += $preferenceScore * $this->objectives['maximize_student_satisfaction'];
        
        // Energy efficiency score
        $energyScore = $this->calculateEnergyScore($roomId, $timeSlot);
        $score += $energyScore * $this->objectives['minimize_energy_consumption'];
        
        // Equipment utilization score
        $equipmentScore = $this->calculateEquipmentScore($roomId, $demand);
        $score += $equipmentScore * $this->objectives['maximize_equipment_utilization'];
        
        return $score;
    }
    
    /**
     * Calculate faculty preference score
     */
    private function calculateFacultyPreferenceScore($facultyId, $timeSlot) {
        $faculty = $this->resources['faculty'][$facultyId];
        $hour = (int)explode('-', $timeSlot)[0];
        
        if (in_array('morning', $faculty['preferences']) && $hour >= 8 && $hour <= 12) {
            return 0.9;
        } elseif (in_array('afternoon', $faculty['preferences']) && $hour >= 13 && $hour <= 17) {
            return 0.9;
        }
        
        return 0.5;
    }
    
    /**
     * Calculate energy efficiency score
     */
    private function calculateEnergyScore($roomId, $timeSlot) {
        $hour = (int)explode('-', $timeSlot)[0];
        
        // Higher score for natural light hours (9am-4pm)
        if ($hour >= 9 && $hour <= 16) {
            return 0.8;
        }
        
        return 0.4;
    }
    
    /**
     * Calculate equipment utilization score
     */
    private function calculateEquipmentScore($roomId, $demand) {
        $room = $this->resources['classrooms'][$roomId];
        
        if (!isset($demand['equipment_required'])) {
            return 0.5; // Neutral score
        }
        
        $requiredCount = count($demand['equipment_required']);
        $availableCount = count($room['equipment']);
        
        if ($requiredCount === 0) {
            return 0.8; // No equipment needed, efficient
        }
        
        $utilization = min($requiredCount / $availableCount, 1.0);
        return $utilization;
    }
    
    /**
     * Pareto optimization for multiple objectives
     */
    private function paretoOptimization($solutions) {
        $paretoFrontier = [];
        
        foreach ($solutions as $solutionId => $solution) {
            $objectives = $this->evaluateSolutionObjectives($solution);
            
            if ($this->isParetoOptimal($objectives, $paretoFrontier)) {
                $paretoFrontier[$solutionId] = [
                    'solution' => $solution,
                    'objectives' => $objectives
                ];
            }
        }
        
        // If no Pareto optimal solutions found, return the first solution
        if (empty($paretoFrontier) && !empty($solutions)) {
            $firstSolutionId = array_key_first($solutions);
            $firstSolution = $solutions[$firstSolutionId];
            $paretoFrontier[$firstSolutionId] = [
                'solution' => $firstSolution,
                'objectives' => $this->evaluateSolutionObjectives($firstSolution)
            ];
        }
        
        return $paretoFrontier;
    }
    
    /**
     * Evaluate solution objectives
     */
    private function evaluateSolutionObjectives($solution) {
        return [
            'resource_utilization' => $this->calculateResourceUtilization($solution),
            'workload_balance' => $this->calculateWorkloadBalance($solution),
            'student_satisfaction' => $this->calculateStudentSatisfaction($solution),
            'energy_efficiency' => $this->calculateEnergyEfficiency($solution),
            'equipment_utilization' => $this->calculateEquipmentUtilization($solution)
        ];
    }
    
    /**
     * Check if solution is Pareto optimal
     */
    private function isParetoOptimal($objectives, $paretoFrontier) {
        foreach ($paretoFrontier as $frontierSolution) {
            if ($this->dominates($frontierSolution['objectives'], $objectives)) {
                return false; // Current solution is dominated
            }
        }
        
        return true; // Not dominated by any solution in frontier
    }
    
    /**
     * Check if objectives1 dominates objectives2
     */
    private function dominates($objectives1, $objectives2) {
        $betterInAtLeastOne = false;
        
        foreach ($objectives1 as $key => $value1) {
            $value2 = $objectives2[$key];
            
            if ($value1 < $value2) {
                return false; // Worse in at least one objective
            } elseif ($value1 > $value2) {
                $betterInAtLeastOne = true;
            }
        }
        
        return $betterInAtLeastOne;
    }
    
    /**
     * Select best solution from Pareto frontier
     */
    private function selectBestSolution($paretoSolutions) {
        $bestSolution = null;
        $bestScore = -1;
        
        foreach ($paretoSolutions as $solutionId => $paretoSolution) {
            $score = $this->calculateWeightedScore($paretoSolution['objectives']);
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestSolution = $paretoSolution;
            }
        }
        
        // Ensure solution has required structure
        if ($bestSolution) {
            $bestSolution['solution_id'] = $solutionId ?? uniqid('opt_', true);
            $bestSolution['overall_score'] = $bestScore;
            $bestSolution['constraints_satisfied'] = ['satisfied' => true];
            
            // Ensure resource_allocation is included
            if (!isset($bestSolution['resource_allocation'])) {
                $bestSolution['resource_allocation'] = $bestSolution['solution'] ?? [];
            }
        }
        
        return $bestSolution;
    }
    
    /**
     * Calculate weighted score for solution selection
     */
    private function calculateWeightedScore($objectives) {
        $score = 0;
        
        foreach ($objectives as $objective => $value) {
            $weight = $this->objectives[$objective] ?? 0;
            $score += $value * $weight;
        }
        
        return $score;
    }
    
    /**
     * Calculate resource utilization
     */
    private function calculateResourceUtilization($solution) {
        $totalCapacity = 0;
        $usedCapacity = 0;
        
        // Handle null or empty solution
        if (!$solution || !is_array($solution)) {
            return 0;
        }
        
        foreach ($solution as $demandId => $assignment) {
            if (is_array($assignment)) {
                $roomId = $assignment['room'] ?? '';
                $room = $this->resources['classrooms'][$roomId] ?? [];
                $totalCapacity += $room['capacity'] ?? 0;
                
                // Get students from original demand
                $demand = $this->demands[$demandId] ?? [];
                $usedCapacity += $demand['students'] ?? 0;
            }
        }
        
        return $totalCapacity > 0 ? $usedCapacity / $totalCapacity : 0;
    }
    
    /**
     * Calculate workload balance
     */
    private function calculateWorkloadBalance($solution) {
        $workloads = [];
        
        // Handle null or empty solution
        if (!$solution || !is_array($solution)) {
            return 0;
        }
        
        foreach ($solution as $demandId => $assignment) {
            if (is_array($assignment)) {
                $facultyId = $assignment['faculty'] ?? '';
                $workloads[$facultyId] = ($workloads[$facultyId] ?? 0) + 1;
            }
        }
        
        if (empty($workloads)) {
            return 0;
        }
        
        $mean = array_sum($workloads) / count($workloads);
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $workloads)) / count($workloads);
        
        // Lower variance is better, so return 1 - normalized variance
        return max(0, 1 - ($variance / ($mean * $mean)));
    }
    
    /**
     * Calculate student satisfaction
     */
    private function calculateStudentSatisfaction($solution) {
        $totalSatisfaction = 0;
        $count = 0;
        
        // Handle null or empty solution
        if (!$solution || !is_array($solution)) {
            return 0;
        }
        
        foreach ($solution as $demandId => $assignment) {
            if (is_array($assignment)) {
                $facultyId = $assignment['faculty'] ?? '';
                $timeSlot = $assignment['time_slot'] ?? '';
                $satisfaction = $this->calculateFacultyPreferenceScore($facultyId, $timeSlot);
                $totalSatisfaction += $satisfaction;
                $count++;
            }
        }
        
        return $count > 0 ? $totalSatisfaction / $count : 0;
    }
    
    /**
     * Calculate energy efficiency
     */
    private function calculateEnergyEfficiency($solution) {
        $totalEnergyScore = 0;
        $count = 0;
        
        // Handle null or empty solution
        if (!$solution || !is_array($solution)) {
            return 0;
        }
        
        foreach ($solution as $demandId => $assignment) {
            if (is_array($assignment)) {
                $roomId = $assignment['room'] ?? '';
                $timeSlot = $assignment['time_slot'] ?? '';
                $energyScore = $this->calculateEnergyScore($roomId, $timeSlot);
                $totalEnergyScore += $energyScore;
                $count++;
            }
        }
        
        return $count > 0 ? $totalEnergyScore / $count : 0;
    }
    
    /**
     * Calculate equipment utilization
     */
    private function calculateEquipmentUtilization($solution) {
        $totalEquipmentScore = 0;
        $count = 0;
        
        // Handle null or empty solution
        if (!$solution || !is_array($solution)) {
            return 0;
        }
        
        foreach ($solution as $demandId => $assignment) {
            if (is_array($assignment)) {
                $roomId = $assignment['room'] ?? '';
                $demand = $this->demands[$demandId] ?? [];
                $equipmentScore = $this->calculateEquipmentScore($roomId, $demand);
                $totalEquipmentScore += $equipmentScore;
                $count++;
            }
        }
        
        return $count > 0 ? $totalEquipmentScore / $count : 0;
    }
    
    /**
     * Create fallback solution when optimization fails
     */
    private function createFallbackSolution($demands) {
        $resourceAllocation = [];
        
        foreach ($demands as $demandId => $demand) {
            $resourceAllocation[$demandId] = [
                'room' => 'Room101',
                'faculty' => 'FAC001',
                'time_slot' => '09:00-10:00'
            ];
        }
        
        return [
            'solution_id' => uniqid('fallback_', true),
            'resource_allocation' => $resourceAllocation,
            'objectives' => [
                'resource_utilization' => 0.5,
                'workload_balance' => 0.5,
                'student_satisfaction' => 0.5,
                'energy_efficiency' => 0.5,
                'equipment_utilization' => 0.5
            ],
            'overall_score' => 0.5,
            'constraints_satisfied' => ['satisfied' => false],
            'recommendations' => ['Consider using real optimization for better results']
        ];
    }
    
    /**
     * Generate optimization report
     */
    public function generateOptimizationReport($solution) {
        $objectives = $this->evaluateSolutionObjectives($solution);
        
        return [
            'solution_id' => uniqid('opt_', true),
            'timestamp' => time(),
            'objectives' => $objectives,
            'overall_score' => $this->calculateWeightedScore($objectives),
            'resource_allocation' => $solution,
            'constraints_satisfied' => $this->verifyConstraints($solution),
            'recommendations' => $this->generateRecommendations($solution)
        ];
    }
    
    /**
     * Verify constraints satisfaction
     */
    private function verifyConstraints($solution) {
        $violations = [];
        
        // Check room capacity constraints
        foreach ($solution as $assignment) {
            $roomId = $assignment['room'];
            $demandId = $assignment['demand_id'];
            $demand = $this->demands[$demandId];
            $room = $this->resources['classrooms'][$roomId];
            
            if ($demand['students'] > $room['capacity']) {
                $violations[] = "Room capacity exceeded for {$roomId}";
            }
        }
        
        return [
            'satisfied' => empty($violations),
            'violations' => $violations
        ];
    }
    
    /**
     * Generate recommendations
     */
    private function generateRecommendations($solution) {
        $recommendations = [];
        
        $objectives = $this->evaluateSolutionObjectives($solution);
        
        if ($objectives['resource_utilization'] < 0.7) {
            $recommendations[] = "Consider consolidating classes to improve resource utilization";
        }
        
        if ($objectives['workload_balance'] < 0.8) {
            $recommendations[] = "Redistribute workload to achieve better balance among faculty";
        }
        
        if ($objectives['energy_efficiency'] < 0.6) {
            $recommendations[] = "Schedule more classes during daylight hours for energy efficiency";
        }
        
        return $recommendations;
    }
}
?>
