<?php
// ไฟล์: member/parcels.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../models/Tenant.php';
require_once __DIR__ . '/../models/Parcel.php';

requireRole('member');

$database = new Database();
$db = $database->getConnection();

$tenant = new Tenant($db);
$parcel = new Parcel($db);

// ดึงข้อมูลผู้เช่า
$tenantData = $tenant->getByUserId($_SESSION['user_id']);
if (!$tenantData) die('ไม่พบข้อมูลผู้เช่า');

// ดึงรายการพัสดุ
$myParcels = $parcel->getParcelsByTenant($tenantData['tenant_id']);

// แยกกลุ่มพัสดุ (รอรับ / รับแล้ว)
$waiting = [];
$history = [];

foreach ($myParcels as $p) {
    if ($p['parcel_status'] == 'waiting') {
        $waiting[] = $p;
    } else {
        $history[] = $p;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>พัสดุของฉัน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .parcel-card {
            border-left: 5px solid #ddd;
            transition: transform 0.2s;
        }
        .parcel-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .status-waiting { border-left-color: #ffc107; background-color: #fff9e6; }
        .status-picked { border-left-color: #198754; background-color: #f8fffb; }
        
        .icon-box {
            width: 50px; height: 50px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 50%;
            font-size: 1.5rem;
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-box-seam-fill text-primary"></i> พัสดุของฉัน</h2>
            <p class="text-muted mb-0">รายการพัสดุทั้งหมดที่ส่งถึงห้องคุณ</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> กลับหน้าหลัก
        </a>
    </div>

    <?php if (count($waiting) > 0): ?>
        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-circle-fill fs-4 me-3"></i>
            <div>
                <strong>มีพัสดุรอรับ <?php echo count($waiting); ?> ชิ้น!</strong>
                <br>กรุณาติดต่อรับที่นิติบุคคล
            </div>
        </div>
    <?php endif; ?>

    <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="pills-wait-tab" data-bs-toggle="pill" data-bs-target="#pills-wait" type="button">
                รอรับ (<?php echo count($waiting); ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-hist-tab" data-bs-toggle="pill" data-bs-target="#pills-hist" type="button">
                ประวัติรับแล้ว (<?php echo count($history); ?>)
            </button>
        </li>
    </ul>

    <div class="tab-content" id="pills-tabContent">
        
        <div class="tab-pane fade show active" id="pills-wait">
            <?php if (empty($waiting)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-box2 fs-1"></i>
                    <p class="mt-2">ไม่มีพัสดุค้างรับ</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($waiting as $p): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card parcel-card status-waiting h-100 shadow-sm">
                                <div class="card-body d-flex align-items-start">
                                    <div class="icon-box bg-warning text-dark me-3">
                                        <i class="bi bi-box-seam"></i>
                                    </div>
                                    <div class="w-100">
                                        <div class="d-flex justify-content-between">
                                            <h5 class="card-title mb-1"><?php echo htmlspecialchars($p['courier_company']); ?></h5>
                                            <span class="badge bg-warning text-dark">รอรับ</span>
                                        </div>
                                        <p class="text-muted small mb-2">เลขพัสดุ: <?php echo htmlspecialchars($p['tracking_number']); ?></p>
                                        <hr class="my-2">
                                        <p class="mb-1"><small>ผู้ส่ง: <?php echo htmlspecialchars($p['sender_name']); ?></small></p>
                                        <p class="mb-0 text-muted small">
                                            <i class="bi bi-clock"></i> มาถึงเมื่อ: <?php echo date('d/m/Y H:i', strtotime($p['received_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-pane fade" id="pills-hist">
            <?php if (empty($history)): ?>
                <div class="text-center py-5 text-muted">
                    <p>ยังไม่มีประวัติการรับพัสดุ</p>
                </div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($history as $p): ?>
                        <div class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1 text-success">
                                    <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($p['courier_company']); ?>
                                </h6>
                                <small class="text-muted">รับแล้วเมื่อ: <?php echo date('d/m/Y H:i', strtotime($p['picked_up_at'])); ?></small>
                            </div>
                            <p class="mb-1 small">เลขพัสดุ: <?php echo htmlspecialchars($p['tracking_number']); ?></p>
                            <small class="text-muted">ผู้ส่ง: <?php echo htmlspecialchars($p['sender_name']); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>