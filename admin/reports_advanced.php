<?php
// ============================================
// ไฟล์: admin/reports_advanced.php
// คำอธิบาย: รายงานขั้นสูง (ภาษี, P&L, Cash Flow)
// ============================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Invoice.php';

requireRole(['admin', 'owner']);

$database = new Database();
$db = $database->getConnection();
$invoice = new Invoice($db);

// เลือกปี
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// ======================================
// 1. รายงานภาษี (Tax Report)
// ======================================
$taxQuery = "SELECT 
                MONTH(i.created_at) as month,
                SUM(i.total_amount) as total_revenue,
                SUM(i.paid_amount) as total_paid,
                COUNT(*) as invoice_count
              FROM invoices i
              WHERE YEAR(i.created_at) = :year
              GROUP BY MONTH(i.created_at)
              ORDER BY MONTH(i.created_at)";

$stmt = $db->prepare($taxQuery);
$stmt->execute([':year' => $selectedYear]);
$taxData = $stmt->fetchAll();

$yearlyRevenue = 0;
$yearlyPaid = 0;
foreach ($taxData as $t) {
    $yearlyRevenue += $t['total_revenue'];
    $yearlyPaid += $t['total_paid'];
}

// ======================================
// 2. Profit & Loss Statement
// ======================================

// รายได้
$revenueQuery = "SELECT 
                    'รายได้จากค่าเช่า' as item,
                    SUM(monthly_rent) as amount
                 FROM invoices 
                 WHERE YEAR(created_at) = :year AND payment_status = 'paid'
                 UNION ALL
                 SELECT 
                    'รายได้จากค่าน้ำ' as item,
                    SUM(water_charge) as amount
                 FROM invoices 
                 WHERE YEAR(created_at) = :year AND payment_status = 'paid'
                 UNION ALL
                 SELECT 
                    'รายได้จากค่าไฟ' as item,
                    SUM(electric_charge) as amount
                 FROM invoices 
                 WHERE YEAR(created_at) = :year AND payment_status = 'paid'
                 UNION ALL
                 SELECT 
                    'รายได้จากค่าขยะ' as item,
                    SUM(garbage_fee) as amount
                 FROM invoices 
                 WHERE YEAR(created_at) = :year AND payment_status = 'paid'";

$stmt = $db->prepare($revenueQuery);
$stmt->execute([':year' => $selectedYear]);
$revenueItems = $stmt->fetchAll();

$totalRevenue = array_sum(array_column($revenueItems, 'amount'));

// ค่าใช้จ่าย (สมมติ - ควรมีตารางแยก)
$expenses = [
    ['item' => 'ค่าน้ำประปา (ต้นทาง)', 'amount' => $totalRevenue * 0.15],
    ['item' => 'ค่าไฟฟ้า (ต้นทาง)', 'amount' => $totalRevenue * 0.20],
    ['item' => 'ค่าซ่อมแซม', 'amount' => $totalRevenue * 0.05],
    ['item' => 'ค่าใช้จ่ายอื่นๆ', 'amount' => $totalRevenue * 0.03]
];

$totalExpenses = array_sum(array_column($expenses, 'amount'));
$netProfit = $totalRevenue - $totalExpenses;
$profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;

// ======================================
// 3. Cash Flow Statement
// ======================================
$cashFlowQuery = "SELECT 
                    MONTH(paid_date) as month,
                    SUM(paid_amount) as cash_in
                  FROM invoices
                  WHERE YEAR(paid_date) = :year 
                  AND payment_status = 'paid'
                  GROUP BY MONTH(paid_date)
                  ORDER BY MONTH(paid_date)";

$stmt = $db->prepare($cashFlowQuery);
$stmt->execute([':year' => $selectedYear]);
$cashFlowData = $stmt->fetchAll();

// กราฟรายได้ต่อเดือน
$monthlyData = array_fill(1, 12, 0);
foreach ($taxData as $t) {
    $monthlyData[$t['month']] = $t['total_paid'];
}

// Export Excel
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="report_' . $selectedYear . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo "<html><meta charset='utf-8'><body>";
    echo "<h1>รายงานการเงิน ปี " . toBuddhistYear($selectedYear) . "</h1>";
    
    echo "<h2>1. รายงานภาษี</h2>";
    echo "<table border='1'>";
    echo "<tr><th>เดือน</th><th>รายได้รวม</th><th>รับชำระแล้ว</th></tr>";
    foreach ($taxData as $t) {
        echo "<tr>";
        echo "<td>" . getThaiMonth($t['month']) . "</td>";
        echo "<td>" . number_format($t['total_revenue'], 2) . "</td>";
        echo "<td>" . number_format($t['total_paid'], 2) . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    echo "<h2>2. งบกำไรขาดทุน (P&L)</h2>";
    echo "<table border='1'>";
    echo "<tr><th colspan='2'>รายได้</th></tr>";
    foreach ($revenueItems as $r) {
        echo "<tr><td>{$r['item']}</td><td>" . number_format($r['amount'], 2) . "</td></tr>";
    }
    echo "<tr><th>รวมรายได้</th><th>" . number_format($totalRevenue, 2) . "</th></tr>";
    echo "<tr><th colspan='2'>ค่าใช้จ่าย</th></tr>";
    foreach ($expenses as $e) {
        echo "<tr><td>{$e['item']}</td><td>" . number_format($e['amount'], 2) . "</td></tr>";
    }
    echo "<tr><th>รวมค่าใช้จ่าย</th><th>" . number_format($totalExpenses, 2) . "</th></tr>";
    echo "<tr><th>กำไรสุทธิ</th><th>" . number_format($netProfit, 2) . "</th></tr>";
    echo "</table>";
    
    echo "</body></html>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานขั้นสูง - ระบบจัดการหอพัก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        @media print {
            .no-print { display: none; }
        }
        .report-card { border-left: 4px solid #0d6efd; }
        .profit { color: #28a745; }
        .loss { color: #dc3545; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-graph-up-arrow"></i> รายงานขั้นสูง
                    </h1>
                    <div class="btn-group no-print">
                        <button onclick="window.print()" class="btn btn-primary">
                            <i class="bi bi-printer"></i> พิมพ์
                        </button>
                        <a href="?year=<?php echo $selectedYear; ?>&export=excel" class="btn btn-success">
                            <i class="bi bi-file-excel"></i> Export Excel
                        </a>
                    </div>
                </div>

                <!-- เลือกปี -->
                <div class="card mb-4 no-print">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-auto">
                                <label class="form-label">เลือกปี:</label>
                            </div>
                            <div class="col-auto">
                                <select name="year" class="form-select" onchange="this.form.submit()">
                                    <?php for ($y = date('Y') - 3; $y <= date('Y'); $y++): ?>
                                        <option value="<?php echo $y; ?>" <?php echo $y == $selectedYear ? 'selected' : ''; ?>>
                                            <?php echo toBuddhistYear($y); ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- สรุปภาพรวม -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <h6 class="card-title">รายได้รวม</h6>
                                <h2>฿<?php echo number_format($yearlyRevenue, 0); ?></h2>
                                <small>ปี <?php echo toBuddhistYear($selectedYear); ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <h6 class="card-title">รับชำระแล้ว</h6>
                                <h2>฿<?php echo number_format($yearlyPaid, 0); ?></h2>
                                <small><?php echo $yearlyRevenue > 0 ? number_format(($yearlyPaid/$yearlyRevenue)*100, 1) : 0; ?>%</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white <?php echo $netProfit > 0 ? 'bg-info' : 'bg-danger'; ?>">
                            <div class="card-body">
                                <h6 class="card-title">กำไรสุทธิ</h6>
                                <h2>฿<?php echo number_format($netProfit, 0); ?></h2>
                                <small>Margin: <?php echo number_format($profitMargin, 1); ?>%</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 1. รายงานภาษี -->
                <div class="card report-card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-receipt"></i> 1. รายงานภาษี (Tax Report)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>เดือน</th>
                                        <th class="text-end">รายได้รวม</th>
                                        <th class="text-end">รับชำระแล้ว</th>
                                        <th class="text-end">จำนวนบิล</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($taxData as $t): ?>
                                        <tr>
                                            <td><?php echo getThaiMonth($t['month']); ?></td>
                                            <td class="text-end">฿<?php echo formatMoney($t['total_revenue']); ?></td>
                                            <td class="text-end text-success">฿<?php echo formatMoney($t['total_paid']); ?></td>
                                            <td class="text-end"><?php echo $t['invoice_count']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td>รวมทั้งปี</td>
                                        <td class="text-end">฿<?php echo formatMoney($yearlyRevenue); ?></td>
                                        <td class="text-end text-success">฿<?php echo formatMoney($yearlyPaid); ?></td>
                                        <td class="text-end"><?php echo array_sum(array_column($taxData, 'invoice_count')); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 2. งบกำไรขาดทุน (P&L) -->
                <div class="card report-card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-bar-chart"></i> 2. งบกำไรขาดทุน (Profit & Loss)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-success">รายได้ (Revenue)</h6>
                                <table class="table table-sm">
                                    <?php foreach ($revenueItems as $r): ?>
                                        <tr>
                                            <td><?php echo $r['item']; ?></td>
                                            <td class="text-end">฿<?php echo formatMoney($r['amount']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="fw-bold table-success">
                                        <td>รวมรายได้</td>
                                        <td class="text-end">฿<?php echo formatMoney($totalRevenue); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-danger">ค่าใช้จ่าย (Expenses)</h6>
                                <table class="table table-sm">
                                    <?php foreach ($expenses as $e): ?>
                                        <tr>
                                            <td><?php echo $e['item']; ?></td>
                                            <td class="text-end">฿<?php echo formatMoney($e['amount']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="fw-bold table-danger">
                                        <td>รวมค่าใช้จ่าย</td>
                                        <td class="text-end">฿<?php echo formatMoney($totalExpenses); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <hr>
                        <div class="text-center">
                            <h4 class="<?php echo $netProfit > 0 ? 'profit' : 'loss'; ?>">
                                กำไรสุทธิ: ฿<?php echo formatMoney($netProfit); ?>
                                <small class="text-muted">(<?php echo number_format($profitMargin, 2); ?>%)</small>
                            </h4>
                        </div>
                    </div>
                </div>

                <!-- 3. กระแสเงินสด (Cash Flow) -->
                <div class="card report-card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-cash-stack"></i> 3. กระแสเงินสด (Cash Flow)
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="cashFlowChart" height="80"></canvas>
                    </div>
                </div>

                <!-- 4. กราฟเปรียบเทียบรายได้ -->
                <div class="card report-card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="bi bi-graph-up"></i> 4. กราฟเปรียบเทียบรายได้ต่อเดือน
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart" height="80"></canvas>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // กราฟรายได้
        const revenueCtx = document.getElementById('revenueChart');
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'],
                datasets: [{
                    label: 'รายได้ (บาท)',
                    data: <?php echo json_encode(array_values($monthlyData)); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '฿' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // กราฟกระแสเงินสด
        const cashFlowCtx = document.getElementById('cashFlowChart');
        new Chart(cashFlowCtx, {
            type: 'line',
            data: {
                labels: ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'],
                datasets: [{
                    label: 'เงินสดรับเข้า',
                    data: <?php echo json_encode(array_values($monthlyData)); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1,
                    fill: true,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '฿' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>