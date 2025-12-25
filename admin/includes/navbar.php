<?php
// ============================================
// ไฟล์: admin/includes/navbar.php
// คำอธิบาย: Navbar สำหรับ Admin
// ============================================
?>
<nav class="navbar navbar-dark bg-dark sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-building"></i> ระบบจัดการหอพัก
        </a>
        
        <div class="d-flex align-items-center">
            <span class="text-white me-3">
                <i class="bi bi-person-circle"></i> 
                <?php echo $_SESSION['full_name']; ?> 
                <span class="badge bg-primary"><?php echo strtoupper($_SESSION['role']); ?></span>
            </span>
            <a href="../logout.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
            </a>
        </div>
    </div>
</nav>
