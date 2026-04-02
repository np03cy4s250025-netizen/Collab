<?php
// frontend/pages/admin/edit-vehicle.php

require_once '../../../backend/config/db.php';
require_once '../../../backend/config/session.php';

// ✅ Fix: use requireAdmin() — checks $_SESSION['role'] correctly
requireAdmin('../dashboard.php');

$vehicle_id = (int)($_GET['id'] ?? 0);
if (!$vehicle_id) {
    header('Location: ../admin.php');
    exit;
}

// Fetch vehicle — excludes soft-deleted
$stmt = $conn->prepare("SELECT * FROM vehicles WHERE id = :id AND deleted_at IS NULL LIMIT 1");
$stmt->execute([':id' => $vehicle_id]);
$vehicleData = $stmt->fetch();

if (!$vehicleData) {
    header('Location: ../admin.php?error=' . urlencode('Vehicle not found.'));
    exit;
}

$allowedTypes = ['car', 'bike'];
$allowedFuels = ['Petrol', 'Diesel', 'Electric', 'Hybrid', 'CNG'];

// Fetch all cities for localization (Nepal)
$cityStmt = $conn->query("SELECT name FROM cities ORDER BY name ASC");
$nepaliCities = $cityStmt->fetchAll(PDO::FETCH_COLUMN);

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify(); // ✅ CSRF protection

    $name         = trim($_POST['name']  ?? '');
    $type         = trim($_POST['type']  ?? '');
    $fuel         = trim($_POST['fuel']  ?? '');
    $seats        = (int)($_POST['seats'] ?? 0);
    $price        = (float)($_POST['price'] ?? 0);
    $city         = trim($_POST['city']  ?? '');
    $image        = trim($_POST['image'] ?? '');
    $availability = (int)($_POST['availability'] ?? 0);

    // ✅ Whitelist + validation
    if (!$name || !$type || !$fuel || !$seats || !$price || !$city) {
        $error = 'All fields except image are required.';
    } elseif (!in_array($type, $allowedTypes)) {
        $error = 'Invalid vehicle type.';
    } elseif (!in_array($fuel, $allowedFuels)) {
        $error = 'Invalid fuel type.';
    } elseif ($seats < 1 || $seats > 20) {
        $error = 'Seats must be between 1 and 20.';
    } elseif ($price <= 0) {
        $error = 'Price must be greater than 0.';
    } elseif (!in_array($city, $nepaliCities)) {
        $error = 'Please select a valid Nepali city.';
    } elseif ($image && !filter_var($image, FILTER_VALIDATE_URL)) {
        $error = 'Please enter a valid image URL.';
    } else {
        try {
            $stmt = $conn->prepare(
                "UPDATE vehicles
                 SET name=:name, type=:type, fuel=:fuel, seats=:seats,
                     price=:price, city=:city, image=:image, availability=:availability
                 WHERE id=:id"
            );
            $stmt->execute([
                ':name'         => $name,
                ':type'         => $type,
                ':fuel'         => $fuel,
                ':seats'        => $seats,
                ':price'        => $price,
                ':city'         => $city,
                ':image'        => $image ?: null,
                ':availability' => $availability,
                ':id'           => $vehicle_id,
            ]);

            $success = 'Vehicle updated successfully! Redirecting…';
            header('refresh:2;url=../admin.php');

        } catch (PDOException $e) {
            error_log('[EditVehicle] ' . $e->getMessage());
            $error = 'Failed to update vehicle. Please try again.';
        }
    }
}

// Use POST values if re-displaying after error, otherwise DB values
$v = ($_SERVER['REQUEST_METHOD'] === 'POST' && $error) ? $_POST : $vehicleData;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SpinGo | Edit Vehicle — <?= htmlspecialchars($vehicleData['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700;800&display=swap&font-display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/layout.css">
    <link rel="stylesheet" href="../../css/components.css">
    <link rel="stylesheet" href="../../css/main.css">
    <link rel="stylesheet" href="../../css/home.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <style>
        .av-wrap { max-width: 620px; margin: 0 auto; padding: 110px 20px 80px; }
        .av-card  { background:#fff; border-radius:18px; padding:36px 40px; box-shadow:0 4px 24px rgba(0,0,0,0.07); }
        .av-title { font-family:'Space Grotesk',sans-serif; font-size:26px; font-weight:800;
                    color:var(--ink,#1a1a1a); margin-bottom:28px; text-align:center; }
        .av-field { margin-bottom:20px; }
        .av-field label { display:block; font-weight:600; font-size:13px; color:var(--ink-2,#3a3a3a); margin-bottom:7px; }
        .av-field input, .av-field select {
            width:100%; padding:11px 14px; border:1.5px solid var(--sage-dark,#C4D4AE);
            border-radius:10px; font-size:14px; font-family:'Inter',sans-serif;
            color:var(--ink,#1a1a1a); background:var(--sage-light,#EAEFD9);
            outline:none; box-sizing:border-box; transition:border-color 0.2s, box-shadow 0.2s; }
        .av-field input:focus, .av-field select:focus {
            border-color:var(--purple,#8C77A5); background:#fff;
            box-shadow:0 0 0 3px rgba(140,119,165,0.15); }
        .av-alert { padding:12px 16px; border-radius:10px; margin-bottom:18px; font-size:13px; font-weight:500;
                    display:flex; align-items:center; gap:8px; }
        .av-alert-error   { background:#FEF0F0; color:#B91C1C; border:1px solid #FECACA; }
        .av-alert-success { background:#F0FDF4; color:#166534; border:1px solid #BBF7D0; }
        .av-actions { display:flex; gap:12px; margin-top:28px; }
        .av-actions .btn { flex:1; text-align:center; padding:12px; font-size:14px; }
        .av-back { display:inline-flex; align-items:center; gap:6px; font-size:13px;
                   color:var(--ink-3,#888); text-decoration:none; margin-bottom:20px; }
        .av-back:hover { color:var(--purple,#8C77A5); }
        .av-hint { font-size:12px; color:var(--ink-3,#888); margin-top:4px; }
        .av-id-badge { background:var(--sage-light,#EAEFD9); border-radius:8px; padding:8px 14px;
                       font-size:13px; color:var(--ink-3,#888); width:fit-content; margin-bottom:22px; }
    </style>
</head>
<body>

    <!-- Minimal admin navbar -->
    <nav class="navbar" id="navbar">
        <div class="nav-content">
            <a href="../index.php" class="logo" id="logo-link">
                <div class="logo-icon"><i class="fas fa-car"></i></div>
                SpinGo
            </a>
            <div class="auth-buttons">
                <span class="user-greeting">
                    <i class="fas fa-shield-alt"></i> Admin
                </span>
                <a href="../admin.php" class="btn btn-outline">Back to Console</a>
                <a href="../logout.php" class="btn btn-outline">Logout</a>
            </div>
        </div>
    </nav>

    <div class="av-wrap">
        <a href="../admin.php" class="av-back">
            <i class="fas fa-arrow-left"></i> Back to Admin Console
        </a>

        <div class="av-card">
            <h1 class="av-title">
                <i class="fas fa-pen-to-square" style="color:var(--purple);margin-right:8px;"></i>
                Edit Vehicle
            </h1>
            <div class="av-id-badge">
                <i class="fas fa-tag" style="margin-right:5px;"></i>
                Vehicle ID #<?= htmlspecialchars($vehicle_id) ?> — <?= htmlspecialchars($vehicleData['name']) ?>
            </div>

            <?php if ($error): ?>
                <div class="av-alert av-alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="av-alert av-alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST" id="edit-vehicle-form" novalidate>
                <?= csrf_field() ?>

                <div class="av-field">
                    <label for="ev-name">Vehicle Name *</label>
                    <input type="text" id="ev-name" name="name" required
                           value="<?= htmlspecialchars($v['name'] ?? '') ?>">
                </div>

                <div class="av-field">
                    <label for="ev-type">Vehicle Type *</label>
                    <select id="ev-type" name="type" required>
                        <option value="car"  <?= ($v['type'] ?? '') === 'car'  ? 'selected' : '' ?>>Car</option>
                        <option value="bike" <?= ($v['type'] ?? '') === 'bike' ? 'selected' : '' ?>>Bike</option>
                    </select>
                </div>

                <div class="av-field">
                    <label for="ev-fuel">Fuel Type *</label>
                    <select id="ev-fuel" name="fuel" required>
                        <?php foreach ($allowedFuels as $f): ?>
                            <option value="<?= $f ?>"
                                <?= (strtolower($v['fuel'] ?? '') === strtolower($f)) ? 'selected' : '' ?>>
                                <?= $f ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="av-field">
                        <label for="ev-seats">Seats *</label>
                        <input type="number" id="ev-seats" name="seats" required
                               min="1" max="20"
                               value="<?= htmlspecialchars($v['seats'] ?? '') ?>">
                    </div>
                    <div class="av-field">
                        <label for="ev-price">Price / Day (Rs.) *</label>
                        <input type="number" id="ev-price" name="price" required
                               min="1" step="0.01"
                               value="<?= htmlspecialchars($v['price'] ?? '') ?>">
                    </div>
                </div>

                <div class="av-field">
                    <label for="ev-city">City *</label>
                    <select id="ev-city" name="city" required>
                        <?php foreach ($nepaliCities as $c): ?>
                            <option value="<?= $c ?>" <?= (strtolower($v['city'] ?? '') === strtolower($c)) ? 'selected' : '' ?>>
                                <?= $c ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="av-field">
                    <label for="ev-image">Image URL <span style="color:#aaa;font-weight:400;">(optional)</span></label>
                    <input type="url" id="ev-image" name="image"
                           placeholder="https://images.unsplash.com/..."
                           value="<?= htmlspecialchars($v['image'] ?? '') ?>">
                    <p class="av-hint">Leave unchanged to keep the existing photo</p>
                </div>

                <div class="av-field">
                    <label for="ev-avail">Availability</label>
                    <select id="ev-avail" name="availability">
                        <option value="1" <?= ($v['availability'] ?? 1) ? 'selected' : '' ?>>Available</option>
                        <option value="0" <?= !($v['availability'] ?? 1) ? 'selected' : '' ?>>Unavailable</option>
                    </select>
                </div>

                <div class="av-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save" style="margin-right:6px;"></i>Save Changes
                    </button>
                    <a href="../admin.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
    const navbar = document.getElementById('navbar');
    window.addEventListener('scroll', () => navbar?.classList.toggle('scrolled', window.scrollY > 20));
    </script>
</body>
</html>
