<?php
/**
 * Razorpay Webhook Handler
 * Receives payment verification callbacks from Razorpay
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/razorpay_config.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$payload = file_get_contents('php://input');
$webhook_signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

// Verify webhook signature
if (!empty(RAZORPAY_KEY_SECRET)) {
    $expected_signature = hash_hmac('sha256', $payload, RAZORPAY_KEY_SECRET);
    if (empty($webhook_signature) || !hash_equals($expected_signature, $webhook_signature)) {
        http_response_code(400);
        exit('Invalid signature');
    }
}

$event = json_decode($payload, true);
if (!$event || !isset($event['event'])) {
    http_response_code(400);
    exit('Invalid payload');
}

$db = getDB();

try {
    switch ($event['event']) {
        case 'payment.captured':
            $payment_entity = $event['payload']['payment']['entity'] ?? [];
            $order_id = $payment_entity['order_id'] ?? '';
            $payment_id = $payment_entity['id'] ?? '';
            
            if ($order_id && $payment_id) {
                // Update payment record
                $stmt = $db->prepare("UPDATE payments SET razorpay_payment_id = ?, status = 'completed' WHERE razorpay_order_id = ? AND status = 'pending'");
                $stmt->execute([$payment_id, $order_id]);
                
                if ($stmt->rowCount() > 0) {
                    // Get payment details
                    $stmt = $db->prepare("SELECT * FROM payments WHERE razorpay_order_id = ?");
                    $stmt->execute([$order_id]);
                    $payment = $stmt->fetch();
                    
                    if ($payment) {
                        // Activate subscription
                        $stmt = $db->prepare("SELECT duration_months FROM plans WHERE id = ?");
                        $stmt->execute([$payment['plan_id']]);
                        $plan = $stmt->fetch();
                        
                        $months = $plan['duration_months'] ?? 12;
                        $stmt = $db->prepare("UPDATE schools SET plan_id = ?, subscription_start = CURDATE(), subscription_end = DATE_ADD(CURDATE(), INTERVAL ? MONTH) WHERE id = ?");
                        $stmt->execute([$payment['plan_id'], $months, $payment['school_id']]);
                        
                        // Update coupon usage
                        if ($payment['coupon_code']) {
                            $db->prepare("UPDATE coupon_codes SET used_count = used_count + 1 WHERE code = ?")->execute([$payment['coupon_code']]);
                        }
                    }
                }
            }
            break;
            
        case 'payment.failed':
            $payment_entity = $event['payload']['payment']['entity'] ?? [];
            $order_id = $payment_entity['order_id'] ?? '';
            
            if ($order_id) {
                $stmt = $db->prepare("UPDATE payments SET status = 'failed' WHERE razorpay_order_id = ? AND status = 'pending'");
                $stmt->execute([$order_id]);
            }
            break;
    }
    
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal error']);
}
