<?php
/**
 * Dynamic Conflict Resolution Algorithm
 * Patentable Concept: Mathematical approach to scheduling conflicts
 * 
 * This class provides intelligent conflict resolution for faculty scheduling
 * using weighted scoring and optimization algorithms
 */
class ConflictResolutionEngine {
    
    private $weights = [
        'seniority' => 0.3,
        'subject_priority' => 0.25,
        'room_capacity' => 0.2,
        'time_preference' => 0.15,
        'department_balance' => 0.1
    ];
    
    private $constraints = [
        'max_daily_hours' => 8,
        'min_gap_between_classes' => 15, // minutes
        'max_consecutive_hours' => 3
    ];
    
    /**
     * Resolve scheduling conflicts using optimization algorithm
     */
    public function resolveSchedulingConflicts($schedule, $constraints = []) {
        $conflictMatrix = $this->buildConflictMatrix($schedule);
        $resolution = $this->applyOptimizationAlgorithm($conflictMatrix, $constraints);
        return $resolution;
    }
    
    /**
     * Build conflict matrix from existing schedule
     */
    private function buildConflictMatrix($schedule) {
        $conflicts = [];
        
        foreach ($schedule as $index1 => $event1) {
            foreach ($schedule as $index2 => $event2) {
                if ($index1 >= $index2) continue;
                
                $conflict = $this->detectConflict($event1, $event2);
                if ($conflict) {
                    $conflicts[] = [
                        'events' => [$index1, $index2],
                        'type' => $conflict['type'],
                        'severity' => $conflict['severity'],
                        'suggestions' => $conflict['suggestions']
                    ];
                }
            }
        }
        
        return $conflicts;
    }
    
    /**
     * Detect conflicts between two events
     */
    private function detectConflict($event1, $event2) {
        // Time overlap detection
        if ($this->isTimeOverlap($event1['time'], $event2['time'], 
                                $event1['duration'], $event2['duration'])) {
            
            // Check if same room
            if ($event1['room'] === $event2['room']) {
                return [
                    'type' => 'room_conflict',
                    'severity' => 'high',
                    'suggestions' => $this->suggestRoomAlternatives($event1, $event2)
                ];
            }
            
            // Check if same faculty
            if ($event1['faculty_id'] === $event2['faculty_id']) {
                return [
                    'type' => 'faculty_conflict',
                    'severity' => 'critical',
                    'suggestions' => $this->suggestTimeAlternatives($event1, $event2)
                ];
            }
            
            // Check if same student group
            if ($this->hasStudentGroupOverlap($event1, $event2)) {
                return [
                    'type' => 'student_group_conflict',
                    'severity' => 'medium',
                    'suggestions' => $this->suggestRescheduling($event1, $event2)
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Check time overlap between events
     */
    private function isTimeOverlap($time1, $time2, $duration1, $duration2) {
        $start1 = strtotime($time1);
        $end1 = $start1 + ($duration1 * 60); // Convert to minutes
        $start2 = strtotime($time2);
        $end2 = $start2 + ($duration2 * 60);
        
        return ($start1 < $end2) && ($start2 < $end1);
    }
    
    /**
     * Apply optimization algorithm to resolve conflicts
     */
    private function applyOptimizationAlgorithm($conflictMatrix, $constraints) {
        $resolutions = [];
        
        // Sort conflicts by severity
        usort($conflictMatrix, function($a, $b) {
            $severityOrder = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];
            return $severityOrder[$b['severity']] - $severityOrder[$a['severity']];
        });
        
        foreach ($conflictMatrix as $conflict) {
            $resolution = $this->generateResolution($conflict, $constraints);
            $resolutions[] = $resolution;
        }
        
        return $resolutions;
    }
    
    /**
     * Generate resolution for a specific conflict
     */
    private function generateResolution($conflict, $constraints) {
        $event1Index = $conflict['events'][0];
        $event2Index = $conflict['events'][1];
        
        $resolution = [
            'conflict_type' => $conflict['type'],
            'severity' => $conflict['severity'],
            'resolution_strategy' => $this->selectBestStrategy($conflict),
            'affected_events' => [$event1Index, $event2Index],
            'recommended_actions' => []
        ];
        
        switch ($conflict['type']) {
            case 'room_conflict':
                $resolution['recommended_actions'] = $this->resolveRoomConflict($conflict);
                break;
            case 'faculty_conflict':
                $resolution['recommended_actions'] = $this->resolveFacultyConflict($conflict);
                break;
            case 'student_group_conflict':
                $resolution['recommended_actions'] = $this->resolveStudentGroupConflict($conflict);
                break;
        }
        
        return $resolution;
    }
    
    /**
     * Calculate priority score for an event
     */
    public function calculatePriorityScore($event, $constraints) {
        $score = 0;
        
        // Seniority score
        $score += $this->calculateSeniorityScore($event['faculty_id']) * $this->weights['seniority'];
        
        // Subject priority score
        $score += $this->calculateSubjectPriority($event['subject']) * $this->weights['subject_priority'];
        
        // Room capacity match
        $score += $this->calculateRoomCapacityScore($event['room'], $event['expected_students']) * $this->weights['room_capacity'];
        
        // Time preference score
        $score += $this->calculateTimePreferenceScore($event['faculty_id'], $event['time']) * $this->weights['time_preference'];
        
        // Department balance score
        $score += $this->calculateDepartmentBalance($event['department']) * $this->weights['department_balance'];
        
        return $score;
    }
    
    /**
     * Calculate seniority score based on faculty experience
     */
    private function calculateSeniorityScore($facultyId) {
        // Mock implementation - in real system, fetch from database
        $seniorityData = [
            'FAC001' => 0.9, // Senior faculty
            'FAC002' => 0.7, // Mid-level
            'FAC003' => 0.5, // Junior
        ];
        
        return $seniorityData[$facultyId] ?? 0.5;
    }
    
    /**
     * Calculate subject priority (core subjects have higher priority)
     */
    private function calculateSubjectPriority($subject) {
        $coreSubjects = ['Mathematics', 'Physics', 'Chemistry', 'Computer Science'];
        $electiveSubjects = ['Literature', 'History', 'Art'];
        
        if (in_array($subject, $coreSubjects)) {
            return 0.9;
        } elseif (in_array($subject, $electiveSubjects)) {
            return 0.6;
        } else {
            return 0.7;
        }
    }
    
    /**
     * Calculate room capacity match score
     */
    private function calculateRoomCapacityScore($room, $expectedStudents) {
        $roomCapacities = [
            'Room101' => 50,
            'Room202' => 30,
            'Lab1' => 25,
            'Room316' => 40
        ];
        
        $capacity = $roomCapacities[$room] ?? 30;
        $utilization = $expectedStudents / $capacity;
        
        // Optimal utilization is 70-90%
        if ($utilization >= 0.7 && $utilization <= 0.9) {
            return 0.9;
        } elseif ($utilization >= 0.5 && $utilization <= 1.0) {
            return 0.7;
        } else {
            return 0.4;
        }
    }
    
    /**
     * Calculate time preference score
     */
    private function calculateTimePreferenceScore($facultyId, $time) {
        // Mock preference data - in real system, fetch from faculty preferences
        $preferences = [
            'FAC001' => ['preferred' => ['09:00', '10:00'], 'avoid' => ['13:00', '14:00']],
            'FAC002' => ['preferred' => ['10:00', '11:00'], 'avoid' => ['08:00', '09:00']],
        ];
        
        if (isset($preferences[$facultyId])) {
            if (in_array($time, $preferences[$facultyId]['preferred'])) {
                return 0.9;
            } elseif (in_array($time, $preferences[$facultyId]['avoid'])) {
                return 0.3;
            }
        }
        
        return 0.6; // Neutral preference
    }
    
    /**
     * Calculate department balance score
     */
    private function calculateDepartmentBalance($department) {
        // Mock implementation - checks if department is over/under allocated
        $departmentLoads = [
            'Computer Science' => 0.8,
            'Information Technology' => 0.7,
            'Mathematics' => 0.9
        ];
        
        $currentLoad = $departmentLoads[$department] ?? 0.7;
        
        // Prefer departments with lower loads for balance
        return 1.0 - $currentLoad;
    }
    
    /**
     * Suggest room alternatives for conflicts
     */
    private function suggestRoomAlternatives($event1, $event2) {
        $allRooms = ['Room101', 'Room202', 'Lab1', 'Room316', 'Room404'];
        $availableRooms = array_diff($allRooms, [$event1['room'], $event2['room']]);
        
        return array_values($availableRooms);
    }
    
    /**
     * Suggest time alternatives for conflicts
     */
    private function suggestTimeAlternatives($event1, $event2) {
        $timeSlots = ['08:00', '09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'];
        $occupied = [$event1['time'], $event2['time']];
        
        return array_diff($timeSlots, $occupied);
    }
    
    /**
     * Check if events have student group overlap
     */
    private function hasStudentGroupOverlap($event1, $event2) {
        // Check if same year, branch, or section with default values
        return (($event1['year'] ?? '') === ($event2['year'] ?? '') && 
                ($event1['branch'] ?? '') === ($event2['branch'] ?? '') && 
                ($event1['section'] ?? '') === ($event2['section'] ?? ''));
    }
    
    /**
     * Suggest rescheduling options
     */
    private function suggestRescheduling($event1, $event2) {
        $timeSlots = ['08:00', '09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'];
        $occupied = [$event1['time'], $event2['time']];
        
        return array_diff($timeSlots, $occupied);
    }
    
    /**
     * Resolve room conflict
     */
    private function resolveRoomConflict($conflict) {
        return [
            'action' => 'reassign_room',
            'alternative_rooms' => $conflict['suggestions'],
            'recommendation' => 'Assign one of the events to an alternative room'
        ];
    }
    
    /**
     * Resolve faculty conflict
     */
    private function resolveFacultyConflict($conflict) {
        return [
            'action' => 'reschedule_event',
            'alternative_times' => $conflict['suggestions'],
            'recommendation' => 'Reschedule one of the events to an alternative time slot'
        ];
    }
    
    /**
     * Resolve student group conflict
     */
    private function resolveStudentGroupConflict($conflict) {
        return [
            'action' => 'reschedule_or_split',
            'recommendation' => 'Either reschedule or split the student group'
        ];
    }
    
    /**
     * Select best resolution strategy
     */
    private function selectBestStrategy($conflict) {
        switch ($conflict['severity']) {
            case 'critical':
                return 'immediate_reschedule';
            case 'high':
                return 'room_reassignment';
            case 'medium':
                return 'time_adjustment';
            case 'low':
                return 'monitor_and_optimize';
            default:
                return 'manual_review';
        }
    }
}
?>
