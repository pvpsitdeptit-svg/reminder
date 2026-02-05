<?php
/**
 * Real-Time Data Synchronization Engine
 * Patentable Concept: Efficient multi-system data synchronization
 * 
 * This class provides real-time data synchronization across multiple systems
 * using vector clocks, operational transformation, and conflict resolution
 */
class DataSyncEngine {
    
    private $vectorClock;
    private $operationLog;
    private $localData;
    private $remoteData;
    private $syncStatus;
    private $conflictResolver;
    
    public function __construct() {
        $this->vectorClock = new VectorClock();
        $this->operationLog = [];
        $this->localData = [];
        $this->remoteData = [];
        $this->syncStatus = [];
        $this->conflictResolver = new ConflictResolver();
    }
    
    /**
     * Synchronize data between local and remote systems
     */
    public function synchronizeData($localData, $remoteData) {
        $this->localData = $localData;
        $this->remoteData = $remoteData;
        
        // Update vector clock
        $this->vectorClock->increment('local');
        
        // Detect conflicts
        $conflicts = $this->detectConflicts($localData, $remoteData);
        
        // Resolve conflicts using operational transformation
        $resolution = $this->resolveConflictsOT($conflicts);
        
        // Apply changes
        $synchronizedData = $this->applyChanges($resolution);
        
        // Update sync status
        $this->updateSyncStatus($synchronizedData);
        
        return $synchronizedData;
    }
    
    /**
     * Detect conflicts between local and remote data
     */
    private function detectConflicts($localData, $remoteData) {
        $conflicts = [];
        
        // Compare data versions
        foreach ($localData as $key => $localValue) {
            if (isset($remoteData[$key])) {
                $remoteValue = $remoteData[$key];
                
                // Check if versions conflict
                if ($this->hasVersionConflict($localValue, $remoteValue)) {
                    $conflicts[] = [
                        'type' => 'version_conflict',
                        'key' => $key,
                        'local_value' => $localValue,
                        'remote_value' => $remoteValue,
                        'local_timestamp' => $localValue['timestamp'] ?? 0,
                        'remote_timestamp' => $remoteValue['timestamp'] ?? 0
                    ];
                }
            } else {
                // Local data not present remotely
                $conflicts[] = [
                    'type' => 'local_only',
                    'key' => $key,
                    'value' => $localValue
                ];
            }
        }
        
        // Check for remote-only data
        foreach ($remoteData as $key => $remoteValue) {
            if (!isset($localData[$key])) {
                $conflicts[] = [
                    'type' => 'remote_only',
                    'key' => $key,
                    'value' => $remoteValue
                ];
            }
        }
        
        return $conflicts;
    }
    
    /**
     * Check if there's a version conflict
     */
    private function hasVersionConflict($localValue, $remoteValue) {
        $localTimestamp = $localValue['timestamp'] ?? 0;
        $remoteTimestamp = $remoteValue['timestamp'] ?? 0;
        $localVersion = $localValue['version'] ?? 1;
        $remoteVersion = $remoteValue['version'] ?? 1;
        
        // Check if both have been modified since last sync
        if ($localTimestamp !== $remoteTimestamp) {
            // Check if content is different
            $localContent = $localValue['data'] ?? $localValue;
            $remoteContent = $remoteValue['data'] ?? $remoteValue;
            
            return $localContent !== $remoteContent;
        }
        
        return false;
    }
    
    /**
     * Resolve conflicts using Operational Transformation
     */
    private function resolveConflictsOT($conflicts) {
        $resolution = [];
        
        foreach ($conflicts as $conflict) {
            switch ($conflict['type']) {
                case 'version_conflict':
                    $resolution[$conflict['key']] = $this->resolveVersionConflict($conflict);
                    break;
                case 'local_only':
                    $resolution[$conflict['key']] = $this->resolveLocalOnlyConflict($conflict);
                    break;
                case 'remote_only':
                    $resolution[$conflict['key']] = $this->resolveRemoteOnlyConflict($conflict);
                    break;
            }
        }
        
        return $resolution;
    }
    
    /**
     * Resolve version conflict using operational transformation
     */
    private function resolveVersionConflict($conflict) {
        $key = $conflict['key'];
        $localValue = $conflict['local_value'];
        $remoteValue = $conflict['remote_value'];
        
        // Get operations that led to conflict
        $localOps = $this->getOperationsSince($key, $localValue['last_sync_version'] ?? 0);
        $remoteOps = $this->getOperationsSince($key, $remoteValue['last_sync_version'] ?? 0);
        
        // Transform operations
        $transformedLocalOps = $this->transformOperations($localOps, $remoteOps);
        $transformedRemoteOps = $this->transformOperations($remoteOps, $localOps);
        
        // Apply transformed operations
        $baseData = $this->getBaseData($key);
        $finalData = $this->applyOperations($baseData, array_merge($transformedLocalOps, $transformedRemoteOps));
        
        return [
            'data' => $finalData,
            'version' => max($localValue['version'] ?? 1, $remoteValue['version'] ?? 1) + 1,
            'timestamp' => time(),
            'conflict_resolved' => true,
            'resolution_method' => 'operational_transformation'
        ];
    }
    
    /**
     * Resolve local-only conflict
     */
    private function resolveLocalOnlyConflict($conflict) {
        return [
            'data' => $conflict['value']['data'] ?? $conflict['value'],
            'version' => ($conflict['value']['version'] ?? 1) + 1,
            'timestamp' => time(),
            'conflict_resolved' => false,
            'resolution_method' => 'local_wins'
        ];
    }
    
    /**
     * Resolve remote-only conflict
     */
    private function resolveRemoteOnlyConflict($conflict) {
        return [
            'data' => $conflict['value']['data'] ?? $conflict['value'],
            'version' => ($conflict['value']['version'] ?? 1) + 1,
            'timestamp' => time(),
            'conflict_resolved' => false,
            'resolution_method' => 'remote_wins'
        ];
    }
    
    /**
     * Transform operations against concurrent operations
     */
    private function transformOperations($operations, $concurrentOps) {
        $transformed = [];
        
        foreach ($operations as $op) {
            $transformedOp = $op;
            
            foreach ($concurrentOps as $concurrentOp) {
                $transformedOp = $this->transformOperation($transformedOp, $concurrentOp);
            }
            
            $transformed[] = $transformedOp;
        }
        
        return $transformed;
    }
    
    /**
     * Transform a single operation against a concurrent operation
     */
    private function transformOperation($operation, $concurrentOperation) {
        $transformed = $operation;
        
        // Handle different operation types
        switch ($operation['type']) {
            case 'insert':
                $transformed = $this->transformInsert($operation, $concurrentOperation);
                break;
            case 'delete':
                $transformed = $this->transformDelete($operation, $concurrentOperation);
                break;
            case 'update':
                $transformed = $this->transformUpdate($operation, $concurrentOperation);
                break;
        }
        
        return $transformed;
    }
    
    /**
     * Transform insert operation
     */
    private function transformInsert($insertOp, $concurrentOp) {
        if ($concurrentOp['type'] === 'insert' && $concurrentOp['position'] <= $insertOp['position']) {
            return [
                'type' => 'insert',
                'position' => $insertOp['position'] + 1,
                'content' => $insertOp['content'],
                'attributes' => $insertOp['attributes'] ?? []
            ];
        }
        
        return $insertOp;
    }
    
    /**
     * Transform delete operation
     */
    private function transformDelete($deleteOp, $concurrentOp) {
        if ($concurrentOp['type'] === 'insert' && $concurrentOp['position'] < $deleteOp['position']) {
            return [
                'type' => 'delete',
                'position' => $deleteOp['position'] + 1,
                'length' => $deleteOp['length']
            ];
        } elseif ($concurrentOp['type'] === 'delete' && $concurrentOp['position'] < $deleteOp['position']) {
            return [
                'type' => 'delete',
                'position' => max($deleteOp['position'] - $concurrentOp['length'], $concurrentOp['position']),
                'length' => $deleteOp['length']
            ];
        }
        
        return $deleteOp;
    }
    
    /**
     * Transform update operation
     */
    private function transformUpdate($updateOp, $concurrentOp) {
        // For updates, we typically don't need transformation unless they affect the same field
        if ($concurrentOp['type'] === 'update' && $concurrentOp['field'] === $updateOp['field']) {
            // Use timestamp to determine which update wins
            if ($concurrentOp['timestamp'] > $updateOp['timestamp']) {
                return $concurrentOp;
            }
        }
        
        return $updateOp;
    }
    
    /**
     * Apply operations to data
     */
    private function applyOperations($data, $operations) {
        $result = $data;
        
        foreach ($operations as $op) {
            switch ($op['type']) {
                case 'insert':
                    $result = $this->applyInsert($result, $op);
                    break;
                case 'delete':
                    $result = $this->applyDelete($result, $op);
                    break;
                case 'update':
                    $result = $this->applyUpdate($result, $op);
                    break;
            }
        }
        
        return $result;
    }
    
    /**
     * Apply insert operation
     */
    private function applyInsert($data, $operation) {
        if (is_string($data)) {
            $before = substr($data, 0, $operation['position']);
            $after = substr($data, $operation['position']);
            return $before . $operation['content'] . $after;
        } elseif (is_array($data)) {
            array_splice($data, $operation['position'], 0, [$operation['content']]);
            return $data;
        }
        
        return $data;
    }
    
    /**
     * Apply delete operation
     */
    private function applyDelete($data, $operation) {
        if (is_string($data)) {
            return substr_replace($data, '', $operation['position'], $operation['length']);
        } elseif (is_array($data)) {
            array_splice($data, $operation['position'], $operation['length']);
            return $data;
        }
        
        return $data;
    }
    
    /**
     * Apply update operation
     */
    private function applyUpdate($data, $operation) {
        if (is_array($data)) {
            $data[$operation['field']] = $operation['value'];
        }
        
        return $data;
    }
    
    /**
     * Apply synchronized changes
     */
    private function applyChanges($resolution) {
        $synchronizedData = [];
        
        // Merge resolved data
        foreach ($resolution as $key => $value) {
            $synchronizedData[$key] = $value;
        }
        
        // Add unchanged local data
        foreach ($this->localData as $key => $value) {
            if (!isset($synchronizedData[$key])) {
                $synchronizedData[$key] = $value;
            }
        }
        
        // Add unchanged remote data
        foreach ($this->remoteData as $key => $value) {
            if (!isset($synchronizedData[$key])) {
                $synchronizedData[$key] = $value;
            }
        }
        
        return $synchronizedData;
    }
    
    /**
     * Update synchronization status
     */
    private function updateSyncStatus($synchronizedData) {
        $this->syncStatus = [
            'last_sync_timestamp' => time(),
            'vector_clock' => $this->vectorClock->getClock(),
            'total_records' => count($synchronizedData),
            'conflicts_resolved' => $this->countResolvedConflicts($synchronizedData),
            'sync_status' => 'completed'
        ];
    }
    
    /**
     * Count resolved conflicts
     */
    private function countResolvedConflicts($data) {
        $count = 0;
        foreach ($data as $key => $value) {
            if (isset($value['conflict_resolved']) && $value['conflict_resolved']) {
                $count++;
            }
        }
        return $count;
    }
    
    /**
     * Get operations since a specific version
     */
    private function getOperationsSince($key, $sinceVersion) {
        $operations = [];
        
        if (isset($this->operationLog[$key])) {
            foreach ($this->operationLog[$key] as $op) {
                if ($op['version'] > $sinceVersion) {
                    $operations[] = $op;
                }
            }
        }
        
        return $operations;
    }
    
    /**
     * Get base data for conflict resolution
     */
    private function getBaseData($key) {
        // Return the most recent common ancestor
        if (isset($this->localData[$key]) && isset($this->remoteData[$key])) {
            $localVersion = $this->localData[$key]['last_sync_version'] ?? 0;
            $remoteVersion = $this->remoteData[$key]['last_sync_version'] ?? 0;
            $commonVersion = min($localVersion, $remoteVersion);
            
            return $this->getDataAtVersion($key, $commonVersion);
        }
        
        return [];
    }
    
    /**
     * Get data at specific version
     */
    private function getDataAtVersion($key, $version) {
        // Mock implementation - in real system, fetch from version history
        return [];
    }
    
    /**
     * Add operation to log
     */
    public function logOperation($key, $operation) {
        if (!isset($this->operationLog[$key])) {
            $this->operationLog[$key] = [];
        }
        
        $operation['timestamp'] = time();
        $operation['id'] = uniqid('op_', true);
        
        $this->operationLog[$key][] = $operation;
        $this->vectorClock->increment('local');
    }
    
    /**
     * Get synchronization status
     */
    public function getSyncStatus() {
        return $this->syncStatus;
    }
    
    /**
     * Export synchronization report
     */
    public function exportSyncReport() {
        return [
            'report_id' => uniqid('sync_report_', true),
            'timestamp' => time(),
            'sync_status' => $this->syncStatus,
            'vector_clock' => $this->vectorClock->getClock(),
            'operation_log_size' => count($this->operationLog),
            'conflict_resolution_stats' => $this->getConflictResolutionStats()
        ];
    }
    
    /**
     * Get conflict resolution statistics
     */
    private function getConflictResolutionStats() {
        $stats = [
            'total_conflicts' => 0,
            'resolved_conflicts' => 0,
            'resolution_methods' => []
        ];
        
        foreach ($this->syncStatus as $key => $value) {
            if (isset($value['conflict_resolved'])) {
                $stats['total_conflicts']++;
                if ($value['conflict_resolved']) {
                    $stats['resolved_conflicts']++;
                }
                
                $method = $value['resolution_method'] ?? 'unknown';
                $stats['resolution_methods'][$method] = ($stats['resolution_methods'][$method] ?? 0) + 1;
            }
        }
        
        return $stats;
    }
}

/**
 * Vector Clock implementation for distributed synchronization
 */
class VectorClock {
    private $clock;
    
    public function __construct() {
        $this->clock = [
            'local' => 0,
            'remote' => 0
        ];
    }
    
    public function increment($node) {
        $this->clock[$node] = ($this->clock[$node] ?? 0) + 1;
    }
    
    public function update($remoteClock) {
        foreach ($remoteClock as $node => $timestamp) {
            $this->clock[$node] = max($this->clock[$node] ?? 0, $timestamp);
        }
    }
    
    public function getClock() {
        return $this->clock;
    }
    
    public function compare($otherClock) {
        $greater = false;
        $less = false;
        
        foreach ($this->clock as $node => $timestamp) {
            $otherTimestamp = $otherClock[$node] ?? 0;
            
            if ($timestamp > $otherTimestamp) {
                $greater = true;
            } elseif ($timestamp < $otherTimestamp) {
                $less = true;
            }
        }
        
        if ($greater && !$less) return 'greater';
        if ($less && !$greater) return 'less';
        if (!$greater && !$less) return 'equal';
        return 'concurrent';
    }
}

/**
 * Conflict Resolution helper class
 */
class ConflictResolver {
    public function resolveConflict($conflict) {
        // Implement various conflict resolution strategies
        switch ($conflict['type']) {
            case 'version_conflict':
                return $this->resolveVersionConflict($conflict);
            case 'data_conflict':
                return $this->resolveDataConflict($conflict);
            default:
                return $this->resolveGenericConflict($conflict);
        }
    }
    
    private function resolveVersionConflict($conflict) {
        // Use last-write-wins strategy
        return $conflict['local_timestamp'] > $conflict['remote_timestamp'] 
            ? $conflict['local_value'] 
            : $conflict['remote_value'];
    }
    
    private function resolveDataConflict($conflict) {
        // Use merge strategy for compatible data
        return $this->mergeData($conflict['local_value'], $conflict['remote_value']);
    }
    
    private function resolveGenericConflict($conflict) {
        // Default to local value
        return $conflict['local_value'] ?? $conflict['remote_value'];
    }
    
    private function mergeData($local, $remote) {
        if (is_array($local) && is_array($remote)) {
            return array_merge_recursive($local, $remote);
        }
        
        return $local;
    }
}
?>
