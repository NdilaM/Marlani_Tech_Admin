<?php
// client-quote.php - View Quotes for a Client
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.html');
    exit();
}

require_once 'db.php';

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

// Get all quotes for this client
$stmt = $conn->prepare("SELECT * FROM quotations WHERE client_id = ? ORDER BY created_at DESC");
$stmt->execute([$client_id]);
$quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$first_name = $_SESSION['first_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Marlani Admin - Client Quotes</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Page level plugins -->
    <script src="vendor/chart.js/Chart.min.js"></script>

<style>
/* ===== COPY ALL YOUR INDEX.PHP CSS HERE ===== */
:root {
  --blue: #002a66;
  --indigo: #6610f2;
  --purple: #6f42c1;
  --pink: #e83e8c;
  --red: #e74a3b;
  --orange: #fd7e14;
  --yellow: #f6c23e;
  --green: #1cc88a;
  --teal: #20c9a6;
  --cyan: #36b9cc;
  --white: #fff;
  --gray: #858796;
  --gray-dark: #5a5c69;
  --primary: #4e73df;
  --secondary: #858796;
  --success: #1cc88a;
  --info: #36b9cc;
  --warning: #f6c23e;
  --danger: #e74a3b;
  --light: #f8f9fc;
  --dark: #5a5c69;
  --breakpoint-xs: 0;
  --breakpoint-sm: 576px;
  --breakpoint-md: 768px;
  --breakpoint-lg: 992px;
  --breakpoint-xl: 1200px;
  --font-family-sans-serif: "Nunito", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
  --font-family-monospace: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
}

*, *::before, *::after {
  box-sizing: border-box;
}

html {
  font-family: sans-serif;
  line-height: 1.15;
  -webkit-text-size-adjust: 100%;
  -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
}

/* ... ALL THE SAME CSS AS INDEX.PHP ... */
/* (Copy all the CSS from your index.php here, up to the custom styles) */

/* ===== CUSTOM STYLES FOR CLIENT QUOTE PAGE ===== */
.client-info-box {
    background: #fff;
    padding: 1.5rem;
    border-radius: 0.75rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 2rem;
}
.client-info-box h2 { color: #1e3c72; margin-bottom: 0.5rem; }
.client-info-box .details { display: flex; gap: 2rem; flex-wrap: wrap; color: #666; }
.client-info-box .details span { display: flex; align-items: center; gap: 0.5rem; }

.actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
</style>
</head>
<body id="page-top">
    <div id="wrapper">
        <!-- Sidebar -->
        <div id="sidebar-container"></div>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Topbar - Same as index.php -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <form class="form-inline mr-auto ml-3 navbar-search">
                        <div class="input-group">
                            <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..." aria-label="Search">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="button">
                                    <i class="fas fa-search fa-sm"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                    <ul class="navbar-nav ml-auto">
                        <div class="topbar-divider d-none d-sm-block"></div>
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo htmlspecialchars($first_name); ?></span>
                                <img class="img-profile rounded-circle" src="img/undraw_profile.svg">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> Logout</a>
                            </div>
                        </li>
                    </ul>
                </nav>

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-file-invoice"></i> Client Quotes
                        </h1>
                        <div>
                            <a href="clients.php" class="btn btn-sm btn-secondary shadow-sm">
                                <i class="fas fa-arrow-left"></i> Back to Clients
                            </a>
                            <a href="generate-quote.php?client_id=<?php echo $client_id; ?>" class="btn btn-sm btn-success shadow-sm">
                                <i class="fas fa-plus"></i> New Quote
                            </a>
                        </div>
                    </div>

                    <!-- Client Info -->
                    <div class="client-info-box">
                        <h2><?php echo htmlspecialchars($client['company_name']); ?></h2>
                        <div class="details">
                            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($client['contact_person'] ?? 'N/A'); ?></span>
                            <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($client['email']); ?></span>
                            <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($client['phone'] ?? 'N/A'); ?></span>
                            <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($client['client_code']); ?></span>
                        </div>
                    </div>

                    <!-- Quotes Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">All Quotes</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <?php if (empty($quotes)): ?>
                                            <p class="text-center text-muted">No quotes found for this client.</p>
                                            <p class="text-center"><a href="generate-quote.php?client_id=<?php echo $client_id; ?>" class="btn btn-success">Create First Quote</a></p>
                                        <?php else: ?>
                                        <table class="table table-bordered" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Quote #</th>
                                                    <th>Date</th>
                                                    <th>Expiry</th>
                                                    <th>Total</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($quotes as $quote): ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($quote['quote_number']); ?></strong></td>
                                                    <td><?php echo date('d/m/Y', strtotime($quote['quote_date'])); ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($quote['expiry_date'])); ?></td>
                                                    <td>R <?php echo number_format($quote['grand_total'], 2); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo $quote['status']; ?>">
                                                            <?php echo ucfirst($quote['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="actions">
                                                            <a href="view-quote.php?id=<?php echo $quote['id']; ?>" class="btn btn-sm btn-primary" title="View">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <?php if ($quote['pdf_path']): ?>
                                                            <a href="<?php echo htmlspecialchars($quote['pdf_path']); ?>" target="_blank" class="btn btn-sm btn-success" title="Download PDF">
                                                                <i class="fas fa-file-pdf"></i>
                                                            </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        <?php endif; ?>
                                    </div>
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

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>

    <script>
    $(function() {
        $("#sidebar-container").load("sidebar.html", function() {
            console.log("Sidebar loaded successfully");
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