<?php
// ============================================
// ไฟล์: admin/dashboard.php
// คำอธิบาย: Dashboard สำหรับ Admin/Owner
// ============================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Room.php';
require_once __DIR__ . '/../models/Invoice.php';


requireRole(roles: ['admin', 'owner']);

$database = new Database();
$db = $database->getConnection();

$room = new Room(db: $db);
$invoice = new Invoice(db: $db);

// สถิติห้อง
$roomStats = $room->countByStatus();
$totalRooms = array_sum(array: $roomStats);
$occupiedRooms = $roomStats['occupied'] ?? 0;
$availableRooms = $roomStats['available'] ?? 0;

// สถิติเดือนนี้
$currentMonth = date(format: 'n');
$currentYear = date(format: 'Y');
$monthlySummary = $invoice->getMonthlySummary(month: $currentMonth, year: $currentYear);

// ใบเสร็จที่รอชำระ
$pendingInvoices = $invoice->getAll(filters: ['status' => 'pending', 'month' => $currentMonth, 'year' => $currentYear]);

// ใบเสร็จที่รอตรวจสอบ
$checkingInvoices = $invoice->getAll(filters: ['status' => 'checking']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ระบบจัดการหอพัก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-calendar"></i> <?php echo getThaiMonth($currentMonth) . ' ' . toBuddhistYear($currentYear); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- สถิติแบบการ์ด -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">ห้องทั้งหมด</h6>
                                        <h2 class="mb-0"><?php echo $totalRooms; ?></h2>
                                    </div>
                                    <i class="bi bi-building" style="font-size: 3rem; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">ห้องที่เช่าแล้ว</h6>
                                        <h2 class="mb-0"><?php echo $occupiedRooms; ?></h2>
                                    </div>
                                    <i class="bi bi-check-circle" style="font-size: 3rem; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">ห้องว่าง</h6>
                                        <h2 class="mb-0"><?php echo $availableRooms; ?></h2>
                                    </div>
                                    <i class="bi bi-door-open" style="font-size: 3rem; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">รายได้เดือนนี้</h6>
                                        <h2 class="mb-0">฿<?php echo number_format($monthlySummary['total_paid'] ?? 0); ?></h2>
                                    </div>
                                    <i class="bi bi-cash-coin" style="font-size: 3rem; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- กราฟสรุปยอด -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-bar-chart"></i> สรุปรายได้ประจำเดือน</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="revenueChart" height="80"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-pie-chart"></i> สถานะห้อง</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="roomStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- รายการแจ้งเตือน -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-warning text-white">
                                <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> รอชำระเงิน (<?php echo count($pendingInvoices); ?>)</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>ห้อง</th>
                                                <th>ผู้เช่า</th>
                                                <th>ยอดเงิน</th>
                                                <th>กำหนดชำระ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($pendingInvoices) > 0): ?>
                                                <?php foreach (array_slice($pendingInvoices, 0, 5) as $inv): ?>
                                                    <tr>
                                                        <td><?php echo $inv['room_number']; ?></td>
                                                        <td><?php echo $inv['tenant_name']; ?></td>
                                                        <td>฿<?php echo formatMoney($inv['total_amount']); ?></td>
                                                        <td><?php echo formatThaiDate($inv['due_date']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">ไม่มีรายการ</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="bi bi-clock-history"></i> รอตรวจสอบ (<?php echo count($checkingInvoices); ?>)</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>ห้อง</th>
                                                <th>ผู้เช่า</th>
                                                <th>ยอดเงิน</th>
                                                <th>จัดการ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($checkingInvoices) > 0): ?>
                                                <?php foreach (array_slice($checkingInvoices, 0, 5) as $inv): ?>
                                                    <tr>
                                                        <td><?php echo $inv['room_number']; ?></td>
                                                        <td><?php echo $inv['tenant_name']; ?></td>
                                                        <td>฿<?php echo formatMoney($inv['total_amount']); ?></td>
                                                        <td>
                                                            <a href="payments_verify.php?id=<?php echo $inv['invoice_id']; ?>" 
                                                               class="btn btn-sm btn-primary">
                                                                <i class="bi bi-check-circle"></i> ตรวจสอบ
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">ไม่มีรายการ</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
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
                    data: [35000, 35000, 38000, 35000, 40000, 38000, 42000, 40000, 38000, 45000, 42000, 40000],
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
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

        // กราฟสถานะห้อง
        const roomCtx = document.getElementById('roomStatusChart');
        new Chart(roomCtx, {
            type: 'doughnut',
            data: {
                labels: ['เช่าแล้ว', 'ว่าง', 'ซ่อมแซม'],
                datasets: [{
                    data: [<?php echo $occupiedRooms; ?>, <?php echo $availableRooms; ?>, <?php echo $roomStats['maintenance'] ?? 0; ?>],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(255, 99, 132, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>

