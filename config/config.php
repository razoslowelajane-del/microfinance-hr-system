<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "microfinance";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// PHPMailer configuration
$mail_config = [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'smtp_secure' => 'tls', // tls or ssl
    'smtp_auth' => true,
    'username' => 'suruiz.joshuabcp@gmail.com',  
    'password' => 'aovb dqcb sqve rbsa',     
    'from_email' => 'suruiz.joshuabcp@gmail.com',
    'from_name' => 'Microfinance System',
    'reply_to' => 'suruiz.joshuabcp@gmail.com',
];

// Function to send OTP email using PHPMailer
function sendOtpEmail($toEmail, $otp, $userName = '') {
    global $mail_config;
    
    // Import PHPMailer
    require_once __DIR__ . '/../vendor/autoload.php';
    
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = $mail_config['host'];
        $mail->SMTPAuth = $mail_config['smtp_auth'];
        $mail->Username = $mail_config['username'];
        $mail->Password = $mail_config['password'];
        $mail->SMTPSecure = $mail_config['smtp_secure'];
        $mail->Port = $mail_config['port'];
        
        // Recipients
        $mail->setFrom($mail_config['from_email'], $mail_config['from_name']);
        $mail->addAddress($toEmail);
        $mail->addReplyTo($mail_config['reply_to']);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Microfinance Login OTP Code';
        
        $emailBody = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f4f4f4;'>
            <div style='background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <div style='text-align: center; margin-bottom: 30px;'>
                    <h2 style='color: #2ca078; margin: 0;'>Microfinance System</h2>
                    <p style='color: #666; margin: 5px 0 0 0;'>Secure Login Verification</p>
                </div>
                
                <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;'>
                    <h3 style='color: #333; margin: 0 0 10px 0;'>Your OTP Code</h3>
                    <div style='font-size: 32px; font-weight: bold; color: #2ca078; letter-spacing: 5px; margin: 15px 0;'>
                        $otp
                    </div>
                    <p style='color: #666; margin: 10px 0 0 0; font-size: 14px;'>This code will expire in 10 minutes</p>
                </div>
                
                <div style='margin: 30px 0;'>
                    <h4 style='color: #333; margin: 0 0 10px 0;'>Instructions:</h4>
                    <ol style='color: #666; margin: 0; padding-left: 20px;'>
                        <li>Enter the 6-digit code above in the login verification page</li>
                        <li>Do not share this code with anyone</li>
                        <li>If you didn't request this code, please ignore this email</li>
                    </ol>
                </div>
                
                <div style='border-top: 1px solid #eee; padding-top: 20px; margin-top: 30px; text-align: center;'>
                    <p style='color: #999; font-size: 12px; margin: 0;'>
                        This is an automated message from Microfinance System.<br>
                        Please do not reply to this email.
                    </p>
                </div>
            </div>
        </div>";
        
        $mail->Body = $emailBody;
        $mail->AltBody = "Your OTP code is: $otp\n\nThis code will expire in 10 minutes.\n\nIf you didn't request this code, please ignore this email.";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        // Log error for debugging
        error_log("PHPMailer Error: " . $e->getMessage());
        return false;
    }
}
define('GROQ_API_KEY', 'GROQ_API_KEY');