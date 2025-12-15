<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Verify payment status if payment_link_id is in session or URL parameter
$payment_verified = false;
$secret_key = 'REPLACE_WITH_SECRET';

// Check for payment_link_id in session or URL
$payment_link_id = null;
if (isset($_SESSION['payment_link_id'])) {
    $payment_link_id = $_SESSION['payment_link_id'];
} elseif (isset($_GET['payment_link_id'])) {
    $payment_link_id = $_GET['payment_link_id'];
}

if ($payment_link_id) {
    // Check payment link status using PayMongo API v1
    $ch = curl_init('https://api.paymongo.com/v1/links/' . $payment_link_id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode($secret_key . ':')
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $result = json_decode($response, true);
        if (isset($result['data']['attributes']['status']) && $result['data']['attributes']['status'] === 'paid') {
            $payment_verified = true;
            
            // Save booking and transaction to database
            if (isset($_SESSION['booking_data'])) {
                try {
                    $booking_data = $_SESSION['booking_data'];
                    $user_id = $_SESSION['user_id'];
                    
                    // Get payment details from PayMongo response
                    $payment_amount = isset($result['data']['attributes']['amount']) 
                        ? ($result['data']['attributes']['amount'] / 100) 
                        : $booking_data['totalPrice'];
                    
                    // Generate unique booking ID
                    $booking_id = isset($result['data']['attributes']['metadata']['booking_id']) 
                        ? $result['data']['attributes']['metadata']['booking_id']
                        : 'BK_' . uniqid() . '_' . time();
                    
                    // Generate unique transaction ID
                    $transaction_id = 'TXN_' . uniqid() . '_' . time();
                    
                    // Start transaction
                    $pdo->beginTransaction();
                    
                    // Check if booking already exists (to avoid duplicates)
                    $checkStmt = $pdo->prepare("SELECT id FROM bookings WHERE booking_id = ? AND user_id = ?");
                    $checkStmt->execute([$booking_id, $user_id]);
                    $existingBooking = $checkStmt->fetch();
                    
                    if (!$existingBooking) {
                        // Insert booking
                        $bookingStmt = $pdo->prepare("
                            INSERT INTO bookings (
                                booking_id, user_id, room_id, room_name, 
                                checkin_date, checkout_date, nights, 
                                price_per_night, total_price, 
                                payment_status, payment_link_id
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid', ?)
                        ");
                        
                        $bookingStmt->execute([
                            $booking_id,
                            $user_id,
                            $booking_data['roomId'],
                            $booking_data['roomName'],
                            $booking_data['checkin'],
                            $booking_data['checkout'],
                            $booking_data['nights'],
                            $booking_data['pricePerNight'],
                            $booking_data['totalPrice'],
                            $payment_link_id
                        ]);
                        
                        $booking_db_id = $pdo->lastInsertId();
                        
                        // Insert transaction
                        $transactionStmt = $pdo->prepare("
                            INSERT INTO transactions (
                                transaction_id, booking_id, user_id, payment_link_id,
                                amount, currency, payment_status, paymongo_response
                            ) VALUES (?, ?, ?, ?, ?, 'PHP', 'paid', ?)
                        ");
                        
                        $paymongo_response_json = json_encode($result['data']);
                        
                        $transactionStmt->execute([
                            $transaction_id,
                            $booking_db_id,
                            $user_id,
                            $payment_link_id,
                            $payment_amount,
                            $paymongo_response_json
                        ]);
                        
                        // Commit transaction
                        $pdo->commit();
                        
                        // Store booking ID in session for display
                        $_SESSION['last_booking_id'] = $booking_id;
                        
                        // Send email notification
                        try {
                            require_once 'includes/email_sender.php';
                            $emailSender = new EmailSender();
                            
                            // Get user email
                            $userStmt = $pdo->prepare("SELECT email, username FROM users WHERE id = ?");
                            $userStmt->execute([$user_id]);
                            $user = $userStmt->fetch();
                            
                            if ($user && !empty($user['email'])) {
                                $email_booking_data = [
                                    'booking_id' => $booking_id,
                                    'room_name' => $booking_data['roomName'],
                                    'checkin_date' => $booking_data['checkin'],
                                    'checkout_date' => $booking_data['checkout'],
                                    'nights' => $booking_data['nights'],
                                    'price_per_night' => $booking_data['pricePerNight'],
                                    'total_price' => $booking_data['totalPrice']
                                ];
                                
                                $emailSender->sendBookingConfirmation(
                                    $user['email'],
                                    $user['username'],
                                    $email_booking_data
                                );
                            }
                        } catch (Exception $e) {
                            // Log email error but don't fail the payment
                            error_log("Email sending error: " . $e->getMessage());
                        }
                    } else {
                        // Booking already exists, just update payment status
                        $updateStmt = $pdo->prepare("
                            UPDATE bookings 
                            SET payment_status = 'paid', payment_link_id = ?
                            WHERE id = ?
                        ");
                        $updateStmt->execute([$payment_link_id, $existingBooking['id']]);
                        
                        $pdo->commit();
                        $_SESSION['last_booking_id'] = $booking_id;
                        
                        // Send email notification for existing booking update
                        try {
                            require_once 'includes/email_sender.php';
                            $emailSender = new EmailSender();
                            
                            // Get user email
                            $userStmt = $pdo->prepare("SELECT email, username FROM users WHERE id = ?");
                            $userStmt->execute([$user_id]);
                            $user = $userStmt->fetch();
                            
                            if ($user && !empty($user['email'])) {
                                $email_booking_data = [
                                    'booking_id' => $booking_id,
                                    'room_name' => $booking_data['roomName'],
                                    'checkin_date' => $booking_data['checkin'],
                                    'checkout_date' => $booking_data['checkout'],
                                    'nights' => $booking_data['nights'],
                                    'price_per_night' => $booking_data['pricePerNight'],
                                    'total_price' => $booking_data['totalPrice']
                                ];
                                
                                $emailSender->sendBookingConfirmation(
                                    $user['email'],
                                    $user['username'],
                                    $email_booking_data
                                );
                            }
                        } catch (Exception $e) {
                            // Log email error but don't fail the payment
                            error_log("Email sending error: " . $e->getMessage());
                        }
                    }
                    
                } catch(PDOException $e) {
                    // Rollback on error
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    // Log error but don't show to user
                    error_log("Database error saving booking: " . $e->getMessage());
                }
            }
            
            // Clear session
            unset($_SESSION['payment_link_id']);
            unset($_SESSION['booking_data']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - Dwellscape</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .success-container {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
        }

        .success-icon {
            width: 100px;
            height: 100px;
            background: rgba(16, 185, 129, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: scaleIn 0.5s ease;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .success-icon i {
            font-size: 50px;
            color: #10b981;
        }

        h1 {
            font-size: 32px;
            color: #1f2937;
            margin-bottom: 15px;
        }

        p {
            color: #6b7280;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .btn {
            background: linear-gradient(135deg, #C3B091 0%, #9A8B6F 100%);
            color: white;
            padding: 16px 36px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(195, 176, 145, 0.3);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(195, 176, 145, 0.4);
        }

        .btn i {
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1>Payment Successful!</h1>
        <p>Your booking has been confirmed and payment processed successfully.<?php if (isset($_SESSION['last_booking_id'])) { ?> Your booking reference is: <strong><?php echo htmlspecialchars($_SESSION['last_booking_id']); ?></strong><?php } ?></p>
        <p style="margin-top: 15px; color: #6b7280; font-size: 14px;">A confirmation email has been sent to your registered email address.</p>
        <div style="margin-top: 30px;">
            <a href="dashboard.php" class="btn">
                <i class="fas fa-home"></i> Go to Dashboard
            </a>
        </div>
    </div>
</body>
</html>

