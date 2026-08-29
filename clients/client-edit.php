<?php
// client-edit.php - Edit Client Details
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.html');
    exit();
}

require_once '../db.php';

$client_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($client_id == 0) {
    header('Location: clients.php');
    exit();
}

// Get client info
$stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    header('Location: clients.php');
    exit();
}

$first_name = $_SESSION['first_name'] ?? 'User';
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $company_name = trim($_POST['company_name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? 'South Africa');
    $industry = trim($_POST['industry'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $client_type = $_POST['client_type'] ?? 'business';
    $notes = trim($_POST['notes'] ?? '');
    
    // Validate
    if (empty($company_name)) $errors[] = "Company name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    
    // Check if email exists (excluding current client)
    if (!empty($email)) {
        $stmt = $conn->prepare("SELECT id FROM clients WHERE email = ? AND id != ?");
        $stmt->execute([$email, $client_id]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Email already exists for another client";
        }
    }
    
    // If no errors, update client
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("
                UPDATE clients SET 
                    company_name = ?,
                    contact_person = ?,
                    email = ?,
                    phone = ?,
                    address = ?,
                    city = ?,
                    province = ?,
                    postal_code = ?,
                    country = ?,
                    industry = ?,
                    status = ?,
                    client_type = ?,
                    notes = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $company_name,
                $contact_person,
                $email,
                $phone,
                $address,
                $city,
                $province,
                $postal_code,
                $country,
                $industry,
                $status,
                $client_type,
                $notes,
                $client_id
            ]);
            
            $_SESSION['success'] = "Client updated successfully!";
            header('Location: client-view.php?id=' . $client_id);
            exit();
            
        } catch(Exception $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Marlani Admin - Edit Client</title>

    <!-- Custom fonts for this template-->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        /* ===== SIDEBAR LOADING FIXES - ORIGINAL STYLE ===== */
        #sidebar-container {
            min-height: 100vh;
            position: sticky;
            top: 0;
            align-self: flex-start;
        }

        #sidebar-container .sidebar {
            min-height: 100vh;
        }

        /* Remove white background from subtabs - original style */
        #sidebar-container .sidebar .nav-item .collapse .collapse-inner {
            background: transparent !important;
        }

        #sidebar-container .sidebar .nav-item .collapse .collapse-inner .collapse-item {
            color: rgba(255,255,255,0.7) !important;
        }

        #sidebar-container .sidebar .nav-item .collapse .collapse-inner .collapse-item:hover {
            background: rgba(255,255,255,0.08) !important;
            color: #fff !important;
        }

        #sidebar-container .sidebar .nav-item .collapse .collapse-inner .collapse-item.active {
            background: rgba(255,255,255,0.15) !important;
            color: #fff !important;
        }

        #sidebar-container .sidebar .nav-item .collapse .collapse-inner .collapse-header {
            color: rgba(255,255,255,0.4) !important;
            border-bottom: 1px solid rgba(255,255,255,0.08) !important;
        }

        /* ===== SIDEBAR OVERLAY FIX ===== */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1040;
            display: none;
        }

        .sidebar-overlay.active {
            display: block;
        }

        /* ===== CUSTOM STYLES ===== */
        .error-messages {
            background: #fee2e2;
            color: #dc2626;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .error-messages ul { padding-left: 1.5rem; }

        .form-group .required { color: #e74a3b; }

        .form-card {
            max-width: 900px;
            margin: 0 auto;
        }

        .btn-warning {
            color: #fff;
            background-color: #f6c23e;
            border-color: #f6c23e;
        }
        .btn-warning:hover {
            color: #fff;
            background-color: #dda20a;
            border-color: #d39a0a;
        }

        .badge-active { background: #dcfce7; color: #16a34a; }
        .badge-pending { background: #fef3c7; color: #d97706; }
        .badge-inactive { background: #fee2e2; color: #dc2626; }
        .badge-suspended { background: #f3f4f6; color: #6b7280; }

        /* ===== SIDEBAR COLLAPSE ON ALL SCREENS ===== */
        #sidebarToggleTop {
            display: inline-flex !important;
        }

        .sidebar.collapsed {
            width: 6.5rem !important;
        }

        .sidebar.collapsed .sidebar-brand-text {
            display: none !important;
        }

        .sidebar.collapsed .nav-link span {
            display: none !important;
        }

        .sidebar.collapsed .nav-link i {
            margin-right: 0 !important;
            font-size: 1.3rem !important;
        }

        .sidebar.collapsed .sidebar-heading {
            text-align: center !important;
            font-size: 0.55rem !important;
        }

        .sidebar.collapsed .sidebar-card {
            display: none !important;
        }

        /* ===== FOOTER ===== */
        footer.sticky-footer {
            margin-top: auto;
            width: 100%;
            height: 36px;
            min-height: 36px;
            padding: 8px 0;
            background: #002a66;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        footer.sticky-footer .copyright {
            margin: 0;
            font-size: 12px;
            line-height: 1;
        }

        /* ===== SIDEBAR FIX - STICKY ===== */
        #sidebar-container {
            display: flex;
            flex-shrink: 0;
            height: 100vh;
            position: sticky;
            top: 0;
            align-self: flex-start;
            overflow: hidden;
        }

        .sidebar {
            position: relative !important;
            height: 100vh !important;
            min-height: 100vh !important;
            width: 6.5rem !important;
            flex-shrink: 0 !important;
            display: flex !important;
            flex-direction: column !important;
        }

        #wrapper {
            display: flex !important;
            align-items: flex-start !important;
        }

        #wrapper #content-wrapper {
            flex: 1 1 auto !important;
            width: auto !important;
            min-width: 0 !important;
            overflow-x: hidden !important;
        }

        @media (min-width: 768px) {
            .sidebar {
                width: 14rem !important;
            }
        }

        @media (max-width: 768px) {
            #sidebar-container {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                height: 100vh !important;
                z-index: 1050 !important;
                width: 0 !important;
                overflow: hidden !important;
                transition: width 0.3s ease !important;
                align-self: auto !important;
            }
            
            #sidebar-container.toggled {
                width: 14rem !important;
            }
            
            .sidebar {
                width: 100% !important;
                height: 100vh !important;
                position: relative !important;
            }
        }

        /* ===== ORIGINAL SIDEBAR DROPDOWN STYLES ===== */
        .sidebar .nav-item .collapse {
            position: absolute;
            left: calc(6.5rem + 1.5rem / 2);
            z-index: 1;
            top: 2px;
        }

        .sidebar .nav-item .collapse .collapse-inner {
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            padding: 0.5rem 0;
            min-width: 12rem;
            font-size: 0.85rem;
            background: #ffffff;
            border: 1px solid #e3e6f0;
        }

        /* For desktop expanded sidebar */
        @media (min-width: 768px) {
            .sidebar .nav-item .collapse {
                position: relative;
                left: 0;
                z-index: 1;
                top: 0;
                animation: none;
            }

            .sidebar .nav-item .collapse .collapse-inner {
                border-radius: 0;
                box-shadow: none;
                background: transparent !important;
                border: none;
                padding: 0.25rem 0;
                min-width: auto;
            }

            .sidebar .nav-item .collapse .collapse-inner .collapse-header {
                color: rgba(255,255,255,0.4) !important;
                border-bottom: 1px solid rgba(255,255,255,0.08) !important;
                padding: 0.5rem 1rem;
                font-size: 0.6rem;
            }

            .sidebar .nav-item .collapse .collapse-inner .collapse-item {
                color: rgba(255,255,255,0.7) !important;
                padding: 0.4rem 1rem;
                border-left: 3px solid transparent;
                font-weight: 400;
            }

            .sidebar .nav-item .collapse .collapse-inner .collapse-item:hover {
                background: rgba(255,255,255,0.08) !important;
                color: #fff !important;
                border-left-color: #fff;
            }

            .sidebar .nav-item .collapse .collapse-inner .collapse-item.active {
                background: rgba(255,255,255,0.15) !important;
                color: #fff !important;
                border-left-color: #fff;
            }

            .sidebar .nav-item .collapsing {
                display: block;
                transition: height 0.15s ease;
            }

            .sidebar .nav-item .collapse,
            .sidebar .nav-item .collapsing {
                margin: 0 1rem;
            }

            .sidebar .nav-item .nav-link[data-toggle="collapse"]::after {
                width: 1rem;
                text-align: center;
                float: right;
                vertical-align: 0;
                border: 0;
                font-weight: 900;
                content: '\f107';
                font-family: 'Font Awesome 5 Free';
                margin-left: auto;
            }

            .sidebar .nav-item .nav-link[data-toggle="collapse"].collapsed::after {
                content: '\f105';
            }
        }

        /* For toggled/collapsed sidebar */
        @media (min-width: 768px) {
            .sidebar.toggled .nav-item .collapse {
                position: absolute;
                left: calc(6.5rem + 0.5rem);
                z-index: 1;
                top: 0;
                animation-name: growIn;
                animation-duration: 200ms;
                animation-timing-function: transform cubic-bezier(0.18, 1.25, 0.4, 1), opacity cubic-bezier(0, 1, 0.4, 1);
            }

            .sidebar.toggled .nav-item .collapse .collapse-inner {
                box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
                border-radius: 0.35rem;
                background: #ffffff !important;
                border: 1px solid #e3e6f0;
                min-width: 12rem;
                padding: 0.5rem 0;
            }

            .sidebar.toggled .nav-item .collapse .collapse-inner .collapse-header {
                color: #b7b9cc !important;
                border-bottom: 1px solid #e3e6f0 !important;
                padding: 0.5rem 1.5rem;
            }

            .sidebar.toggled .nav-item .collapse .collapse-inner .collapse-item {
                color: #3a3b45 !important;
                padding: 0.5rem 1.5rem;
                border-left: 3px solid transparent;
            }

            .sidebar.toggled .nav-item .collapse .collapse-inner .collapse-item:hover {
                background: #f0f4ff !important;
                color: #003986 !important;
                border-left-color: #003986;
            }

            .sidebar.toggled .nav-item .collapse .collapse-inner .collapse-item.active {
                background: #003986 !important;
                color: #ffffff !important;
                border-left-color: #ffffff;
            }

            .sidebar.toggled .nav-item .collapsing {
                display: none;
                transition: none;
            }

            .sidebar.toggled .nav-item .collapse,
            .sidebar.toggled .nav-item .collapsing {
                margin: 0;
            }

            .sidebar.toggled .nav-item .nav-link[data-toggle="collapse"]::after {
                display: none;
            }

            .sidebar.toggled .sidebar-brand .sidebar-brand-icon i {
                font-size: 2rem;
                display: block;
                margin-right: 0;
                margin-bottom: 4px;
            }

            .sidebar.toggled .sidebar-brand .sidebar-brand-text {
                display: none;
            }

            .sidebar.toggled .sidebar-heading {
                text-align: center;
            }

            .sidebar.toggled .sidebar-card {
                display: none;
            }
        }

        @keyframes growIn {
            0% { transform: scale(0.9); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        .animated--grow-in {
            animation-name: growIn;
            animation-duration: 200ms;
            animation-timing-function: transform cubic-bezier(0.18, 1.25, 0.4, 1), opacity cubic-bezier(0, 1, 0.4, 1);
        }
    </style>
</head>
<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <div id="sidebar-container"></div>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <!-- Sidebar Toggle (Topbar) - Always visible on all screens -->
                    <button id="sidebarToggleTop" class="btn btn-link rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-edit"></i> Edit Client
                    </h1>

                    <ul class="navbar-nav ml-auto">
                        <div class="topbar-divider d-none d-sm-block"></div>
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php echo htmlspecialchars($first_name); ?>
                                </span>
                                <img class="img-profile rounded-circle" src="../img/undraw_profile.svg">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Profile
                                </a>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Settings
                                </a>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Activity Log
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="../logout.php">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
                  
                    <!-- Client Code Display -->
                    <div class="row justify-content-center">
                        <div class="col-lg-9">
                            <div class="alert alert-info">
                                <i class="fas fa-tag"></i> <strong>Client Code:</strong> <?php echo htmlspecialchars($client['client_code']); ?>
                                <span class="float-right">
                                    <span class="badge badge-<?php echo $client['status']; ?>">
                                        <?php echo ucfirst($client['status']); ?>
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Form Card -->
                    <div class="row justify-content-center">
                        <div class="col-lg-9">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Client Information</h6>
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

                                    <form method="POST">
                                        <!-- Company Name -->
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Company Name <span class="required">*</span></label>
                                                <input type="text" class="form-control" name="company_name" required value="<?php echo htmlspecialchars($client['company_name']); ?>" placeholder="Enter company name">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Contact Person</label>
                                                <input type="text" class="form-control" name="contact_person" value="<?php echo htmlspecialchars($client['contact_person'] ?? ''); ?>" placeholder="Enter contact person name">
                                            </div>
                                        </div>

                                        <!-- Email & Phone -->
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Email <span class="required">*</span></label>
                                                <input type="email" class="form-control" name="email" required value="<?php echo htmlspecialchars($client['email']); ?>" placeholder="Enter email address">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Phone</label>
                                                <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($client['phone'] ?? ''); ?>" placeholder="Enter phone number">
                                            </div>
                                        </div>

                                        <!-- Address -->
                                        <div class="form-group">
                                            <label>Address</label>
                                            <textarea class="form-control" name="address" rows="2" placeholder="Enter street address"><?php echo htmlspecialchars($client['address'] ?? ''); ?></textarea>
                                        </div>

                                        <!-- City, Province, Postal Code -->
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label>City</label>
                                                <input type="text" class="form-control" name="city" value="<?php echo htmlspecialchars($client['city'] ?? ''); ?>" placeholder="Enter city">
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label>Province</label>
                                                <input type="text" class="form-control" name="province" value="<?php echo htmlspecialchars($client['province'] ?? ''); ?>" placeholder="Enter province">
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label>Postal Code</label>
                                                <input type="text" class="form-control" name="postal_code" value="<?php echo htmlspecialchars($client['postal_code'] ?? ''); ?>" placeholder="Enter postal code">
                                            </div>
                                        </div>

                                        <!-- Country & Industry -->
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Country</label>
                                                <input type="text" class="form-control" name="country" value="<?php echo htmlspecialchars($client['country'] ?? 'South Africa'); ?>" placeholder="Enter country">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Industry</label>
                                                <input type="text" class="form-control" name="industry" value="<?php echo htmlspecialchars($client['industry'] ?? ''); ?>" placeholder="e.g. Technology, Finance, Healthcare">
                                            </div>
                                        </div>

                                        <!-- Client Type & Status -->
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Client Type</label>
                                                <select class="form-control" name="client_type">
                                                    <option value="business" <?php echo ($client['client_type'] == 'business') ? 'selected' : ''; ?>>Business</option>
                                                    <option value="individual" <?php echo ($client['client_type'] == 'individual') ? 'selected' : ''; ?>>Individual</option>
                                                    <option value="government" <?php echo ($client['client_type'] == 'government') ? 'selected' : ''; ?>>Government</option>
                                                    <option value="non-profit" <?php echo ($client['client_type'] == 'non-profit') ? 'selected' : ''; ?>>Non-Profit</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Status</label>
                                                <select class="form-control" name="status">
                                                    <option value="active" <?php echo ($client['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                                    <option value="pending" <?php echo ($client['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="inactive" <?php echo ($client['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                                    <option value="suspended" <?php echo ($client['status'] == 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div style="display:flex; gap:1rem; margin-top:1.5rem; padding-top:1rem; border-top:1px solid #e3e6f0;">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-save"></i> Update Client
                                            </button>
                                            <a href="client-view.php?id=<?php echo $client_id; ?>" class="btn btn-secondary">
                                                <i class="fas fa-times"></i> Cancel
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End of container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-blue">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Marlani Technologies <?php echo date('Y'); ?></span>
                    </div>
                </div>
            </footer>
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Bootstrap core JavaScript-->
    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="../js/sb-admin-2.min.js"></script>

    <script>
    // Load sidebar from external file
    $(function() {
        $("#sidebar-container").load("../sidebar.html", function() {
            console.log("Sidebar loaded successfully");
            
            // Set active state for current page
            $('#sidebar-container .nav-item').removeClass('active');
            $('#sidebar-container .nav-item a[href*="client-edit"]').closest('.nav-item').addClass('active');
            
            // Find and highlight the parent menu if in submenu
            $('#sidebar-container .nav-item .collapse .collapse-item').each(function() {
                if ($(this).attr('href') && $(this).attr('href').includes('client-edit')) {
                    $(this).addClass('active');
                    $(this).closest('.collapse').addClass('show');
                    $(this).closest('.nav-item').find('.nav-link').removeClass('collapsed');
                }
            });
            
            // Sidebar toggle for all screen sizes - no effects
            $('#sidebarToggleTop').on('click', function(e) {
                e.preventDefault();
                
                // Toggle the sidebar container
                $('#sidebar-container').toggleClass('toggled');
                
                // Toggle the sidebar collapse class for desktop
                $('.sidebar').toggleClass('collapsed');
                
                // Add overlay for mobile
                if ($('#sidebar-container').hasClass('toggled')) {
                    if ($(window).width() <= 768) {
                        $('body').append('<div class="sidebar-overlay active"></div>');
                    }
                } else {
                    $('.sidebar-overlay').remove();
                }
            });
            
            // Close sidebar on overlay click
            $(document).on('click', '.sidebar-overlay', function() {
                $('#sidebar-container').removeClass('toggled');
                $('.sidebar').removeClass('collapsed');
                $('.sidebar-overlay').remove();
            });
            
            // Handle window resize
            $(window).resize(function() {
                if ($(window).width() > 768) {
                    $('#sidebar-container').removeClass('toggled');
                    $('.sidebar-overlay').remove();
                }
            });
        });
    });
    </script>
</body>
</html>