<?php
// admin/parcel/index.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../models/Parcel.php';

// ตรวจสอบสิทธิ์ Admin/Staff
requireRole(['admin', 'staff', 'owner']);

$db = new Database();
$pdo = $db->getConnection();
$parcelModel = new Parcel($pdo);

// ดึงรายการพัสดุทั้งหมด
$parcels = $parcelModel->getAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการพัสดุ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-box-seam-fill"></i> จัดการพัสดุ</h2>
        <div>
            <a href="../dashboard.php" class="btn btn-secondary me-2">กลับหน้าหลัก</a>
            <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> รับพัสดุใหม่</a>
        </div>
    </div>

    <?php $stats = $parcelModel->getStats(); ?>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>ทั้งหมด</h5>
                    <h3><?= $stats['total'] ?? 0 ?> ชิ้น</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5>รอรับ</h5>
                    <h3><?= $stats['waiting'] ?? 0 ?> ชิ้น</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5>รับแล้ว</h5>
                    <h3><?= $stats['picked_up'] ?? 0 ?> ชิ้น</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5>วันนี้</h5>
                    <h3><?= $stats['today'] ?? 0 ?> ชิ้น</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>วันที่รับ</th>
                        <th>ห้อง</th>
                        <th>ผู้เช่า</th>
                        <th>ขนส่ง / เลขพัสดุ</th>
                        <th>ผู้ส่ง</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($parcels as $p): ?>
                    <tr>
                        <td>
                            <?= date('d/m/Y', strtotime($p['received_at'])) ?><br>
                            <small class="text-muted"><?= date('H:i', strtotime($p['received_at'])) ?></small>
                        </td>
                        <td><span class="badge bg-secondary"><?= $p['room_number'] ?></span></td>
                        <td>
                            <?= htmlspecialchars($p['tenant_name']) ?><br>
                            <small class="text-muted"><?= $p['tenant_phone'] ?></small>
                        </td>
                        <td>
                            <strong><?= $p['courier_company'] ?></strong><br>
                            <span class="text-primary"><?= $p['tracking_number'] ?></span>
                        </td>
                        <td><?= htmlspecialchars($p['sender_name']) ?></td>
                        <td>
                            <?php if($p['parcel_status'] == 'waiting'): ?>
                                <span class="badge bg-warning text-dark">รอรับ</span>
                            <?php else: ?>
                                <span class="badge bg-success">รับแล้ว</span>
                                <div class="small text-muted mt-1">
                                    โดย: <?= date('d/m/y', strtotime($p['picked_up_at'])) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($p['parcel_status'] == 'waiting'): ?>
                                <form action="pickup.php" method="post" onsubmit="return confirm('ยืนยันว่าผู้เช่ามารับของแล้ว?');">
                                    <input type="hidden" name="id" value="<?= $p['parcel_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="bi bi-check-circle"></i> ส่งมอบของ
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-sm btn-secondary" disabled>เรียบร้อย</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>