<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Status</title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: "Poppins", sans-serif;
            background: linear-gradient(135deg, #0d1117, #1e293b);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #e2e8f0;
        }
        .message-container {
            width: 100%;
            max-width: 500px;
            padding: 40px;
            background: linear-gradient(145deg, #1e293b, #111827);
            border-radius: 18px;
            box-shadow: 0 8px 25px rgba(244, 63, 94, 0.3);
            text-align: center;
        }
        .suspended .icon { color: #f87171; }
        .terminated .icon { color: #ef4444; }
        .suspended h2 { color: #f87171; }
        .terminated h2 { color: #ef4444; }
        .suspended { box-shadow: 0 8px 25px rgba(244, 63, 94, 0.3); }
        .terminated { box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3); }
        .icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        h2 {
            font-weight: 700;
            margin-bottom: 20px;
        }
        p {
            color: #e2e8f0;
            margin-bottom: 25px;
            font-size: 1.1rem;
        }
        .btn-secondary {
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            background: #475569;
            border: none;
            transition: all 0.3s ease;
        }
        .btn-secondary:hover {
            background: #64748b;
            box-shadow: 0 0 12px rgba(100, 116, 139, 0.5);
        }
    </style>
</head>
<body>
    <?php
    $status = isset($_SESSION['account_status']) ? $_SESSION['account_status'] : 'suspended';
    $message = isset($_SESSION['status_message']) ? $_SESSION['status_message'] : 'Your account access has been restricted.';
    ?>
    
    <div class="message-container <?php echo $status; ?>">
        <div class="icon">
            <?php if($status === 'suspended'): ?>
                <i class="fas fa-user-slash"></i>
            <?php else: ?>
                <i class="fas fa-ban"></i>
            <?php endif; ?>
        </div>
        <h2><?php echo strtoupper($status) . ' ACCOUNT'; ?></h2>
        <p><?php echo $message; ?></p>
        <a href="logout.php" class="btn btn-secondary">Return to Login</a>
    </div>
</body>
</html>