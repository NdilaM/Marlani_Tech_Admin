<?php
// staff-remove.php - Terminate Staff (Soft Delete)
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.html');
    exit();
}

// Include database connection
require_once '../db.php';

// Get user data from session
$staff_id = $_SESSION['staff_id'] ?? 1;
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_staff_id = intval($_POST['staff_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    
    // Validate
    if ($target_staff_id <= 0) {
        $errors[] = "Invalid staff member selected";
    }
    
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            // 1. Get staff data first
            $stmt = $conn->prepare("SELECT * FROM staff WHERE id = ?");
            $stmt->execute([$target_staff_id]);
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$staff) {
                throw new Exception("Staff member not found");
            }
            
            // 2. Insert into terminated_staff table
            $stmt = $conn->prepare("
                INSERT INTO terminated_staff 
                (original_staff_id, first_name, last_name, email, phone_no, staff_type, terminated_by, reason)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $staff['id'],
                $staff['first_name'],
                $staff['last_name'],
                $staff['email'],
                $staff['phone_no'] ?? null,
                $staff['staff_type'] ?? null,
                $staff_id,
                $reason
            ]);
            
            // 3. Update staff status to 'terminated' (soft delete)
            $stmt = $conn->prepare("
                UPDATE staff 
                SET status = 'terminated', 
                    terminated_at = NOW(),
                    terminated_by = ?,
                    termination_reason = ?
                WHERE id = ?
            ");
            
            $stmt->execute([$staff_id, $reason, $target_staff_id]);
            
            $conn->commit();
            
            $_SESSION['success'] = "Staff member terminated successfully!";
            header('Location: view-staff.php');
            exit();
            
        } catch(Exception $e) {
            $conn->rollBack();
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}

// If GET request, show confirmation page
$target_staff_id = intval($_GET['id'] ?? 0);
$staff = null;

if ($target_staff_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM staff WHERE id = ? AND status != 'terminated'");
    $stmt->execute([$target_staff_id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Marlani Admin - Terminate Staff</title>

    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        /* Reuse styles from your existing pages */
        :root {
            --blue: #002a66;
            --primary: #4e73df;
            --success: #1cc88a;
            --danger: #e74a3b;
            --warning: #f6c23e;
        }
        
        body {
            font-family: "Nunito", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #f8f9fc;
            color: #858796;
        }
        
        .form-card {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .error-messages {
            background: #fee2e2;
            color: #dc2626;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .error-messages ul { padding-left: 1.5rem; margin: 0; }
        
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .warning-box i {
            color: #dc3545;
            font-size: 1.5rem;
            margin-right: 0.5rem;
        }
        
        .staff-details {
            background: #f8f9fc;
            border: 1px solid #e3e6f0;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .staff-details .row {
            margin-bottom: 0.5rem;
        }
        
        .staff-details label {
            font-weight: 700;
            color: #5a5c69;
            margin-bottom: 0;
        }
        
        .required { color: #e74a3b; }
        
        .btn-terminate {
            color: #fff;
            background-color: #e74a3b;
            border-color: #e74a3b;
        }
        .btn-terminate:hover {
            color: #fff;
            background-color: #c0392b;
            border-color: #b03024;
        }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <div id="sidebar-container"></div>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-user-slash"></i> Terminate Staff Member
                    </h1>

                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'User'); ?>
                                </span>
                                <img class="img-profile rounded-circle" src="../img/undraw_profile.svg">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in">
                                <a class="dropdown-item" href="../logout.php">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <a href="view-staff.php" class="btn btn-sm btn-secondary shadow-sm">
                            <i class="fas fa-arrow-left"></i> Back to View Staff
                        </a>
                    </div>

                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-danger">
                                        <i class="fas fa-exclamation-triangle"></i> Confirm Staff Termination
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($errors)): ?>
                                    <div class="error-messages">
                                        <ul>
                                            <?php foreach ($errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($staff): ?>
                                    <div class="warning-box">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <strong>Warning:</strong> This action will terminate the staff member's account.
                                        The record will be moved to the Terminated Staff table and cannot be undone.
                                    </div>

                                    <form method="POST" id="terminateForm">
                                        <input type="hidden" name="staff_id" value="<?php echo $staff['id']; ?>">
                                        
                                        <div class="staff-details">
                                            <h6 class="font-weight-bold text-gray-800 mb-3">Staff Member Details</h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label>Name:</label>
                                                    <p><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Email:</label>
                                                    <p><?php echo htmlspecialchars($staff['email']); ?></p>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label>Staff Type:</label>
                                                    <p><?php echo htmlspecialchars($staff['staff_type'] ?? 'N/A'); ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Current Status:</label>
                                                    <p>
                                                        <span class="badge badge-<?php 
                                                            echo $staff['status'] === 'active' ? 'success' : 
                                                                ($staff['status'] === 'suspended' ? 'warning' : 'secondary'); 
                                                        ?>">
                                                            <?php echo ucfirst($staff['status']); ?>
                                                        </span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Reason for Termination <span class="required">*</span></label>
                                            <textarea class="form-control" name="reason" rows="3" required 
                                                placeholder="Please provide a reason for terminating this staff member..."></textarea>
                                        </div>

                                        <div style="display:flex; gap:1rem; margin-top:1.5rem; padding-top:1rem; border-top:1px solid #e3e6f0;">
                                            <button type="submit" class="btn btn-terminate" 
                                                onclick="return confirm('Are you sure you want to terminate this staff member? This action cannot be undone.');">
                                                <i class="fas fa-user-slash"></i> Terminate Staff
                                            </button>
                                            <a href="view-staff.php" class="btn btn-secondary">
                                                <i class="fas fa-times"></i> Cancel
                                            </a>
                                        </div>
                                    </form>
                                    <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Staff member not found or already terminated.
                                    </div>
                                    <a href="view-staff.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Back to View Staff
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <footer class="sticky-footer bg-blue">
                    <div class="copyright text-center">
                        <span>Copyright &copy; Marlani Technologies 2026</span>
                    </div>
                </footer>
            </div>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../js/sb-admin-2.min.js"></script>

    <script>
    $(function() {
        $("#sidebar-container").load("../sidebar.php", function() {
            $('#sidebarToggleTop').on('click', function() {
                $('#sidebar-container').toggleClass('toggled');
                if ($('#sidebar-container').hasClass('toggled')) {
                    $('body').append('<div class="sidebar-overlay active"></div>');
                } else {
                    $('.sidebar-overlay').remove();
                }
            });
            
            $(document).on('click', '.sidebar-overlay', function() {
                $('#sidebar-container').removeClass('toggled');
                $('.sidebar-overlay').remove();
            });
        });
    });
    </script>
</body>
</html>