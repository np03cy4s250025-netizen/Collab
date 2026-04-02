<?php
// frontend/pages/dashboard.php

require_once '../../backend/config/db.php';
require_once '../../backend/config/session.php';
require_once '../../backend/models/Booking.php';

requireLogin();

$user_id   = $_SESSION['user_id'];
$user      = getCurrentUser();
$navActive = 'dashboard';

$booking  = new Booking($conn);
$bookings = $booking->getBookingsByUser($user_id);

$stats = [
    'total'     => count($bookings),
    'active'    => count(array_filter($bookings, fn($b) => in_array($b['status'], ['pending', 'confirmed']))),
    'completed' => count(array_filter($bookings, fn($b) => $b['status'] === 'completed')),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Manage your SpinGo bookings and rental history.">
    <title>SpinGo | My Bookings</title>
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
        .dash-visuals {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }
        @media (max-width: 992px) {
            .dash-visuals { grid-template-columns: 1fr; }
        }
        .dash-btn-print {
            color: var(--primary);
            font-size: 16px;
            padding: 6px;
            border-radius: 6px;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border-light);
        }
        .dash-btn-print:hover {
            background: var(--bg-light);
            color: var(--accent);
        }
    </style>
</head>
<body>

    <?php include '../includes/navbar.php'; ?>

    <div class="dash-wrapper">

        <!-- Page Header -->
        <div class="dash-header">
            <h1>My Bookings</h1>
            <p>Hello, <?= htmlspecialchars($user['name']) ?> — manage your vehicle reservations below.</p>
        </div>

        <!-- Stat Cards -->
        <div class="dash-stats-grid">
            <div class="dash-stat-card">
                <div class="dash-stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div>
                    <span class="dash-stat-label">Total Bookings</span>
                    <span class="dash-stat-value"><?= $stats['total'] ?></span>
                </div>
            </div>

            <div class="dash-stat-card dash-stat-accent-yellow">
                <div class="dash-stat-icon">
                    <i class="fas fa-car"></i>
                </div>
                <div>
                    <span class="dash-stat-label">Active / Confirmed</span>
                    <span class="dash-stat-value"><?= $stats['active'] ?></span>
                </div>
            </div>

            <div class="dash-stat-card dash-stat-accent-blue">
                <div class="dash-stat-icon">
                    <i class="fas fa-flag-checkered"></i>
                </div>
                <div>
                    <span class="dash-stat-label">Completed Trips</span>
                    <span class="dash-stat-value"><?= $stats['completed'] ?></span>
                </div>
            </div>
        </div>

        <div class="dash-visuals">
            <!-- Main Content -->
            <div class="dash-card" style="margin-bottom:0;">
                <div class="dash-card-header">
                    <h3>Recent Activity</h3>
                </div>
                <div style="padding:20px; height: 300px;">
                    <canvas id="userBookingsTrend"></canvas>
                </div>
            </div>
            
            <!-- Type Usage -->
            <div class="dash-card" style="margin-bottom:0;">
                <div class="dash-card-header">
                    <h3>Vehicle Usage</h3>
                </div>
                <div style="padding:20px; height: 300px; display: flex; align-items: center; justify-content: center;">
                    <canvas id="userTypeChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Booking History -->
        <div class="dash-card">
            <div class="dash-card-header">
                <h3>Booking History</h3>
                <a href="fleet.php" class="btn btn-primary">
                    <i class="fas fa-plus" style="margin-right:5px;"></i>Book a Vehicle
                </a>
            </div>

            <?php if (empty($bookings)): ?>
                <div class="dash-empty">
                    <i class="fas fa-calendar-times"></i>
                    <p>You haven't made any bookings yet.</p>
                    <a href="fleet.php" class="dash-empty-link">Explore our fleet &rarr;</a>
                </div>
            <?php else: ?>
                <div class="dash-table-wrap">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Vehicle</th>
                                <th>Pick-up</th>
                                <th>Drop-off</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td class="dash-td-id">#<?= htmlspecialchars($b['id']) ?></td>
                                <td class="dash-td-vehicle">
                                    <div class="dash-vehicle-name"><?= htmlspecialchars($b['name']) ?></div>
                                    <div class="dash-vehicle-type"><?= ucfirst(htmlspecialchars($b['type'] ?? '')) ?></div>
                                </td>
                                <td><?= date('d M Y', strtotime($b['pickup_date'])) ?></td>
                                <td><?= date('d M Y', strtotime($b['dropoff_date'])) ?></td>
                                <td class="dash-td-price">Rs. <?= number_format($b['total_price'], 2) ?></td>
                                <td>
                                    <span class="dash-status-badge dash-status-<?= htmlspecialchars($b['status']) ?>">
                                        <?= ucfirst(htmlspecialchars($b['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                        <div style="display:flex; gap:8px; align-items:center;">
                                            <a href="print-booking.php?id=<?= $b['id'] ?>" class="dash-btn-print" title="Print Confirmation">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <?php if (in_array($b['status'], ['pending', 'confirmed'])): ?>
                                                <form action="cancel-booking.php" method="POST" style="display:inline;"
                                                      onsubmit="return confirm('Cancel booking #<?= $b['id'] ?>?')">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="booking_id" value="<?= htmlspecialchars($b['id']) ?>">
                                                    <button type="submit" class="dash-btn-cancel" title="Cancel Booking">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <script src="../js/app.js" defer></script>
    <script>
    // ── CHARTS ────────────────────────────────────────────────────────
    const bookings = <?= json_encode($bookings) ?>;
    
    // Process data for Type Chart
    const types = bookings.reduce((acc, b) => {
        const t = b.type || 'unknown';
        acc[t] = (acc[t] || 0) + 1;
        return acc;
    }, {});

    new Chart(document.getElementById('userTypeChart'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(types).map(t => t.charAt(0).toUpperCase() + t.slice(1)),
            datasets: [{
                data: Object.values(types),
                backgroundColor: ['#3F3E46', '#7B6262', '#BFC4C4']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // Process data for Trend Chart (last 6 bookings)
    const recent = [...bookings].reverse().slice(-6);
    new Chart(document.getElementById('userBookingsTrend'), {
        type: 'bar',
        data: {
            labels: recent.map(b => b.name.split(' ')[0]),
            datasets: [{
                label: 'Rental Price (Rs.)',
                data: recent.map(b => b.total_price),
                backgroundColor: '#3F3E46',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } },
            plugins: { legend: { display: false } }
        }
    });
    </script>
</body>
</html>
