<?php
/**
 * Queue Worker - Background Job Processor
 * 
 * Processes refresh jobs asynchronously from the queue
 * Run this script periodically via cron or as a daemon
 * 
 * Usage: php queue_worker.php
 * 
 * @package RobloxRefresher
 * @version 1.0.0
 */

// Require database
require_once __DIR__ . '/../../config/database.php';

// Check if running from CLI
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

echo "Queue Worker Starting...\n";

class QueueWorker
{
    private int $maxJobs = 10;
    private int $sleepTime = 5; // seconds between checks
    private bool $running = true;
    
    public function __construct()
    {
        // Handle shutdown signals
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'handleShutdown']);
            pcntl_signal(SIGINT, [$this, 'handleShutdown']);
        }
    }
    
    /**
     * Main worker loop
     */
    public function run(): void
    {
        echo "Worker is running. Press Ctrl+C to stop.\n";
        
        while ($this->running) {
            if (!Database::isAvailable()) {
                echo "Database not available. Waiting...\n";
                sleep($this->sleepTime);
                continue;
            }
            
            $this->processJobs();
            
            // Allow signal handling
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
            
            sleep($this->sleepTime);
        }
        
        echo "\nWorker stopped gracefully.\n";
    }
    
    /**
     * Process pending jobs
     */
    private function processJobs(): void
    {
        try {
            // Get pending jobs ordered by priority
            $stmt = Database::execute(
                "SELECT id, cookie_encrypted, ip_address, user_id, attempts, max_attempts
                 FROM queue_jobs
                 WHERE status = 'pending'
                 ORDER BY priority ASC, created_at ASC
                 LIMIT ?",
                [$this->maxJobs]
            );
            
            $jobs = $stmt->fetchAll();
            
            if (empty($jobs)) {
                return;
            }
            
            echo sprintf("[%s] Processing %d jobs...\n", date('Y-m-d H:i:s'), count($jobs));
            
            foreach ($jobs as $job) {
                $this->processJob($job);
            }
        } catch (Exception $e) {
            error_log("Queue worker error: " . $e->getMessage());
        }
    }
    
    /**
     * Process a single job
     * 
     * NOTE: Cookie encryption uses simple base64 encoding for this demo.
     * For production, implement proper encryption using openssl_encrypt:
     * 
     * Encrypt:
     *   $key = getenv('ENCRYPTION_KEY'); // 32-byte key
     *   $iv = random_bytes(16);
     *   $encrypted = openssl_encrypt($cookie, 'AES-256-CBC', $key, 0, $iv);
     *   $cookie_encrypted = base64_encode($iv . $encrypted);
     * 
     * Decrypt:
     *   $data = base64_decode($cookie_encrypted);
     *   $iv = substr($data, 0, 16);
     *   $encrypted = substr($data, 16);
     *   $cookie = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
     */
    private function processJob(array $job): void
    {
        $jobId = $job['id'];
        
        try {
            // Mark as processing
            Database::execute(
                "UPDATE queue_jobs 
                 SET status = 'processing', started_at = NOW() 
                 WHERE id = ?",
                [$jobId]
            );
            
            // Decrypt cookie (simple base64 - in production use proper encryption)
            $cookie = base64_decode($job['cookie_encrypted']);
            
            // Simulate refresh process (in real implementation, call refresh logic)
            // For now, just mark as completed
            $result = $this->simulateRefresh($cookie);
            
            if ($result['success']) {
                // Mark as completed
                Database::execute(
                    "UPDATE queue_jobs 
                     SET status = 'completed', 
                         completed_at = NOW(),
                         result_data = ?
                     WHERE id = ?",
                    [json_encode($result['data']), $jobId]
                );
                
                echo sprintf("  ✓ Job #%d completed\n", $jobId);
            } else {
                $this->handleJobFailure($job, $result['error']);
            }
        } catch (Exception $e) {
            $this->handleJobFailure($job, $e->getMessage());
        }
    }
    
    /**
     * Handle job failure
     */
    private function handleJobFailure(array $job, string $error): void
    {
        $jobId = $job['id'];
        $attempts = $job['attempts'] + 1;
        $maxAttempts = $job['max_attempts'];
        
        if ($attempts >= $maxAttempts) {
            // Max attempts reached, mark as failed
            Database::execute(
                "UPDATE queue_jobs 
                 SET status = 'failed',
                     completed_at = NOW(),
                     attempts = ?,
                     error_message = ?
                 WHERE id = ?",
                [$attempts, $error, $jobId]
            );
            
            echo sprintf("  ✗ Job #%d failed after %d attempts: %s\n", $jobId, $attempts, $error);
        } else {
            // Retry later
            Database::execute(
                "UPDATE queue_jobs 
                 SET status = 'pending',
                     attempts = ?,
                     error_message = ?
                 WHERE id = ?",
                [$attempts, $error, $jobId]
            );
            
            echo sprintf("  ⟳ Job #%d retry %d/%d: %s\n", $jobId, $attempts, $maxAttempts, $error);
        }
    }
    
    /**
     * Simulate refresh process
     * In production, this would call the actual refresh logic
     */
    private function simulateRefresh(string $cookie): array
    {
        // For now, just simulate success/failure
        // In a real implementation, you would:
        // 1. Call the refresh API logic
        // 2. Return the actual result
        
        sleep(1); // Simulate processing time
        
        // Simulate 90% success rate
        if (rand(1, 100) <= 90) {
            return [
                'success' => true,
                'data' => [
                    'cookie' => 'refreshed_cookie_here',
                    'user' => 'test_user'
                ]
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Simulated failure'
            ];
        }
    }
    
    /**
     * Handle shutdown signal
     */
    public function handleShutdown(): void
    {
        echo "\nReceived shutdown signal. Finishing current jobs...\n";
        $this->running = false;
    }
}

// Check database availability
if (!Database::isAvailable()) {
    die("✗ Cannot connect to database. Please check configuration.\n");
}

// Start worker
$worker = new QueueWorker();
$worker->run();
