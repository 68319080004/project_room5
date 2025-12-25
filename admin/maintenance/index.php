<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';

requireRole(['admin', 'owner']);

$db = new Database();
$pdo = $db->getConnection();

// แก้ SQL: เปลี่ยน m.status เป็น m.request_status และเปลี่ยนการ JOIN ให้ถูกต้อง
$sql = "
    SELECT 
        m.*,
        r.room_number,
        COALESCE(t.full_name, u.full_name) AS requester_name,
        tech.full_name AS technician_name
    FROM maintenance_requests m
    LEFT JOIN rooms r ON m.room_id = r.room_id
    LEFT JOIN tenants t ON m.tenant_id = t.tenant_id
    LEFT JOIN users u ON m.requested_by_user_id = u.user_id
    LEFT JOIN users tech ON m.assigned_to = tech.user_id
    ORDER BY FIELD(m.request_status, 'new', 'assigned', 'in_progress', 'done'), m.created_at DESC
";

// ^^^ สังเกตบรรทัด ORDER BY เปลี่ยนจาก m.status เป็น m.request_status

try {
    $stmt = $pdo->query($sql);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการแจ้งซ่อม</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>รายการแจ้งซ่อม</h2>
        <a href="../dashboard.php" class="btn btn-secondary">
            &larr; กลับหน้าหลัก
        </a>
    </div>

    <table class="table table-bordered bg-white shadow-sm">
        <thead>
            <tr>
                <th>สถานะ</th>
                <th>วันที่</th>
                <th>ห้อง</th>
                <th>ผู้แจ้ง</th>
                <th>ปัญหา</th>
                <th>ช่าง</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($requests as $r): ?>
            <?php 
                // ใช้ request_status แทน status
                $st = $r['request_status']; 
                $statusColor = match($st) {
                    'new' => 'bg-danger',
                    'assigned' => 'bg-primary',
                    'in_progress' => 'bg-info',
                    'done', 'completed' => 'bg-success',
                    default => 'bg-secondary'
                };
            ?>
            <tr>
                <td><span class="badge <?= $statusColor ?>"><?= $st ?></span></td>
                <td><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>
                <td><?= $r['room_number'] ?></td>
                <td><?= htmlspecialchars($r['requester_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($r['issue_description']) ?></td>
                <td><?= htmlspecialchars($r['technician_name'] ?? '-') ?></td>
                <td>
                    <a href="view.php?id=<?= $r['request_id'] ?>" class="btn btn-sm btn-outline-primary">ดู</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>