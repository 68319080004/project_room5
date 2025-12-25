<?php
// ============================================
// ไฟล์: admin/rooms.php
// คำอธิบาย: จัดการห้องเช่า (เพิ่ม/ลบ/แก้ไข)
// ============================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Room.php';
require_once __DIR__ . '/../models/Building.php';

requireRole(['admin', 'owner']);

$database = new Database();
$db = $database->getConnection();
$room = new Room($db);
$building = new Building($db);

$message = '';
$messageType = '';

// เพิ่มห้องใหม่
if (isset($_POST['add_room'])) {
    $data = [
        'room_number' => $_POST['room_number'],
        'room_type' => $_POST['room_type'],
        'monthly_rent' => $_POST['monthly_rent'],
        'room_status' => 'available',
        'floor' => $_POST['floor'],
        'description' => $_POST['description'] ?? '',
        'building_id' => $_POST['building_id']
    ];

    if ($room->create($data)) {
        $message = 'เพิ่มห้องสำเร็จ!';
        $messageType = 'success';
    } else {
        $message = 'เกิดข้อผิดพลาด หรือเลขห้องซ้ำ';
        $messageType = 'danger';
    }
}

// แก้ไขห้อง
if (isset($_POST['edit_room'])) {
    $data = [
        'room_number' => $_POST['room_number'],
        'room_type' => $_POST['room_type'],
        'monthly_rent' => $_POST['monthly_rent'],
        'room_status' => $_POST['room_status'],
        'floor' => $_POST['floor'],
        'description' => $_POST['description'] ?? '',
        'building_id' => $_POST['building_id']
    ];

    if ($room->update($_POST['room_id'], $data)) {
        $message = 'แก้ไขข้อมูลสำเร็จ!';
        $messageType = 'success';
    } else {
        $message = 'เกิดข้อผิดพลาด';
        $messageType = 'danger';
    }
}

// ลบห้อง
if (isset($_GET['delete']) && $_SESSION['role'] == 'owner') {
    if ($room->delete($_GET['delete'])) {
        $message = 'ลบห้องสำเร็จ!';
        $messageType = 'success';
    } else {
        $message = 'ไม่สามารถลบห้องที่มีผู้เช่าได้';
        $messageType = 'danger';
    }
}

// ดึงรายการห้องทั้งหมด
$rooms = $room->getAll();
$roomStats = $room->countByStatus();
$buildings = $building->getAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการห้องเช่า - ระบบจัดการหอพัก</title>
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
                <h1 class="h2"><i class="bi bi-door-open"></i> จัดการห้องเช่า</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                    <i class="bi bi-plus-circle"></i> เพิ่มห้องใหม่
                </button>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- สถิติห้อง -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5>ห้องทั้งหมด</h5>
                            <h2><?php echo count($rooms); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5>เช่าแล้ว</h5>
                            <h2><?php echo $roomStats['occupied'] ?? 0; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h5>ว่าง</h5>
                            <h2><?php echo $roomStats['available'] ?? 0; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-danger">
                        <div class="card-body">
                            <h5>ซ่อมแซม</h5>
                            <h2><?php echo $roomStats['maintenance'] ?? 0; ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ตารางห้อง -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ห้อง</th>
                                    <th>ประเภท</th>
                                    <th>ชั้น</th>
                                    <th>ค่าเช่า/เดือน</th>
                                    <th>สถานะ</th>
                                    <th>อาคาร</th>
                                    <th>ผู้เช่า</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rooms as $r): ?>
                                    <tr>
                                        <td><strong><?php echo $r['room_number']; ?></strong></td>
                                        <td><?php echo $r['room_type']; ?></td>
                                        <td>ชั้น <?php echo $r['floor']; ?></td>
                                        <td>฿<?php echo formatMoney($r['monthly_rent']); ?></td>
                                        <td>
                                            <?php if ($r['room_status'] == 'occupied'): ?>
                                                <span class="badge bg-success">เช่าแล้ว</span>
                                            <?php elseif ($r['room_status'] == 'available'): ?>
                                                <span class="badge bg-warning">ว่าง</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">ซ่อมแซม</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $r['building_name'] ?? '-'; ?></td>
                                        <td><?php echo $r['tenant_name'] ?: '-'; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" 
                                                    onclick="editRoom(<?php echo htmlspecialchars(json_encode($r)); ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php if ($_SESSION['role'] == 'owner' && $r['room_status'] == 'available'): ?>
                                                <a href="?delete=<?php echo $r['room_id']; ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('ยืนยันการลบห้อง <?php echo $r['room_number']; ?>?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal เพิ่มห้อง -->
<div class="modal fade" id="addRoomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> เพิ่มห้องใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">เลขห้อง *</label>
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
                        <label class="form-label">เลือกอาคาร <span class="text-danger">*</span></label>
                        <select class="form-select" name="building_id" required>
                            <option value="">-- เลือกอาคาร --</option>
                            <?php foreach ($buildings as $b): ?>
                                <option value="<?php echo $b['building_id']; ?>">
                                    <?php echo $b['building_name']; ?> (<?php echo $b['building_type']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
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
                        <label class="form-label">เลือกอาคาร <span class="text-danger">*</span></label>
                        <select class="form-select" name="building_id" id="edit_building_id" required>
                            <option value="">-- เลือกอาคาร --</option>
                            <?php foreach ($buildings as $b): ?>
                                <option value="<?php echo $b['building_id']; ?>">
                                    <?php echo $b['building_name']; ?> (<?php echo $b['building_type']; ?>)
                                </option>
                            <?php endforeach; ?>
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
    document.getElementById('edit_building_id').value = room.building_id;
    document.getElementById('edit_description').value = room.description || '';

    new bootstrap.Modal(document.getElementById('editRoomModal')).show();
}
</script>
</body>
</html>
