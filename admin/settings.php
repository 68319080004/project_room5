<?php

// ============================================
// ไฟล์: admin/settings.php
// คำอธิบาย: ตั้งค่าระบบ (เฉพาะ Owner)
// ============================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/SystemSettings.php';

requireRole(roles: 'owner');

$database = new Database();
$db = $database->getConnection();
$settings = new SystemSettings(db: $db);

$message = '';
$messageType = '';

// บันทึกการตั้งค่า
if (isset($_POST['save_settings'])) {
    $data = [
        'water_rate_per_unit' => $_POST['water_rate_per_unit'],
        'water_minimum_unit' => $_POST['water_minimum_unit'],
        'water_minimum_charge' => $_POST['water_minimum_charge'],
        'electric_rate_per_unit' => $_POST['electric_rate_per_unit'],
        'garbage_fee' => $_POST['garbage_fee'],
        'dormitory_name' => $_POST['dormitory_name'],
        'dormitory_address' => $_POST['dormitory_address'],
        'dormitory_phone' => $_POST['dormitory_phone']
    ];
    
    if ($settings->updateMultiple($data, $_SESSION['user_id'])) {
        $message = 'บันทึกการตั้งค่าสำเร็จ!';
        $messageType = 'success';
    } else {
        $message = 'เกิดข้อผิดพลาด';
        $messageType = 'danger';
    }
}

// ดึงการตั้งค่าปัจจุบัน
$allSettings = $settings->getAll();
$currentSettings = [];
foreach ($allSettings as $s) {
    $currentSettings[$s['setting_key']] = $s['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าระบบ - ระบบจัดการหอพัก</title>
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
                        <i class="bi bi-gear"></i> ตั้งค่าระบบ
                    </h1>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="row">
                        <!-- ข้อมูลหอพัก -->
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="bi bi-building"></i> ข้อมูลหอพัก</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">ชื่อหอพัก</label>
                                        <input type="text" class="form-control" name="dormitory_name" 
                                               value="<?php echo $currentSettings['dormitory_name']; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">ที่อยู่</label>
                                        <textarea class="form-control" name="dormitory_address" rows="3" required><?php echo $currentSettings['dormitory_address']; ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">เบอร์โทรศัพท์</label>
                                        <input type="text" class="form-control" name="dormitory_phone" 
                                               value="<?php echo $currentSettings['dormitory_phone']; ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ค่าน้ำ-ค่าไฟ -->
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0"><i class="bi bi-droplet-fill"></i> อัตราค่าน้ำ</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">ค่าน้ำต่อหน่วย (บาท)</label>
                                        <input type="number" step="0.01" class="form-control" name="water_rate_per_unit" 
                                               value="<?php echo $currentSettings['water_rate_per_unit']; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">จำนวนยูนิตขั้นต่ำ</label>
                                        <input type="number" step="0.01" class="form-control" name="water_minimum_unit" 
                                               value="<?php echo $currentSettings['water_minimum_unit']; ?>" required>
                                        <small class="text-muted">ถ้าใช้ไม่ถึงจำนวนนี้ จะคิดตามค่าขั้นต่ำ</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">ค่าน้ำขั้นต่ำ (บาท)</label>
                                        <input type="number" step="0.01" class="form-control" name="water_minimum_charge" 
                                               value="<?php echo $currentSettings['water_minimum_charge']; ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-4">
                                <div class="card-header bg-warning text-white">
                                    <h5 class="mb-0"><i class="bi bi-lightning-fill"></i> อัตราค่าไฟ</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">ค่าไฟต่อหน่วย (บาท)</label>
                                        <input type="number" step="0.01" class="form-control" name="electric_rate_per_unit" 
                                               value="<?php echo $currentSettings['electric_rate_per_unit']; ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-4">
                                <div class="card-header bg-secondary text-white">
                                    <h5 class="mb-0"><i class="bi bi-trash"></i> ค่าขยะ</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">ค่าขยะรายเดือน (บาท)</label>
                                        <input type="number" step="0.01" class="form-control" name="garbage_fee" 
                                               value="<?php echo $currentSettings['garbage_fee']; ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mb-4">
                        <button type="submit" name="save_settings" class="btn btn-success btn-lg">
                            <i class="bi bi-save"></i> บันทึกการตั้งค่า
                        </button>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>เลขห้อง *</label>
                            <input type="text" class="form-control" name="room_number" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ประเภทห้อง *</label>
                            <select class="form-select" name="room_type" required>
                                <option value="แอร์">แอร์</option>
                                <option value="พัดลม">พัดลม</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ชั้น *</label>
                            <input type="number" class="form-control" name="floor" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ค่าเช่า/เดือน (บาท) *</label>
                            <input type="number" step="0.01" class="form-control" name="monthly_rent" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">คำอธิบาย</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="add_room" class="btn btn-primary">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal แก้ไขห้อง -->
    <div class="modal fade" id="editRoomModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="room_id" id="edit_room_id">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil"></i> แก้ไขข้อมูลห้อง</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">เลขห้อง *</label>
                            <input type="text" class="form-control" name="room_number" id="edit_room_number" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ประเภทห้อง *</label>
                            <select class="form-select" name="room_type" id="edit_room_type" required>
                                <option value="แอร์">แอร์</option>
                                <option value="พัดลม">พัดลม</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ชั้น *</label>
                            <input type="number" class="form-control" name="floor" id="edit_floor" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ค่าเช่า/เดือน (บาท) *</label>
                            <input type="number" step="0.01" class="form-control" name="monthly_rent" id="edit_monthly_rent" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">สถานะห้อง *</label>
                            <select class="form-select" name="room_status" id="edit_room_status" required>
                                <option value="available">ว่าง</option>
                                <option value="occupied">เช่าแล้ว</option>
                                <option value="maintenance">ซ่อมแซม</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">คำอธิบาย</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="edit_room" class="btn btn-primary">บันทึกการแก้ไข</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editRoom(room) {
            document.getElementById('edit_room_id').value = room.room_id;
            document.getElementById('edit_room_number').value = room.room_number;
            document.getElementById('edit_room_type').value = room.room_type;
            document.getElementById('edit_floor').value = room.floor;
            document.getElementById('edit_monthly_rent').value = room.monthly_rent;
            document.getElementById('edit_room_status').value = room.room_status;
            document.getElementById('edit_description').value = room.description || '';
            
            new bootstrap.Modal(document.getElementById('editRoomModal')).show();
        }
    </script>
</body>
</html>