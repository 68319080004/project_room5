<?php
// ============================================
// ไฟล์: admin/users.php
// คำอธิบาย: จัดการผู้ใช้งานระบบ (เฉพาะ Owner)
// ============================================

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../models/User.php';

requireRole('owner'); // เฉพาะ Owner เท่านั้น

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$message = '';
$messageType = '';

// เพิ่มผู้ใช้ใหม่
if (isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    
    if (strlen($password) < 6) {
        $message = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
        $messageType = 'danger';
    } else {
        $user_id = $user->create($username, $password, $full_name, $phone, $role);
        
        if ($user_id) {
            // อัปเดท email
            $user->update($user_id, ['email' => $email]);
            
            $message = "เพิ่มผู้ใช้สำเร็จ! Username: <strong>{$username}</strong>";
            $messageType = 'success';
        } else {
            $message = 'Username นี้ถูกใช้งานแล้ว';
            $messageType = 'danger';
        }
    }
}

// แก้ไขผู้ใช้
if (isset($_POST['edit_user'])) {
    $data = [
        'full_name' => $_POST['full_name'],
        'phone' => $_POST['phone'],
        'email' => $_POST['email'],
        'role' => $_POST['role'],
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    if ($user->update($_POST['user_id'], $data)) {
        $message = 'แก้ไขข้อมูลสำเร็จ!';
        $messageType = 'success';
    } else {
        $message = 'เกิดข้อผิดพลาด';
        $messageType = 'danger';
    }
}

// เปลี่ยนรหัสผ่าน
if (isset($_POST['change_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (strlen($new_password) < 6) {
        $message = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
        $messageType = 'danger';
    } elseif ($new_password !== $confirm_password) {
        $message = 'รหัสผ่านไม่ตรงกัน';
        $messageType = 'danger';
    } else {
        if ($user->changePassword($_POST['user_id'], $new_password)) {
            $message = 'เปลี่ยนรหัสผ่านสำเร็จ!';
            $messageType = 'success';
        } else {
            $message = 'เกิดข้อผิดพลาด';
            $messageType = 'danger';
        }
    }
}

// ลบผู้ใช้ (แค่ปิดการใช้งาน)
if (isset($_GET['deactivate'])) {
    $user->update($_GET['deactivate'], ['is_active' => 0]);
    $message = 'ปิดการใช้งานผู้ใช้สำเร็จ';
    $messageType = 'warning';
}

// เปิดการใช้งาน
if (isset($_GET['activate'])) {
    $user->update($_GET['activate'], ['is_active' => 1]);
    $message = 'เปิดการใช้งานผู้ใช้สำเร็จ';
    $messageType = 'success';
}

// ดึงรายการผู้ใช้ทั้งหมด
$users = $user->getAll();

// นับจำนวนตาม Role
$countByRole = [
    'owner' => 0,
    'admin' => 0,
    'member' => 0,
    'inactive' => 0
];

foreach ($users as $u) {
    if ($u['is_active']) {
        $countByRole[$u['role']]++;
    } else {
        $countByRole['inactive']++;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้งาน - ระบบจัดการหอพัก</title>
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
                        <i class="bi bi-person-gear"></i> จัดการผู้ใช้งานระบบ
                    </h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="bi bi-person-plus"></i> เพิ่มผู้ใช้ใหม่
                    </button>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- สถิติ -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-danger">
                            <div class="card-body">
                                <h6 class="card-title mb-0">Owner</h6>
                                <h2 class="mb-0"><?php echo $countByRole['owner']; ?></h2>
                                <small>เจ้าของหอพัก</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <h6 class="card-title mb-0">Admin</h6>
                                <h2 class="mb-0"><?php echo $countByRole['admin']; ?></h2>
                                <small>ผู้ดูแลระบบ</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <h6 class="card-title mb-0">Member</h6>
                                <h2 class="mb-0"><?php echo $countByRole['member']; ?></h2>
                                <small>ผู้เช่า</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-secondary">
                            <div class="card-body">
                                <h6 class="card-title mb-0">ปิดใช้งาน</h6>
                                <h2 class="mb-0"><?php echo $countByRole['inactive']; ?></h2>
                                <small>Inactive</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ตารางผู้ใช้ -->
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="bi bi-people"></i> รายชื่อผู้ใช้ทั้งหมด</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Username</th>
                                        <th>ชื่อ-นามสกุล</th>
                                        <th>เบอร์โทร</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>สถานะ</th>
                                        <th>สร้างเมื่อ</th>
                                        <th width="200">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $u): ?>
                                        <tr class="<?php echo !$u['is_active'] ? 'table-secondary' : ''; ?>">
                                            <td>
                                                <strong><?php echo $u['username']; ?></strong>
                                                <?php if ($u['user_id'] == $_SESSION['user_id']): ?>
                                                    <span class="badge bg-info">คุณ</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $u['full_name']; ?></td>
                                            <td><?php echo $u['phone'] ?: '-'; ?></td>
                                            <td><?php echo $u['email'] ?: '-'; ?></td>
                                            <td>
                                                <?php
                                                $roleColors = [
                                                    'owner' => 'danger',
                                                    'admin' => 'primary',
                                                    'member' => 'success'
                                                ];
                                                $roleNames = [
                                                    'owner' => 'Owner',
                                                    'admin' => 'Admin',
                                                    'member' => 'Member'
                                                ];
                                                ?>
                                                <span class="badge bg-<?php echo $roleColors[$u['role']]; ?>">
                                                    <?php echo $roleNames[$u['role']]; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($u['is_active']): ?>
                                                    <span class="badge bg-success">ใช้งาน</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">ปิดใช้งาน</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($u['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info" 
                                                        onclick='editUser(<?php echo json_encode($u, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-warning" 
                                                        onclick='changePassword(<?php echo $u["user_id"]; ?>, "<?php echo $u["username"]; ?>")'>
                                                    <i class="bi bi-key"></i>
                                                </button>
                                                <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
                                                    <?php if ($u['is_active']): ?>
                                                        <a href="?deactivate=<?php echo $u['user_id']; ?>" 
                                                           class="btn btn-sm btn-danger"
                                                           onclick="return confirm('ยืนยันการปิดการใช้งาน?')">
                                                            <i class="bi bi-x-circle"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="?activate=<?php echo $u['user_id']; ?>" 
                                                           class="btn btn-sm btn-success"
                                                           onclick="return confirm('ยืนยันการเปิดการใช้งาน?')">
                                                            <i class="bi bi-check-circle"></i>
                                                        </a>
                                                    <?php endif; ?>
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

    <!-- Modal เพิ่มผู้ใช้ -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="bi bi-person-plus"></i> เพิ่มผู้ใช้ใหม่</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password" required>
                            <small class="text-muted">อย่างน้อย 6 ตัวอักษร</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">เบอร์โทร <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select" name="role" required>
                                <option value="admin">Admin (ผู้ดูแลระบบ)</option>
                                <option value="member">Member (ผู้เช่า)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="add_user" class="btn btn-primary">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal แก้ไขผู้ใช้ -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title"><i class="bi bi-pencil"></i> แก้ไขข้อมูลผู้ใช้</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" id="edit_username" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="full_name" id="edit_full_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">เบอร์โทร <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" name="phone" id="edit_phone" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select" name="role" id="edit_role" required>
                                <option value="owner">Owner (เจ้าของหอพัก)</option>
                                <option value="admin">Admin (ผู้ดูแลระบบ)</option>
                                <option value="member">Member (ผู้เช่า)</option>
                            </select>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" value="1">
                            <label class="form-check-label" for="edit_is_active">
                                เปิดการใช้งาน
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="edit_user" class="btn btn-primary">บันทึกการแก้ไข</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal เปลี่ยนรหัสผ่าน -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="user_id" id="pwd_user_id">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title"><i class="bi bi-key"></i> เปลี่ยนรหัสผ่าน</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <strong>Username:</strong> <span id="pwd_username"></span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">รหัสผ่านใหม่ <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="new_password" required>
                            <small class="text-muted">อย่างน้อย 6 ตัวอักษร</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ยืนยันรหัสผ่าน <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="change_password" class="btn btn-warning">เปลี่ยนรหัสผ่าน</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editUser(user) {
            document.getElementById('edit_user_id').value = user.user_id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_full_name').value = user.full_name;
            document.getElementById('edit_phone').value = user.phone || '';
            document.getElementById('edit_email').value = user.email || '';
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_is_active').checked = user.is_active == 1;
            
            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        }

        function changePassword(userId, username) {
            document.getElementById('pwd_user_id').value = userId;
            document.getElementById('pwd_username').textContent = username;
            
            new bootstrap.Modal(document.getElementById('changePasswordModal')).show();
        }
    </script>
</body>
</html>