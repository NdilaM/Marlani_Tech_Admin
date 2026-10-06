<?php
$current = basename($_SERVER['PHP_SELF']);
$tabs = [
    'Overview.php'     => ['Overview',   'fa-chart-line'],
    'Invoices.php'    => ['Invoices',   'fa-file-invoice'],
    'Outstanding.php' => ['Outstanding','fa-exclamation-circle'],
    'Payments.php'    => ['Payments',   'fa-money-bill-wave'],
    'finance_reports.php'     => ['Reports',    'fa-chart-bar'],
    'salaries.php'    => ['Salaries',   'fa-user-tie'],
];
?>
<div class="tabs">
    <?php foreach ($tabs as $file => [$label, $icon]): ?>
        <a class="tab<?= $current === $file ? ' active' : '' ?>" href="<?= $file ?>">
            <i class="fas <?= $icon ?>"></i> <?= $label ?>
        </a>
    <?php endforeach; ?>
</div>