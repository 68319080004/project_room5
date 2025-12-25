<?php
// ============================================
// ไฟล์: admin/includes/sidebar.php
// คำอธิบาย: Sidebar Menu สำหรับ Admin
// ============================================
?>
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="rooms.php">
                    <i class="bi bi-door-open"></i> จัดการห้องเช่า
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="buildings.php">
                    <i class="bi bi-buildings"></i> จัดการอาคาร
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="tenants.php">
                    <i class="bi bi-people"></i> จัดการผู้เช่า
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="meters.php">
                    <i class="bi bi-speedometer"></i> บันทึกมิเตอร์
                </a>
            </li>
            <li class="nav-item">
    <a class="nav-link" href="maintenance/index.php">
        <i class="bi bi-tools"></i> แจ้งซ่อม / ซ่อมบำรุง
    </a>
</li>
<li class="nav-item">
    <a class="nav-link" href="parcel/index.php">
        <i class="bi bi-box-seam me-2"></i> จัดการพัสดุ
    </a>
</li>
            <li class="nav-item">
                <a class="nav-link" href="invoices.php">
                    <i class="bi bi-receipt"></i> ใบเสร็จ/บิล
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="payments.php">
                    <i class="bi bi-credit-card"></i> การชำระเงิน
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="reports.php">
                    <i class="bi bi-bar-chart"></i> รายงาน
                </a>
                <li class="nav-item">
                <a class="nav-link" href="reports_advanced.php">
                    <i class="bi bi-bar-chart-line-fill me-2"></i> รายงานขั้นสูง
                </a>
</li>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="invoice_create_manual.php">
                    <i class="bi bi-pencil-square"></i> สร้างบิล Manual
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="contracts.php">
                    <i class="bi bi-file-earmark-text"></i> จัดการสัญญาเช่า
                </a>
            </li>
            <!-- เมนูจัดการอาคารสำหรับทุกคน -->

            <?php if ($_SESSION['role'] == 'owner'): ?>
                <hr>
                <li class="nav-item">
                    <a class="nav-link" href="users.php">
                        <i class="bi bi-person-gear"></i> จัดการผู้ใช้
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="settings.php">
                        <i class="bi bi-gear"></i> ตั้งค่าระบบ
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>