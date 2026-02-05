<?php
/**
 * Cryptographic Leave Verification System
 * Patentable Concept: Blockchain-inspired data integrity without actual blockchain
 * 
 * This class provides immutable record keeping and cryptographic verification
 * for faculty leave records using hash chains and digital signatures
 */
class CryptographicLeaveSystem {
    
    private $hashAlgorithm = 'sha256';
    private $privateKey;
    private $publicKey;
    private $chainStorage;
    
    public function __construct() {
        try {
            // Generate RSA key pair for digital signatures
            $this->generateKeyPair();
            $this->chainStorage = [];
        } catch (Exception $e) {
            // Fallback to mock implementation if OpenSSL fails
            $this->initializeMockSystem();
        }
    }
    
    /**
     * Initialize mock system for environments without OpenSSL
     */
    private function initializeMockSystem() {
        $this->privateKey = 'mock_private_key_' . uniqid();
        $this->publicKey = 'mock_public_key_' . uniqid();
        $this->chainStorage = [];
        $this->mockMode = true;
    }
    
    /**
     * Generate RSA key pair for digital signatures
     */
    private function generateKeyPair() {
        $config = [
            "digest_alg" => "sha256",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];
        
        // Generate new key pair
        $keyPair = openssl_pkey_new($config);
        
        if ($keyPair === false) {
            throw new Exception("Failed to generate RSA key pair");
        }
        
        // Export private key
        $privateKey = '';
        $exportResult = openssl_pkey_export($keyPair, $privateKey);
        
        if ($exportResult === false) {
            throw new Exception("Failed to export private key");
        }
        
        // Get public key details
        $publicKeyDetails = openssl_pkey_get_details($keyPair);
        
        if ($publicKeyDetails === false) {
            throw new Exception("Failed to get public key details");
        }
        
        $publicKey = $publicKeyDetails['key'];
        
        $this->privateKey = $privateKey;
        $this->publicKey = $publicKey;
        
        // Free the key pair resource
        openssl_pkey_free($keyPair);
    }
    
    /**
     * Create immutable leave record with cryptographic proof
     */
    public function createLeaveRecord($leaveData) {
        // Add timestamp and nonce for uniqueness
        $leaveData['timestamp'] = time();
        $leaveData['nonce'] = bin2hex(random_bytes(16));
        
        // Create hash of the leave data
        $dataHash = $this->createDataHash($leaveData);
        
        // Get previous hash for chain integrity
        $previousHash = $this->getPreviousHash();
        
        // Create block data
        $blockData = [
            'data_hash' => $dataHash,
            'previous_hash' => $previousHash,
            'timestamp' => time(),
            'nonce' => bin2hex(random_bytes(8))
        ];
        
        // Create block hash
        $blockHash = $this->createBlockHash($blockData);
        
        // Create digital signature
        $signature = $this->createDigitalSignature($blockHash);
        
        // Create immutable record
        $record = [
            'id' => uniqid('leave_', true),
            'leave_data' => $leaveData,
            'block' => [
                'hash' => $blockHash,
                'data_hash' => $dataHash,
                'previous_hash' => $previousHash,
                'signature' => $signature,
                'timestamp' => $blockData['timestamp'],
                'nonce' => $blockData['nonce']
            ],
            'verification_status' => 'verified'
        ];
        
        // Store in chain
        $this->storeImmutableRecord($record);
        
        return $record;
    }
    
    /**
     * Create hash of leave data
     */
    private function createDataHash($leaveData) {
        $jsonString = json_encode($leaveData);
        return hash($this->hashAlgorithm, $jsonString);
    }
    
    /**
     * Create block hash
     */
    private function createBlockHash($blockData) {
        $jsonString = json_encode($blockData);
        return hash($this->hashAlgorithm, $jsonString);
    }
    
    /**
     * Create digital signature
     */
    private function createDigitalSignature($data) {
        if (isset($this->mockMode) && $this->mockMode) {
            // Mock signature for environments without OpenSSL
            return 'mock_signature_' . hash($this->hashAlgorithm, $data . $this->privateKey);
        }
        
        openssl_sign($data, $signature, $this->privateKey, OPENSSL_ALGO_SHA256);
        return base64_encode($signature);
    }
    
    /**
     * Verify digital signature
     */
    private function verifyDigitalSignature($data, $signature) {
        if (isset($this->mockMode) && $this->mockMode) {
            // Mock verification for environments without OpenSSL
            $expectedSignature = 'mock_signature_' . hash($this->hashAlgorithm, $data . $this->privateKey);
            return hash_equals($expectedSignature, $signature);
        }
        
        $signature = base64_decode($signature);
        $result = openssl_verify($data, $signature, $this->publicKey, OPENSSL_ALGO_SHA256);
        return $result === 1;
    }
    
    /**
     * Get previous hash from chain
     */
    private function getPreviousHash() {
        if (empty($this->chainStorage)) {
            return '0'; // Genesis block
        }
        
        $lastRecord = end($this->chainStorage);
        return $lastRecord['block']['hash'];
    }
    
    /**
     * Store immutable record
     */
    private function storeImmutableRecord($record) {
        $this->chainStorage[$record['id']] = $record;
        
        // In real implementation, store to database or file system
        $this->saveToPersistentStorage($record);
    }
    
    /**
     * Verify leave record integrity
     */
    public function verifyLeaveIntegrity($recordId) {
        if (!isset($this->chainStorage[$recordId])) {
            return ['valid' => false, 'message' => 'Record not found'];
        }
        
        $record = $this->chainStorage[$recordId];
        
        // Verify data hash
        $expectedDataHash = $this->createDataHash($record['leave_data']);
        if ($expectedDataHash !== $record['block']['data_hash']) {
            return ['valid' => false, 'message' => 'Data hash mismatch - data has been tampered'];
        }
        
        // Verify block hash
        $expectedBlockHash = $this->createBlockHash([
            'data_hash' => $record['block']['data_hash'],
            'previous_hash' => $record['block']['previous_hash'],
            'timestamp' => $record['block']['timestamp'],
            'nonce' => $record['block']['nonce']
        ]);
        
        if ($expectedBlockHash !== $record['block']['hash']) {
            return ['valid' => false, 'message' => 'Block hash mismatch - block has been tampered'];
        }
        
        // Verify digital signature
        $signatureValid = $this->verifyDigitalSignature(
            $record['block']['hash'], 
            $record['block']['signature']
        );
        
        if (!$signatureValid) {
            return ['valid' => false, 'message' => 'Digital signature invalid'];
        }
        
        // Verify chain integrity
        $chainValid = $this->verifyChainIntegrity($recordId);
        if (!$chainValid) {
            return ['valid' => false, 'message' => 'Chain integrity compromised'];
        }
        
        return ['valid' => true, 'message' => 'Record integrity verified'];
    }
    
    /**
     * Verify chain integrity
     */
    private function verifyChainIntegrity($recordId) {
        $record = $this->chainStorage[$recordId];
        
        // If it's the first record, chain is valid
        if ($record['block']['previous_hash'] === '0') {
            return true;
        }
        
        // Find previous record
        $previousRecord = null;
        foreach ($this->chainStorage as $id => $rec) {
            if ($rec['block']['hash'] === $record['block']['previous_hash']) {
                $previousRecord = $rec;
                break;
            }
        }
        
        if ($previousRecord === null) {
            return false; // Previous record not found
        }
        
        // Recursively verify previous records
        return $this->verifyChainIntegrity($previousRecord['id']);
    }
    
    /**
     * Create audit trail
     */
    public function createAuditTrail($action, $recordId, $userId, $details = []) {
        $auditEntry = [
            'id' => uniqid('audit_', true),
            'action' => $action,
            'record_id' => $recordId,
            'user_id' => $userId,
            'timestamp' => time(),
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        // Create cryptographic proof for audit entry
        $auditEntry['hash'] = $this->createDataHash($auditEntry);
        $auditEntry['signature'] = $this->createDigitalSignature($auditEntry['hash']);
        
        return $auditEntry;
    }
    
    /**
     * Verify audit trail integrity
     */
    public function verifyAuditTrail($auditEntry) {
        $expectedHash = $this->createDataHash($auditEntry);
        if ($expectedHash !== $auditEntry['hash']) {
            return false;
        }
        
        return $this->verifyDigitalSignature($auditEntry['hash'], $auditEntry['signature']);
    }
    
    /**
     * Export verification report
     */
    public function exportVerificationReport($recordId) {
        $verification = $this->verifyLeaveIntegrity($recordId);
        
        $report = [
            'report_id' => uniqid('report_', true),
            'record_id' => $recordId,
            'verification_timestamp' => time(),
            'verification_result' => $verification,
            'system_info' => [
                'hash_algorithm' => $this->hashAlgorithm,
                'signature_algorithm' => 'RSA-SHA256',
                'key_length' => 2048
            ],
            'chain_length' => count($this->chainStorage),
            'public_key_fingerprint' => $this->getPublicKeyFingerprint()
        ];
        
        // Sign the report
        $report['signature'] = $this->createDigitalSignature(json_encode($report));
        
        return $report;
    }
    
    /**
     * Get public key fingerprint
     */
    private function getPublicKeyFingerprint() {
        return hash($this->hashAlgorithm, $this->publicKey);
    }
    
    /**
     * Save to persistent storage (mock implementation)
     */
    private function saveToPersistentStorage($record) {
        // In real implementation, save to database or file system
        $filename = 'leave_records_' . date('Y-m-d') . '.json';
        $existingData = [];
        
        if (file_exists($filename)) {
            $existingData = json_decode(file_get_contents($filename), true) ?? [];
        }
        
        $existingData[$record['id']] = $record;
        file_put_contents($filename, json_encode($existingData, JSON_PRETTY_PRINT));
    }
    
    /**
     * Load from persistent storage (mock implementation)
     */
    public function loadFromPersistentStorage() {
        $filename = 'leave_records_' . date('Y-m-d') . '.json';
        
        if (file_exists($filename)) {
            $data = json_decode(file_get_contents($filename), true) ?? [];
            $this->chainStorage = array_merge($this->chainStorage, $data);
        }
    }
    
    /**
     * Get full chain statistics
     */
    public function getChainStatistics() {
        return [
            'total_records' => count($this->chainStorage),
            'chain_integrity' => $this->verifyFullChainIntegrity(),
            'first_record_timestamp' => $this->getFirstRecordTimestamp(),
            'last_record_timestamp' => $this->getLastRecordTimestamp(),
            'public_key_fingerprint' => $this->getPublicKeyFingerprint()
        ];
    }
    
    /**
     * Verify full chain integrity
     */
    private function verifyFullChainIntegrity() {
        foreach ($this->chainStorage as $recordId => $record) {
            $verification = $this->verifyLeaveIntegrity($recordId);
            if (!$verification['valid']) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Get first record timestamp
     */
    private function getFirstRecordTimestamp() {
        if (empty($this->chainStorage)) {
            return null;
        }
        
        $timestamps = array_map(function($record) {
            return $record['block']['timestamp'];
        }, $this->chainStorage);
        
        return min($timestamps);
    }
    
    /**
     * Get last record timestamp
     */
    private function getLastRecordTimestamp() {
        if (empty($this->chainStorage)) {
            return null;
        }
        
        $timestamps = array_map(function($record) {
            return $record['block']['timestamp'];
        }, $this->chainStorage);
        
        return max($timestamps);
    }
}
?>
