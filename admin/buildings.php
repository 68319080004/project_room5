<?php
// ============================================
// ไฟล์: admin/buildings.php
// คำอธิบาย: จัดการอาคาร/ทรัพย์สิน
// ============================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Building.php';

requireRole(['admin', 'owner']);

$database = new Database();
$db = $database->getConnection();
$building = new Building($db);

$message = '';
$messageType = '';

// เพิ่มอาคารใหม่
if (isset($_POST['add_building'])) {
    $data = [
        'building_name' => $_POST['building_name'],
        'building_type' => $_POST['building_type'],
        'water_rate_per_unit' => $_POST['water_rate_per_unit'],
        'water_minimum_unit' => $_POST['water_minimum_unit'],
        'water_minimum_charge' => $_POST['water_minimum_charge'],
        'electric_rate_per_unit' => $_POST['electric_rate_per_unit'],
        'garbage_fee' => $_POST['garbage_fee'],
        'address' => $_POST['address'] ?? '',
        'description' => $_POST['description'] ?? '',
        'created_by' => $_SESSION['user_id']
    ];
    
    if ($building->create($data)) {
        $message = 'เพิ่มอาคารสำเร็จ!';
        $messageType = 'success';
    } else {
        $message = 'เกิดข้อผิดพลาด';
        $messageType = 'danger';
    }
}

// แก้ไขอาคาร
if (isset($_POST['edit_building'])) {
    $data = [
        'building_name' => $_POST['building_name'],
        'building_type' => $_POST['building_type'],
        'water_rate_per_unit' => $_POST['water_rate_per_unit'],
        'water_minimum_unit' => $_POST['water_minimum_unit'],
        'water_minimum_charge' => $_POST['water_minimum_charge'],
        'electric_rate_per_unit' => $_POST['electric_rate_per_unit'],
        'garbage_fee' => $_POST['garbage_fee'],
        'address' => $_POST['address'] ?? '',
        'description' => $_POST['description'] ?? ''
    ];
    
    if ($building->update($_POST['building_id'], $data)) {
        $message = 'แก้ไขข้อมูลสำเร็จ!';
        $messageType = 'success';
    } else {
        $message = 'เกิดข้อผิดพลาด';
        $messageType = 'danger';
    }
}

// ปิดการใช้งาน/เปิดการใช้งาน
if (isset($_GET['action']) && isset($_GET['id'])) {
    if ($_GET['action'] == 'deactivate') {
        $building->deactivate($_GET['id']);
        $message = 'ปิดการใช้งานอาคารแล้ว';
        $messageType = 'warning';
    } elseif ($_GET['action'] == 'activate') {
        $building->activate($_GET['id']);
        $message = 'เปิดการใช้งานอาคารแล้ว';
        $messageType = 'success';
    }
}

// ดึงรายการอาคาร
$buildings = $building->getAll(false); // false = รวมทั้งที่ปิดการใช้งาน
$buildingTypes = $building->getBuildingTypes();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการอาคาร - ระบบจัดการหอพัก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- ⚠️ อย่าลืมเพิ่มเมนูใน admin/includes/sidebar.php:
            <li class="nav-item">
                <a class="nav-link" href="buildings.php">
                    <i class="bi bi-buildings"></i> จัดการอาคาร
                </a>
            </li>
            -->

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-buildings"></i> จัดการอาคาร/ทรัพย์สิน
                    </h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBuildingModal">
                        <i class="bi bi-plus-circle"></i> เพิ่มอาคารใหม่
                    </button>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- แสดงรายการอาคาร -->
                <div class="row">
                    <?php foreach ($buildings as $b): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 <?php echo !$b['is_active'] ? 'border-danger' : ''; ?>">
                                <div class="card-header <?php echo !$b['is_active'] ? 'bg-danger text-white' : 'bg-primary text-white'; ?>">
                                    <h5 class="mb-0">
                                        <i class="bi bi-building"></i> <?php echo $b['building_name']; ?>
                                    </h5>
                                    <small><?php echo $b['building_type']; ?></small>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-3 text-muted">
                                        <i class="bi bi-door-closed"></i> 
                                        <?php echo $b['total_rooms']; ?> ห้อง 
                                        (เช่าแล้ว <?php echo $b['occupied_rooms']; ?>)
                                    </h6>
                                    
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td><i class="bi bi-droplet-fill text-info"></i> ค่าน้ำ:</td>
                                            <td class="text-end">
                                                <strong>฿<?php echo number_format($b['water_rate_per_unit'], 2); ?>/ยูนิต</strong>
                                            </td>
                                        </tr>
                                        <?php if ($b['water_minimum_charge'] > 0): ?>
                                        <tr>
                                            <td class="small text-muted">ขั้นต่ำ:</td>
                                            <td class="text-end small">
                                                <?php echo $b['water_minimum_unit']; ?> ยูนิต = 
                                                ฿<?php echo number_format($b['water_minimum_charge'], 2); ?>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                        <tr>
                                            <td><i class="bi bi-lightning-fill text-warning"></i> ค่าไฟ:</td>
                                            <td class="text-end">
                                                <strong>฿<?php echo number_format($b['electric_rate_per_unit'], 2); ?>/ยูนิต</strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><i class="bi bi-trash"></i> ค่าขยะ:</td>
                                            <td class="text-end">
                                                <strong>฿<?php echo number_format($b['garbage_fee'], 2); ?>/เดือน</strong>
                                            </td>
                                        </tr>
                                    </table>
                                    
                                    <?php if ($b['description']): ?>
                                        <p class="card-text small text-muted mt-2">
                                            <?php echo $b['description']; ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <button class="btn btn-sm btn-info" 
                                            onclick='editBuilding(<?php echo json_encode($b, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                        <i class="bi bi-pencil"></i> แก้ไข
                                    </button>
                                    
                                    <?php if ($b['is_active']): ?>
                                        <a href="?action=deactivate&id=<?php echo $b['building_id']; ?>" 
                                           class="btn btn-sm btn-warning"
                                           onclick="return confirm('ยืนยันการปิดการใช้งาน?')">
                                            <i class="bi bi-x-circle"></i> ปิดใช้งาน
                                        </a>
                                    <?php else: ?>
                                        <a href="?action=activate&id=<?php echo $b['building_id']; ?>" 
                                           class="btn btn-sm btn-success"
                                           onclick="return confirm('ยืนยันการเปิดการใช้งาน?')">
                                            <i class="bi bi-check-circle"></i> เปิดใช้งาน
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (count($buildings) == 0): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> ยังไม่มีอาคารในระบบ กรุณาเพิ่มอาคารใหม่
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Modal เพิ่มอาคาร -->
    <div class="modal fade" id="addBuildingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-plus-circle"></i> เพิ่มอาคารใหม่
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">ชื่ออาคาร <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="building_name" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">ประเภท <span class="text-danger">*</span></label>
                                <select class="form-select" name="building_type" required>
                                    <?php foreach ($buildingTypes as $type): ?>
                                        <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <h6 class="text-primary mt-3 mb-3">
                            <i class="bi bi-calculator"></i> อัตราค่าบริการ
                        </h6>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-droplet-fill text-info"></i> ค่าน้ำ/ยูนิต (บาท)
                                </label>
                                <input type="number" step="0.01" class="form-control" 
                                       name="water_rate_per_unit" value="18.00" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">ยูนิตขั้นต่ำ</label>
                                <input type="number" step="0.01" class="form-control" 
                                       name="water_minimum_unit" value="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">ค่าขั้นต่ำ (บาท)</label>
                                <input type="number" step="0.01" class="form-control" 
                                       name="water_minimum_charge" value="0">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-lightning-fill text-warning"></i> ค่าไฟ/ยูนิต (บาท)
                                </label>
                                <input type="number" step="0.01" class="form-control" 
                                       name="electric_rate_per_unit" value="5.00" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-trash"></i> ค่าขยะ/เดือน (บาท)
                                </label>
                                <input type="number" step="0.01" class="form-control" 
                                       name="garbage_fee" value="50.00" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">ที่อยู่</label>
                            <textarea class="form-control" name="address" rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">คำอธิบาย</label>
                            <textarea class="form-control" name="description" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="add_building" class="btn btn-primary">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal แก้ไขอาคาร -->
    <div class="modal fade" id="editBuildingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="building_id" id="edit_building_id">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-pencil"></i> แก้ไขข้อมูลอาคาร
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">ชื่ออาคาร <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="building_name" 
                                       id="edit_building_name" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">ประเภท <span class="text-danger">*</span></label>
                                <select class="form-select" name="building_type" id="edit_building_type" required>
                                    <?php foreach ($buildingTypes as $type): ?>
                                        <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <h6 class="text-primary mt-3 mb-3">
                            <i class="bi bi-calculator"></i> อัตราค่าบริการ
                        </h6>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">ค่าน้ำ/ยูนิต (บาท)</label>
                                <input type="number" step="0.01" class="form-control" 
                                       name="water_rate_per_unit" id="edit_water_rate" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">ยูนิตขั้นต่ำ</label>
                                <input type="number" step="0.01" class="form-control" 
                                       name="water_minimum_unit" id="edit_water_min_unit">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">ค่าขั้นต่ำ (บาท)</label>
                                <input type="number" step="0.01" class="form-control" 
                                       name="water_minimum_charge" id="edit_water_min_charge">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ค่าไฟ/ยูนิต (บาท)</label>
                                <input type="number" step="0.01" class="form-control" 
                                       name="electric_rate_per_unit" id="edit_electric_rate" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ค่าขยะ/เดือน (บาท)</label>
                                <input type="number" step="0.01" class="form-control" 
                                       name="garbage_fee" id="edit_garbage_fee" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">ที่อยู่</label>
                            <textarea class="form-control" name="address" id="edit_address" rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">คำอธิบาย</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="edit_building" class="btn btn-primary">บันทึกการแก้ไข</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editBuilding(building) {
            document.getElementById('edit_building_id').value = building.building_id;
            document.getElementById('edit_building_name').value = building.building_name;
            document.getElementById('edit_building_type').value = building.building_type;
            document.getElementById('edit_water_rate').value = building.water_rate_per_unit;
            document.getElementById('edit_water_min_unit').value = building.water_minimum_unit;
            document.getElementById('edit_water_min_charge').value = building.water_minimum_charge;
            document.getElementById('edit_electric_rate').value = building.electric_rate_per_unit;
            document.getElementById('edit_garbage_fee').value = building.garbage_fee;
            document.getElementById('edit_address').value = building.address || '';
            document.getElementById('edit_description').value = building.description || '';
            
            new bootstrap.Modal(document.getElementById('editBuildingModal')).show();
        }
    </script>
</body>
</html>