<?php
// ============================================
// ไฟล์: admin/tenants.php
// คำอธิบาย: จัดการผู้เช่า (ฉบับสมบูรณ์)
// ============================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Tenant.php';
require_once __DIR__ . '/../models/Room.php';
require_once __DIR__ . '/../models/User.php';

requireRole(['admin', 'owner']);

$database = new Database();
$db = $database->getConnection();

$tenant = new Tenant($db);
$room = new Room($db);
$user = new User($db);

$message = '';
$messageType = '';

// เพิ่มผู้เช่าใหม่
if (isset($_POST['add_tenant'])) {
    // สร้างบัญชี User ก่อน
    $username = 'member_' . uniqid();
    $password = 'temp' . rand(1000, 9999); // รหัสผ่านชั่วคราว
    
    $user_id = $user->create($username, $password, $_POST['full_name'], $_POST['phone'], 'member');
    
    if ($user_id) {
        $data = [
            'user_id' => $user_id,
            'room_id' => $_POST['room_id'],
            'full_name' => $_POST['full_name'],
            'phone' => $_POST['phone'],
            'id_card' => $_POST['id_card'] ?? null,
            'line_id' => $_POST['line_id'] ?? null,
            'facebook' => $_POST['facebook'] ?? null,
            'emergency_contact' => $_POST['emergency_contact'] ?? null,
            'emergency_phone' => $_POST['emergency_phone'] ?? null,
            'move_in_date' => $_POST['move_in_date'],
            'deposit_amount' => $_POST['deposit_amount'] ?? 0,
            'discount_amount' => $_POST['discount_amount'] ?? 0
        ];
        
        if ($tenant->create($data)) {
            $message = "เพิ่มผู้เช่าสำเร็จ!<br><strong>Username:</strong> {$username}<br><strong>Password:</strong> {$password}<br><span class='text-danger'>กรุณาบันทึกข้อมูลนี้แล้วส่งให้ผู้เช่า</span>";
            $messageType = 'success';
        } else {
            $message = 'เกิดข้อผิดพลาดในการเพิ่มผู้เช่า';
            $messageType = 'danger';
        }
    } else {
        $message = 'เกิดข้อผิดพลาดในการสร้างบัญชีผู้ใช้';
        $messageType = 'danger';
    }
}

// แก้ไขผู้เช่า
if (isset($_POST['edit_tenant'])) {
    $data = [
        'full_name' => $_POST['full_name'],
        'phone' => $_POST['phone'],
        'line_id' => $_POST['line_id'] ?? null,
        'facebook' => $_POST['facebook'] ?? null,
        'emergency_contact' => $_POST['emergency_contact'] ?? null,
        'emergency_phone' => $_POST['emergency_phone'] ?? null,
        'discount_amount' => $_POST['discount_amount'] ?? 0
    ];
    
    if ($tenant->update($_POST['tenant_id'], $data)) {
        $message = 'แก้ไขข้อมูลสำเร็จ!';
        $messageType = 'success';
    } else {
        $message = 'เกิดข้อผิดพลาด';
        $messageType = 'danger';
    }
}

// ย้ายผู้เช่าออก
if (isset($_GET['move_out'])) {
    if ($tenant->moveOut($_GET['move_out'], date('Y-m-d'))) {
        $message = 'บันทึกการย้ายออกสำเร็จ! ห้องจะเปลี่ยนสถานะเป็นว่างอัตโนมัติ';
        $messageType = 'success';
    } else {
        $message = 'เกิดข้อผิดพลาด';
        $messageType = 'danger';
    }
}

// ดึงรายการผู้เช่า
$tenants = $tenant->getAll(true);
$availableRooms = $room->getAll('available');

// นับจำนวนผู้เช่า
$totalTenants = count($tenants);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้เช่า - ระบบจัดการหอพัก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .tenant-card {
            transition: all 0.3s;
        }
        .tenant-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
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
                        <i class="bi bi-people-fill"></i> จัดการผู้เช่า
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTenantModal">
                            <i class="bi bi-person-plus-fill"></i> เพิ่มผู้เช่าใหม่
                        </button>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- สถิติ -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">ผู้เช่าทั้งหมด</h6>
                                        <h2 class="mb-0"><?php echo $totalTenants; ?></h2>
                                        <small>รายการ</small>
                                    </div>
                                    <i class="bi bi-people" style="font-size: 3rem; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">ห้องว่าง</h6>
                                        <h2 class="mb-0"><?php echo count($availableRooms); ?></h2>
                                        <small>ห้อง</small>
                                    </div>
                                    <i class="bi bi-door-open" style="font-size: 3rem; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">อัตราการเข้าพัก</h6>
                                        <h2 class="mb-0">
                                            <?php 
                                            $totalRooms = $totalTenants + count($availableRooms);
                                            echo $totalRooms > 0 ? round(($totalTenants / $totalRooms) * 100, 1) : 0; 
                                            ?>%
                                        </h2>
                                        <small>Occupancy Rate</small>
                                    </div>
                                    <i class="bi bi-percent" style="font-size: 3rem; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ตารางผู้เช่า -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-list"></i> รายชื่อผู้เช่าทั้งหมด</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="80">ห้อง</th>
                                        <th>ชื่อ-นามสกุล</th>
                                        <th>เบอร์โทร</th>
                                        <th>LINE ID</th>
                                        <th>Facebook</th>
                                        <th>วันที่เข้าพัก</th>
                                        <th>ส่วนลด</th>
                                        <th width="200">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($tenants) > 0): ?>
                                        <?php foreach ($tenants as $t): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-primary fs-6">
                                                        <?php echo $t['room_number']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong><?php echo $t['full_name']; ?></strong>
                                                    <?php if ($t['discount_amount'] > 0): ?>
                                                        <span class="badge bg-success ms-1">VIP</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <i class="bi bi-telephone"></i> <?php echo $t['phone']; ?>
                                                </td>
                                                <td>
                                                    <?php if ($t['line_id']): ?>
                                                        <i class="bi bi-line text-success"></i> <?php echo $t['line_id']; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($t['facebook']): ?>
                                                        <i class="bi bi-facebook text-primary"></i> <?php echo $t['facebook']; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo formatThaiDate($t['move_in_date']); ?></td>
                                                <td>
                                                    <?php if ($t['discount_amount'] > 0): ?>
                                                        <span class="text-success">-฿<?php echo formatMoney($t['discount_amount']); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-info" 
                                                            onclick='editTenant(<?php echo json_encode($t, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                                        <i class="bi bi-pencil-square"></i> แก้ไข
                                                    </button>
                                                    <a href="?move_out=<?php echo $t['tenant_id']; ?>" 
                                                       class="btn btn-sm btn-warning"
                                                       onclick="return confirm('ยืนยันการย้ายออกของ <?php echo $t['full_name']; ?>?\n\nห้อง <?php echo $t['room_number']; ?> จะเปลี่ยนสถานะเป็นว่าง')">
                                                        <i class="bi bi-box-arrow-right"></i> ย้ายออก
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-5">
                                                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                                <p class="mt-2">ยังไม่มีผู้เช่า</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal เพิ่มผู้เช่า -->
    <div class="modal fade" id="addTenantModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-person-plus-fill"></i> เพิ่มผู้เช่าใหม่
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> 
                            <strong>หมายเหตุ:</strong> ระบบจะสร้างบัญชีผู้ใช้ให้อัตโนมัติ และแสดง Username/Password หลังจากบันทึกสำเร็จ
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-person"></i> ชื่อ-นามสกุล <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" name="full_name" 
                                       placeholder="กรอกชื่อ-นามสกุลจริง" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-telephone"></i> เบอร์โทร <span class="text-danger">*</span>
                                </label>
                                <input type="tel" class="form-control" name="phone" 
                                       placeholder="08X-XXX-XXXX" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-door-open"></i> เลือกห้อง <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="room_id" required>
                                    <option value="">-- เลือกห้องว่าง --</option>
                                    <?php foreach ($availableRooms as $r): ?>
                                        <option value="<?php echo $r['room_id']; ?>">
                                            ห้อง <?php echo $r['room_number']; ?> 
                                            (<?php echo $r['room_type']; ?>) - 
                                            ฿<?php echo formatMoney($r['monthly_rent']); ?>/เดือน
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-calendar"></i> วันที่เข้าพัก <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" name="move_in_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-credit-card"></i> เลขบัตรประชาชน
                                </label>
                                <input type="text" class="form-control" name="id_card" 
                                       placeholder="X-XXXX-XXXXX-XX-X" maxlength="17">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-cash-stack"></i> เงินประกัน (บาท)
                                </label>
                                <input type="number" step="0.01" class="form-control" name="deposit_amount" 
                                       value="0" placeholder="0.00">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-line text-success"></i> LINE ID
                                </label>
                                <input type="text" class="form-control" name="line_id" 
                                       placeholder="@yourline">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-facebook text-primary"></i> Facebook
                                </label>
                                <input type="text" class="form-control" name="facebook" 
                                       placeholder="ชื่อ Facebook">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-person-fill"></i> ผู้ติดต่อฉุกเฉิน
                                </label>
                                <input type="text" class="form-control" name="emergency_contact" 
                                       placeholder="ชื่อผู้ติดต่อฉุกเฉิน">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-telephone-fill"></i> เบอร์ฉุกเฉิน
                                </label>
                                <input type="tel" class="form-control" name="emergency_phone" 
                                       placeholder="08X-XXX-XXXX">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> ยกเลิก
                        </button>
                        <button type="submit" name="add_tenant" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> บันทึกผู้เช่า
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal แก้ไขผู้เช่า -->
    <div class="modal fade" id="editTenantModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="tenant_id" id="edit_tenant_id">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-pencil-square"></i> แก้ไขข้อมูลผู้เช่า
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="full_name" id="edit_full_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">เบอร์โทร <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" name="phone" id="edit_phone" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">LINE ID</label>
                                <input type="text" class="form-control" name="line_id" id="edit_line_id">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Facebook</label>
                                <input type="text" class="form-control" name="facebook" id="edit_facebook">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-tag-fill text-success"></i> 
                                    ส่วนลดรายเดือน (บาท)
                                </label>
                                <input type="number" step="0.01" class="form-control" 
                                       name="discount_amount" id="edit_discount_amount" placeholder="0.00">
                                <small class="text-muted">ส่วนลดจะถูกหักจากยอดรวมในใบเสร็จทุกเดือน</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">ผู้ติดต่อฉุกเฉิน</label>
                                <input type="text" class="form-control" name="emergency_contact" id="edit_emergency_contact">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">เบอร์ฉุกเฉิน</label>
                                <input type="tel" class="form-control" name="emergency_phone" id="edit_emergency_phone">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> ยกเลิก
                        </button>
                        <button type="submit" name="edit_tenant" class="btn btn-primary">
                            <i class="bi bi-save"></i> บันทึกการแก้ไข
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editTenant(tenant) {
            document.getElementById('edit_tenant_id').value = tenant.tenant_id;
            document.getElementById('edit_full_name').value = tenant.full_name;
            document.getElementById('edit_phone').value = tenant.phone;
            document.getElementById('edit_line_id').value = tenant.line_id || '';
            document.getElementById('edit_facebook').value = tenant.facebook || '';
            document.getElementById('edit_discount_amount').value = tenant.discount_amount || 0;
            document.getElementById('edit_emergency_contact').value = tenant.emergency_contact || '';
            document.getElementById('edit_emergency_phone').value = tenant.emergency_phone || '';
            
            new bootstrap.Modal(document.getElementById('editTenantModal')).show();
        }
    </script>
</body>
</html>