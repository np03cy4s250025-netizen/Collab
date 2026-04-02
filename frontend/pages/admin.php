<?php
// frontend/pages/admin.php

require_once '../../backend/config/db.php';
require_once '../../backend/config/session.php';
require_once '../../backend/models/Booking.php';
require_once '../../backend/models/Vehicle.php';

requireAdmin(); // ✅ Uses isAdmin() which checks $_SESSION['role'] correctly

$flash = '';

// ── Handle POST actions ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify(); // ✅ CSRF on all admin POST actions

    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $booking_id = (int)($_POST['booking_id'] ?? 0);
        $allowed    = ['pending', 'confirmed', 'completed', 'cancelled'];
        $status     = in_array($_POST['status'] ?? '', $allowed) ? $_POST['status'] : 'pending';

        $stmt = $conn->prepare("UPDATE bookings SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $booking_id]);
        $flash = 'Booking status updated.';

    } elseif ($action === 'delete_vehicle') {
        $vid  = (int)($_POST['vehicle_id'] ?? 0);
        // Soft delete — preserves booking history and referential integrity
        $stmt = $conn->prepare("UPDATE vehicles SET deleted_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $vid]);
        $flash = 'Vehicle removed.';

    } elseif ($action === 'delete_user') {
        $uid = (int)($_POST['user_id'] ?? 0);
        // Prevent self-deletion
        if ($uid !== (int)$_SESSION['user_id']) {
            // Soft delete — preserves booking history
            $stmt = $conn->prepare("UPDATE users SET deleted_at = NOW() WHERE id = :id AND role != 'admin'");
            $stmt->execute([':id' => $uid]);
            $flash = 'User removed.';
        }
    }
}

// ── Fetch data ────────────────────────────────────────────────────────────────
$bookingModel = new Booking($conn);
$vehicleModel = new Vehicle($conn);

$bookings = $bookingModel->getAllBookings();
$vehicles = $vehicleModel->getAll();

$userCount    = (int)$conn->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND deleted_at IS NULL")->fetchColumn();
$bookingCount = (int)$conn->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$pendingCount = (int)$conn->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
$users        = $conn->query("SELECT id, full_name, email, role, is_verified, created_at FROM users WHERE deleted_at IS NULL ORDER BY created_at DESC")->fetchAll();

$user      = getCurrentUser();
$navActive = 'admin';

// ── Chart Data ──────────────────────────────────────────────────────────────
// 1. Bookings & Revenue over time (Last 6 months)
$timelineQuery = $conn->query("
    SELECT 
        DATE_FORMAT(created_at, '%b %Y') as month,
        COUNT(*) as booking_count,
        SUM(CASE WHEN status = 'completed' THEN total_price ELSE 0 END) as revenue
    FROM bookings
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY created_at ASC
");
$timelineData = $timelineQuery->fetchAll(PDO::FETCH_ASSOC);

// 2. Vehicle Availability Distribution
$availabilityData = $conn->query("
    SELECT 
        CASE WHEN availability = 1 THEN 'Available' ELSE 'Unavailable' END as status,
        COUNT(*) as count
    FROM vehicles
    WHERE deleted_at IS NULL
    GROUP BY availability
")->fetchAll(PDO::FETCH_ASSOC);

// 3. Vehicle Type Distribution (Car vs Bike)
$typeData = $conn->query("
    SELECT type, COUNT(*) as count
    FROM vehicles
    WHERE deleted_at IS NULL
    GROUP BY type
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SpinGo Admin Console — manage fleet, bookings, and users.">
    <title>SpinGo | Admin Console</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700;800&display=swap&font-display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .btn-reject:hover { background: #FEE2E2; }
        
        .row-pending:hover {
            background-color: #FEF3C7 !important;
        }
        .admin-license-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--purple);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            padding: 4px 8px;
            background: #F3E8FF;
            border-radius: 6px;
            transition: opacity 0.2s;
        }
        .admin-license-link:hover {
            opacity: 0.8;
        }
    </style>
</head>
<body>

    <?php include '../includes/navbar.php'; ?>

    <div class="dash-wrapper">

        <!-- Page Header -->
        <div class="dash-header">
            <h1>Admin Console</h1>
            <p>Manage fleet, bookings, and users across the platform.</p>
        </div>

        <?php if ($flash): ?>
            <div style="background:#DCFCE7;color:#166534;border:1px solid #BBF7D0;padding:12px 18px;border-radius:10px;margin-bottom:24px;font-size:14px;font-weight:500;">
                <i class="fas fa-check-circle" style="margin-right:6px;"></i>
                <?= htmlspecialchars($flash) ?>
            </div>
        <?php endif; ?>

        <!-- Stat Cards -->
        <div class="dash-stats-grid">
            <div class="dash-stat-card">
                <div class="dash-stat-icon"><i class="fas fa-users"></i></div>
                <div>
                    <span class="dash-stat-label">Registered Users</span>
                    <span class="dash-stat-value"><?= $userCount ?></span>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon"><i class="fas fa-car"></i></div>
                <div>
                    <span class="dash-stat-label">Fleet Size</span>
                    <span class="dash-stat-value"><?= count($vehicles) ?></span>
                </div>
            </div>
            <div class="dash-stat-card dash-stat-accent-blue">
                <div class="dash-stat-icon"><i class="fas fa-calendar"></i></div>
                <div>
                    <span class="dash-stat-label">Total Bookings</span>
                    <span class="dash-stat-value"><?= $bookingCount ?></span>
                </div>
            </div>
            <div class="dash-stat-card dash-stat-accent-yellow">
                <div class="dash-stat-icon"><i class="fas fa-hourglass-half"></i></div>
                <div>
                    <span class="dash-stat-label">Pending Bookings</span>
                    <span class="dash-stat-value"><?= $pendingCount ?></span>
                </div>
            </div>
        </div>

        <!-- ── Charts Section ────────────────────────────────────────── -->
        <div class="admin-charts-grid">
            <div class="chart-card">
                <h3><i class="fas fa-chart-line"></i> Bookings & Revenue (6 Mo)</h3>
                <canvas id="bookingsChart" height="200"></canvas>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-chart-pie"></i> Fleet Composition</h3>
                <canvas id="fleetTypeChart" height="200"></canvas>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-check-circle"></i> Service Availability</h3>
                <canvas id="availabilityChart" height="200"></canvas>
            </div>
        </div>

        <!-- ── Manage Vehicles ────────────────────────────────────────── -->
        <div class="dash-card">
            <div class="dash-card-header">
                <h2>Manage Vehicles</h2>
                <a href="admin/add-vehicle.php" class="btn btn-primary">
                    <i class="fas fa-plus" style="margin-right:5px;"></i> Add Vehicle
                </a>
            </div>
            <div class="dash-table-wrap">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Vehicle</th>
                            <th>Type</th>
                            <th>Price/Day</th>
                            <th>Seats</th>
                            <th>Status</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($vehicles)): ?>
                            <?php foreach ($vehicles as $v): ?>
                            <tr>
                                <td class="dash-td-id"><?= htmlspecialchars($v['id']) ?></td>
                                <td>
                                    <div class="dash-vehicle-name"><?= htmlspecialchars($v['name']) ?></div>
                                    <div class="dash-vehicle-type"><?= htmlspecialchars($v['city'] ?? '') ?></div>
                                </td>
                                <td><?= ucfirst(htmlspecialchars($v['type'])) ?></td>
                                <td class="dash-td-price">Rs. <?= number_format($v['price'], 2) ?></td>
                                <td><?= htmlspecialchars($v['seats']) ?></td>
                                <td>
                                    <?php if (!empty($v['availability'])): ?>
                                        <span class="dash-status-badge dash-status-confirmed">Available</span>
                                    <?php else: ?>
                                        <span class="dash-status-badge dash-status-cancelled">Unavailable</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right; white-space:nowrap;">
                                    <a href="admin/edit-vehicle.php?id=<?= htmlspecialchars($v['id']) ?>" class="admin-action-link" title="Edit" aria-label="Edit <?= htmlspecialchars($v['name']) ?>">
                                        <i class="fas fa-pen-to-square"></i>
                                    </a>
                                    <form method="POST" style="display:inline;"
                                          onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($v['name'])) ?>?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action"     value="delete_vehicle">
                                        <input type="hidden" name="vehicle_id" value="<?= htmlspecialchars($v['id']) ?>">
                                        <button type="submit" class="admin-delete-btn" title="Delete" aria-label="Delete <?= htmlspecialchars($v['name']) ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="padding:40px;text-align:center;color:#999;">
                                    No vehicles yet. <a href="admin/add-vehicle.php" style="color:var(--purple);">Add one now &rarr;</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── All Bookings ───────────────────────────────────────────── -->
        <div class="dash-card">
            <div class="dash-card-header">
                <h2>All Bookings</h2>
            </div>
            <div class="dash-table-wrap">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Customer</th>
                            <th>Vehicle</th>
                            <th>Dates</th>
                            <th>Total</th>
                            <th>License</th>
                            <th>Status</th>
                            <th>Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($bookings)): ?>
                            <?php foreach ($bookings as $b): ?>
                            <tr class="<?= $b['status'] === 'pending' ? 'row-pending' : '' ?>">
                                <td class="dash-td-id">#<?= htmlspecialchars($b['id']) ?></td>
                                <td>
                                    <div class="dash-vehicle-name"><?= htmlspecialchars($b['full_name'] ?? 'Unknown') ?></div>
                                    <div class="dash-vehicle-type"><?= htmlspecialchars($b['email'] ?? '') ?></div>
                                </td>
                                <td><?= htmlspecialchars($b['vehicle_name'] ?? 'N/A') ?></td>
                                <td style="font-size:13px;white-space:nowrap;">
                                    <?= date('d M Y', strtotime($b['pickup_date'])) ?> &rarr;
                                    <?= date('d M Y', strtotime($b['dropoff_date'])) ?>
                                </td>
                                <td class="dash-td-price" style="color:#10B981;">
                                    Rs. <?= number_format($b['total_price'], 2) ?>
                                </td>
                                <td>
                                    <?php if (!empty($b['license_file'])): ?>
                                        <a href="../uploads/licenses/<?= htmlspecialchars($b['license_file']) ?>" 
                                           target="_blank" class="admin-license-link" title="View License">
                                            <i class="fas fa-id-card"></i> View
                                        </a>
                                    <?php else: ?>
                                        <span style="color:#ccc; font-size:12px;">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="dash-status-badge dash-status-<?= htmlspecialchars($b['status']) ?> <?= $b['status'] === 'pending' ? 'status-badge-urgent' : '' ?>">
                                        <?= ucfirst(htmlspecialchars($b['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="admin-quick-actions">
                                        <?php if ($b['status'] === 'pending'): ?>
                                            <form method="POST" style="display:inline;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="booking_id" value="<?= htmlspecialchars($b['id']) ?>">
                                                <input type="hidden" name="status" value="confirmed">
                                                <button type="submit" class="btn-action btn-approve" title="Approve Booking">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <form method="POST" style="display:inline;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="booking_id" value="<?= htmlspecialchars($b['id']) ?>">
                                                <input type="hidden" name="status" value="cancelled">
                                                <button type="submit" class="btn-action btn-reject" title="Reject Booking">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display:inline;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action"     value="update_status">
                                                <input type="hidden" name="booking_id" value="<?= htmlspecialchars($b['id']) ?>">
                                                <select name="status" onchange="this.form.submit()"
                                                        style="padding:5px 8px;border:1px solid #ddd;border-radius:6px;font-size:12px;cursor:pointer;font-family:inherit;">
                                                    <option value="pending"   <?= $b['status']==='pending'   ? 'selected' : '' ?>>Pending</option>
                                                    <option value="confirmed" <?= $b['status']==='confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                                    <option value="completed" <?= $b['status']==='completed' ? 'selected' : '' ?>>Completed</option>
                                                    <option value="cancelled" <?= $b['status']==='cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                </select>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="padding:40px;text-align:center;color:#999;">No bookings yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── Registered Users ───────────────────────────────────────── -->
        <div class="dash-card">
            <div class="dash-card-header">
                <h2>Registered Users</h2>
            </div>
            <div class="dash-table-wrap">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Verified</th>
                            <th>Joined</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php $idx = 1; foreach ($users as $u): ?>
                            <tr>
                                <td class="dash-td-id">#<?= $idx++ ?></td>
                                <td class="dash-vehicle-name"><?= htmlspecialchars($u['full_name']) ?></td>
                                <td style="color:#555;font-size:13px;"><?= htmlspecialchars($u['email']) ?></td>
                                <td>
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="dash-status-badge" style="background:#EDE9FE;color:#7C3AED;">Admin</span>
                                    <?php else: ?>
                                        <span class="dash-status-badge" style="background:#F1F5F9;color:#64748B;">User</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <!-- ✅ Fix: no emoji — use proper icons to avoid mojibake -->
                                    <?php if ($u['is_verified']): ?>
                                        <i class="fas fa-check-circle" style="color:#10B981;" title="Verified"></i>
                                    <?php else: ?>
                                        <i class="fas fa-times-circle" style="color:#EF4444;" title="Unverified"></i>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size:13px;color:#555;">
                                    <?= date('d M Y', strtotime($u['created_at'])) ?>
                                </td>
                                <td style="text-align:right;">
                                    <?php if ($u['id'] !== $_SESSION['user_id'] && $u['role'] !== 'admin'): ?>
                                        <form method="POST" style="display:inline;"
                                              onsubmit="return confirm('Delete user <?= htmlspecialchars(addslashes($u['full_name'])) ?>?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action"  value="delete_user">
                                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($u['id']) ?>">
                                            <button type="submit" class="admin-delete-btn" title="Delete user" aria-label="Delete user <?= htmlspecialchars($u['full_name']) ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="font-size:12px;color:#ccc;">
                                            <?= $u['id'] === $_SESSION['user_id'] ? '(You)' : '' ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="padding:40px;text-align:center;color:#999;">No users yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /.dash-wrapper -->

    <script src="../js/app.js" defer></script>
    <script>
    // ── CHARTS ────────────────────────────────────────────────────────
    const timelineData = <?= json_encode($timelineData) ?>;
    const availData = <?= json_encode($availabilityData) ?>;
    const typeData = <?= json_encode($typeData) ?>;

    // Bookings & Revenue
    new Chart(document.getElementById('bookingsChart'), {
        type: 'line',
        data: {
            labels: timelineData.map(d => d.month),
            datasets: [
                {
                    label: 'Bookings',
                    data: timelineData.map(d => d.booking_count),
                    borderColor: '#3F3E46',
                    backgroundColor: 'rgba(63, 62, 70, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Revenue (k)',
                    data: timelineData.map(d => d.revenue / 1000),
                    borderColor: '#7B6262',
                    backgroundColor: 'transparent',
                    borderDash: [5, 5],
                    tension: 0.4,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true },
                y1: { position: 'right', grid: { display: false } }
            }
        }
    });

    // Fleet Composition
    new Chart(document.getElementById('fleetTypeChart'), {
        type: 'doughnut',
        data: {
            labels: typeData.map(d => d.type.charAt(0).toUpperCase() + d.type.slice(1)),
            datasets: [{
                data: typeData.map(d => d.count),
                backgroundColor: ['#3F3E46', '#7B6262', '#BFC4C4', '#52525C']
            }]
        },
        options: { responsive: true, cutout: '70%' }
    });

    // Availability
    new Chart(document.getElementById('availabilityChart'), {
        type: 'bar',
        data: {
            labels: availData.map(d => d.status),
            datasets: [{
                label: 'Vehicles',
                data: availData.map(d => d.count),
                backgroundColor: availData.map(d => d.status === 'Available' ? '#10B981' : '#EF4444')
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
    </script>

</body>
</html>
