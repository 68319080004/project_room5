<?php
// admin/parcel/create.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../models/Parcel.php';

requireRole(['admin', 'staff', 'owner']);

$db = new Database();
$pdo = $db->getConnection();
$parcelModel = new Parcel($pdo);

// ดึงรายการบริษัทขนส่งและประเภทพัสดุ
$couriers = $parcelModel->getCourierCompanies();
$types = $parcelModel->getParcelTypes();

// ดึงรายการห้องที่มีคนเช่าอยู่ (เพื่อเอามาใส่ Dropdown)
$stmt = $pdo->query("SELECT r.room_id, r.room_number, t.full_name 
                     FROM rooms r 
                     JOIN tenants t ON r.room_id = t.room_id 
                     WHERE r.room_status = 'occupied' 
                     ORDER BY r.room_number ASC");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// บันทึกข้อมูล
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. หา tenant_id จาก room_id ที่เลือก
    $roomId = $_POST['room_id'];
    $tenantStmt = $pdo->prepare("SELECT tenant_id FROM tenants WHERE room_id = ? LIMIT 1");
    $tenantStmt->execute([$roomId]);
    $tenant = $tenantStmt->fetch();

    if ($tenant) {
        $data = [
            'room_id' => $roomId,
            'tenant_id' => $tenant['tenant_id'],
            'tracking_number' => $_POST['tracking_number'],
            'courier_company' => $_POST['courier_company'],
            'sender_name' => $_POST['sender_name'],
            'parcel_type' => $_POST['parcel_type'],
            'notes' => $_POST['notes'],
            'received_by_staff_id' => $_SESSION['user_id'],
            'parcel_status' => 'waiting'
        ];

        if ($parcelModel->create($data)) {
            echo "<script>alert('บันทึกพัสดุเรียบร้อย'); window.location='index.php';</script>";
        } else {
            $error = "เกิดข้อผิดพลาดในการบันทึก";
        }
    } else {
        $error = "ไม่พบผู้เช่าในห้องนี้ (ห้องอาจจะว่าง)";
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>รับพัสดุใหม่</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">บันทึกพัสดุเข้าใหม่</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>

                        <form method="post">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">ห้องเลขที่ <span class="text-danger">*</span></label>
                                    <select name="room_id" class="form-select" required>
                                        <option value="">-- เลือกห้อง --</option>
                                        <?php foreach ($rooms as $room): ?>
                                            <option value="<?= $room['room_id'] ?>">
                                                ห้อง <?= $room['room_number'] ?> (<?= $room['full_name'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">เลขพัสดุ (Tracking No.)</label>
                                    <input type="text" name="tracking_number" class="form-control"
                                        placeholder="เช่น KERRY123..." required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">บริษัทขนส่ง</label>
                                    <select name="courier_company" class="form-select">
                                        <?php foreach ($couriers as $val => $label): ?>
                                            <option value="<?= $val ?>"><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">ชื่อผู้ส่ง (ถ้ามี)</label>
                                    <input type="text" name="sender_name" class="form-control"
                                        placeholder="เช่น Shopee, Lazada, ชื่อคน">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">ประเภทกล่อง</label>
                                    <select name="parcel_type" class="form-select">
                                        <?php foreach ($types as $val => $label): ?>
                                            <option value="<?= $val ?>"><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">หมายเหตุ</label>
                                    <input type="text" name="notes" class="form-control"
                                        placeholder="เช่น กล่องบุบ, ฝากไว้ที่ป้อมยาม">
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-secondary">ยกเลิก</a>
                                <button type="submit" class="btn btn-success px-4">บันทึกข้อมูล</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>