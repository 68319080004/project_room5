<?php
// ============================================
// ไฟล์: register.php (แก้ไขแล้ว - แสดงอาคารครบ)
// คำอธิบาย: หน้าสมัครสมาชิก พร้อมเลือกห้อง + แสดงอาคาร
// ============================================

require_once 'config/database.php';
require_once 'config/session.php';
require_once 'models/User.php';
require_once 'models/Room.php';
require_once 'models/Tenant.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$room = new Room($db);
$tenant = new Tenant($db);

// ดึงห้องว่าง พร้อมข้อมูลอาคาร (รองรับทั้งมีและไม่มีอาคาร)
$query = "SELECT r.*, 
                 b.building_id,
                 b.building_name, 
                 b.building_type,
                 b.water_rate_per_unit,
                 b.electric_rate_per_unit,
                 b.garbage_fee
          FROM rooms r
          LEFT JOIN buildings b ON r.building_id = b.building_id
          WHERE r.room_status = 'available'
            AND (b.is_active = 1 OR b.building_id IS NULL)
          ORDER BY 
            CASE WHEN b.building_name IS NULL THEN 1 ELSE 0 END,
            b.building_name ASC, 
            r.room_number ASC";

$stmt = $db->prepare($query);
$stmt->execute();
$availableRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $room_id = $_POST['room_id'];
    $id_card = trim($_POST['id_card']);
    $line_id = trim($_POST['line_id']);
    $facebook = trim($_POST['facebook']);
    $emergency_contact = trim($_POST['emergency_contact']);
    $emergency_phone = trim($_POST['emergency_phone']);
    $move_in_date = $_POST['move_in_date'];
    $deposit_amount = $_POST['deposit_amount'];

    // Validation
    if (empty($username) || empty($password) || empty($full_name) || empty($phone) || empty($room_id)) {
        $error = 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน';
    } elseif (strlen($password) < 6) {
        $error = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
    } elseif ($password !== $confirm_password) {
        $error = 'รหัสผ่านไม่ตรงกัน';
    } else {
        $db->beginTransaction();
        
        try {
            // 1. สร้าง User
            $user_id = $user->create($username, $password, $full_name, $phone, 'member');
            
            if (!$user_id) {
                throw new Exception('ชื่อผู้ใช้นี้ถูกใช้งานแล้ว');
            }
            
            // 2. สร้างข้อมูล Tenant
            $tenantData = [
                'user_id' => $user_id,
                'room_id' => $room_id,
                'full_name' => $full_name,
                'phone' => $phone,
                'id_card' => $id_card,
                'line_id' => $line_id,
                'facebook' => $facebook,
                'emergency_contact' => $emergency_contact,
                'emergency_phone' => $emergency_phone,
                'move_in_date' => $move_in_date,
                'deposit_amount' => $deposit_amount,
                'discount_amount' => 0
            ];
            
            $tenant_id = $tenant->create($tenantData);
            
            if (!$tenant_id) {
                throw new Exception('ไม่สามารถสร้างข้อมูลผู้เช่าได้');
            }
            
            // 3. อัพเดทสถานะห้องเป็น occupied
            $roomData = $room->getById($room_id);
            $room->update($room_id, [
                'room_number' => $roomData['room_number'],
                'room_type' => $roomData['room_type'],
                'monthly_rent' => $roomData['monthly_rent'],
                'room_status' => 'occupied',
                'floor' => $roomData['floor'],
                'description' => $roomData['description'],
                'building_id' => $roomData['building_id']
            ]);
            
            $db->commit();
            
            $success = 'สมัครสมาชิกสำเร็จ! กำลังนำคุณไปยังหน้า Login...';
            header("refresh:3;url=login.php");
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - ระบบจัดการหอพัก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 50px 0;
        }
        .register-card {
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        .room-card {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        .room-card input[type="radio"] {
            display: none;
        }
        .room-card input[type="radio"]:checked + label {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            border: 3px solid #667eea !important;
        }
        .room-card input[type="radio"]:checked + label .text-primary,
        .room-card input[type="radio"]:checked + label .text-success,
        .room-card input[type="radio"]:checked + label .text-muted {
            color: white !important;
        }
        .room-card input[type="radio"]:checked + label .badge {
            background-color: rgba(255,255,255,0.3) !important;
            color: white !important;
        }
        .building-section {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card register-card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-person-plus-fill" style="font-size: 3rem; color: #667eea;"></i>
                            <h3 class="mt-3">สมัครสมาชิก</h3>
                            <p class="text-muted">สร้างบัญชีผู้ใช้ใหม่และเลือกห้องพัก</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="bi bi-exclamation-circle"></i> <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" id="registerForm">
                            <!-- ส่วนที่ 1: ข้อมูลบัญชี -->
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="bi bi-shield-lock"></i> ข้อมูลบัญชีผู้ใช้</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">ชื่อผู้ใช้ <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="username" 
                                                   placeholder="ใช้สำหรับเข้าสู่ระบบ" required>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">รหัสผ่าน <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" name="password" 
                                                   placeholder="อย่างน้อย 6 ตัวอักษร" required>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">ยืนยันรหัสผ่าน <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" name="confirm_password" 
                                                   placeholder="กรอกรหัสผ่านอีกครั้ง" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ส่วนที่ 2: ข้อมูลส่วนตัว -->
                            <div class="card mb-4">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0"><i class="bi bi-person-badge"></i> ข้อมูลส่วนตัว</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="full_name" 
                                                   placeholder="กรอกชื่อ-นามสกุลจริง" required>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                                            <input type="tel" class="form-control" name="phone" 
                                                   placeholder="08X-XXX-XXXX" required>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">เลขบัตรประชาชน</label>
                                            <input type="text" class="form-control" name="id_card" 
                                                   placeholder="X-XXXX-XXXXX-XX-X" maxlength="17">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">วันที่เข้าพัก <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" name="move_in_date" 
                                                   value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">LINE ID</label>
                                            <input type="text" class="form-control" name="line_id" 
                                                   placeholder="@yourline">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Facebook</label>
                                            <input type="text" class="form-control" name="facebook" 
                                                   placeholder="ชื่อ Facebook">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">เงินประกัน (บาท)</label>
                                            <input type="number" class="form-control" name="deposit_amount" 
                                                   value="0" placeholder="0">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ส่วนที่ 3: ข้อมูลติดต่อฉุกเฉิน -->
                            <div class="card mb-4">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> ข้อมูลติดต่อฉุกเฉิน</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">ชื่อผู้ติดต่อฉุกเฉิน</label>
                                            <input type="text" class="form-control" name="emergency_contact" 
                                                   placeholder="ชื่อผู้ติดต่อฉุกเฉิน">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">เบอร์โทรฉุกเฉิน</label>
                                            <input type="tel" class="form-control" name="emergency_phone" 
                                                   placeholder="08X-XXX-XXXX">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ส่วนที่ 4: เลือกห้อง -->
                            <div class="card mb-4">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">
                                        <i class="bi bi-door-open"></i> เลือกห้องพัก 
                                        <span class="text-danger">*</span>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (count($availableRooms) > 0): ?>
                                        <div class="alert alert-info mb-4">
                                            <i class="bi bi-info-circle"></i> 
                                            มีห้องว่าง <strong><?php echo count($availableRooms); ?></strong> ห้อง - กรุณาเลือกห้องที่ต้องการ
                                        </div>
                                        
                                        <?php 
                                        // จัดกลุ่มห้องตามอาคาร
                                        $roomsByBuilding = [];
                                        foreach ($availableRooms as $r) {
                                            // ถ้าไม่มีอาคาร ให้ใช้ key 'no_building'
                                            $buildingKey = $r['building_id'] ?? 'no_building';
                                            $roomsByBuilding[$buildingKey][] = $r;
                                        }
                                        
                                        // แสดงแต่ละอาคาร
                                        foreach ($roomsByBuilding as $buildingKey => $rooms): 
                                            $firstRoom = $rooms[0]; 
                                            $hasBuilding = ($buildingKey !== 'no_building' && $firstRoom['building_id']);
                                            ?>
                                            
                                            <div class="building-section mb-5">
                                                <!-- Header อาคาร -->
                                                <div class="card border-primary mb-3">
                                                    <div class="card-header <?php echo $hasBuilding ? 'bg-primary' : 'bg-secondary'; ?> text-white">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <h5 class="mb-0">
                                                                    <i class="bi bi-building-fill"></i> 
                                                                    <?php echo $hasBuilding ? $firstRoom['building_name'] : 'ห้องพักทั่วไป'; ?>
                                                                </h5>
                                                                <small>
                                                                    <?php if ($hasBuilding && $firstRoom['building_type']): ?>
                                                                        <span class="badge bg-light text-dark">
                                                                            <?php echo $firstRoom['building_type']; ?>
                                                                        </span>
                                                                    <?php endif; ?>
                                                                    <span class="badge bg-light text-dark">
                                                                        <?php echo count($rooms); ?> ห้องว่าง
                                                                    </span>
                                                                </small>
                                                            </div>
                                                            <?php if ($hasBuilding && $firstRoom['water_rate_per_unit']): ?>
                                                                <div class="text-end">
                                                                    <div class="small">
                                                                        <i class="bi bi-droplet-fill"></i> 
                                                                        น้ำ: ฿<?php echo number_format($firstRoom['water_rate_per_unit'], 2); ?>/ยูนิต
                                                                    </div>
                                                                    <div class="small">
                                                                        <i class="bi bi-lightning-fill"></i> 
                                                                        ไฟ: ฿<?php echo number_format($firstRoom['electric_rate_per_unit'], 2); ?>/ยูนิต
                                                                    </div>
                                                                    <div class="small">
                                                                        <i class="bi bi-trash"></i> 
                                                                        ขยะ: ฿<?php echo number_format($firstRoom['garbage_fee'], 2); ?>/เดือน
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- แสดงห้องในอาคารนี้ -->
                                                <div class="row">
                                                    <?php foreach ($rooms as $r): ?>
                                                        <div class="col-md-4 mb-3">
                                                            <div class="room-card">
                                                                <input type="radio" name="room_id" 
                                                                       id="room_<?php echo $r['room_id']; ?>" 
                                                                       value="<?php echo $r['room_id']; ?>" required>
                                                                <label for="room_<?php echo $r['room_id']; ?>" 
                                                                       class="card h-100 mb-0 border-2">
                                                                    <div class="card-body text-center">
                                                                        <div class="mb-3">
                                                                            <i class="bi bi-house-door-fill text-primary" 
                                                                               style="font-size: 3rem;"></i>
                                                                        </div>
                                                                        <h4 class="text-primary mb-2">
                                                                            ห้อง <?php echo $r['room_number']; ?>
                                                                        </h4>
                                                                        <div class="mb-2">
                                                                            <span class="badge bg-info">
                                                                                <?php echo $r['room_type']; ?>
                                                                            </span>
                                                                            <span class="badge bg-secondary">
                                                                                ชั้น <?php echo $r['floor']; ?>
                                                                            </span>
                                                                        </div>
                                                                        <h5 class="text-success mb-2">
                                                                            <i class="bi bi-cash"></i> 
                                                                            ฿<?php echo number_format($r['monthly_rent'], 0); ?>/เดือน
                                                                        </h5>
                                                                        <?php if ($r['description']): ?>
                                                                            <hr class="my-2">
                                                                            <small class="text-muted">
                                                                                <?php echo $r['description']; ?>
                                                                            </small>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        
                                    <?php else: ?>
                                        <div class="alert alert-danger">
                                            <h5 class="alert-heading">
                                                <i class="bi bi-x-circle"></i> ไม่มีห้องว่าง
                                            </h5>
                                            <hr>
                                            <p class="mb-0">
                                                ขออภัย ขณะนี้ไม่มีห้องว่างในระบบ กรุณาติดต่อเจ้าหน้าที่เพื่อสอบถามข้อมูลเพิ่มเติม
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if (count($availableRooms) > 0): ?>
                                <div class="d-grid gap-2 mb-3">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-check-circle"></i> สมัครสมาชิกและจองห้อง
                                    </button>
                                </div>
                            <?php endif; ?>

                            <div class="text-center">
                                <a href="login.php" class="text-decoration-none">
                                    <i class="bi bi-arrow-left"></i> กลับไปหน้า Login
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.querySelector('[name="password"]').value;
            const confirmPassword = document.querySelector('[name="confirm_password"]').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('รหัสผ่านไม่ตรงกัน กรุณาตรวจสอบอีกครั้ง');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
                return false;
            }
            
            const roomSelected = document.querySelector('input[name="room_id"]:checked');
            if (!roomSelected) {
                e.preventDefault();
                alert('กรุณาเลือกห้องพัก');
                return false;
            }
        });
    </script>
</body>
</html>