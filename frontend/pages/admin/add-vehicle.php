<?php
// frontend/pages/admin/add-vehicle.php

require_once '../../../backend/config/db.php';
require_once '../../../backend/config/session.php';

// ✅ Fix: use requireAdmin() which checks $_SESSION['role'] correctly
requireAdmin('../dashboard.php');

$error   = '';
$success = '';

$allowedTypes  = ['car', 'bike'];
$allowedFuels  = ['Petrol', 'Diesel', 'Electric', 'Hybrid', 'CNG'];

// Fetch all cities for localization (Nepal)
$cityStmt = $conn->query("SELECT name FROM cities ORDER BY name ASC");
$nepaliCities = $cityStmt->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify(); // ✅ CSRF protection

    $name         = trim($_POST['name']  ?? '');
    $type         = trim($_POST['type']  ?? '');
    $fuel         = trim($_POST['fuel']  ?? '');
    $seats        = (int)($_POST['seats'] ?? 0);
    $price        = (float)($_POST['price'] ?? 0);
    $city         = trim($_POST['city']  ?? '');
    $image        = trim($_POST['image'] ?? '');

    // ✅ Whitelist validation for type and fuel
    if (!$name || !$type || !$fuel || !$seats || !$price || !$city) {
        $error = 'All fields except image are required.';
    } elseif (!in_array($type, $allowedTypes)) {
        $error = 'Invalid vehicle type selected.';
    } elseif (!in_array($fuel, $allowedFuels)) {
        $error = 'Invalid fuel type selected.';
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
                "INSERT INTO vehicles (name, type, fuel, seats, price, city, image, availability, created_at)
                 VALUES (:name, :type, :fuel, :seats, :price, :city, :image, 1, NOW())"
            );
            $stmt->execute([
                ':name'  => $name,
                ':type'  => $type,
                ':fuel'  => $fuel,
                ':seats' => $seats,
                ':price' => $price,
                ':city'  => $city,
                ':image' => $image ?: null,
            ]);

            $success = 'Vehicle added successfully! Redirecting…';
            header('refresh:2;url=../admin.php');

        } catch (PDOException $e) {
            // ✅ Log, never expose raw SQL errors
            error_log('[AddVehicle] ' . $e->getMessage());
            $error = 'Failed to add vehicle. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SpinGo | Add Vehicle</title>
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
                <i class="fas fa-plus-circle" style="color:var(--purple);margin-right:8px;"></i>
                Add New Vehicle
            </h1>

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
            <form method="POST" id="add-vehicle-form" novalidate>
                <?= csrf_field() ?>

                <div class="av-field">
                    <label for="av-name">Vehicle Name *</label>
                    <input type="text" id="av-name" name="name" required
                           placeholder="e.g. Toyota Fortuner"
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>

                <div class="av-field">
                    <label for="av-type">Vehicle Type *</label>
                    <select id="av-type" name="type" required>
                        <option value="">-- Select Type --</option>
                        <option value="car"  <?= ($_POST['type'] ?? '') === 'car'  ? 'selected' : '' ?>>Car</option>
                        <option value="bike" <?= ($_POST['type'] ?? '') === 'bike' ? 'selected' : '' ?>>Bike</option>
                    </select>
                </div>

                <div class="av-field">
                    <label for="av-fuel">Fuel Type *</label>
                    <select id="av-fuel" name="fuel" required>
                        <option value="">-- Select Fuel --</option>
                        <?php foreach ($allowedFuels as $f): ?>
                            <option value="<?= $f ?>" <?= ($_POST['fuel'] ?? '') === $f ? 'selected' : '' ?>>
                                <?= $f ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="av-field">
                        <label for="av-seats">Seats *</label>
                        <input type="number" id="av-seats" name="seats" required
                               min="1" max="20" placeholder="e.g. 5"
                               value="<?= htmlspecialchars($_POST['seats'] ?? '') ?>">
                    </div>
                    <div class="av-field">
                        <label for="av-price">Price / Day (Rs.) *</label>
                        <input type="number" id="av-price" name="price" required
                               min="1" step="0.01" placeholder="e.g. 2500"
                               value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
                    </div>
                </div>

                <div class="av-field">
                    <label for="av-city">City *</label>
                    <select id="av-city" name="city" required>
                        <option value="">-- Select City --</option>
                        <?php foreach ($nepaliCities as $c): ?>
                            <option value="<?= $c ?>" <?= ($_POST['city'] ?? '') === $c ? 'selected' : '' ?>>
                                <?= $c ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="av-field">
                    <label for="av-image">Image URL <span style="color:#aaa;font-weight:400;">(optional)</span></label>
                    <input type="url" id="av-image" name="image"
                           placeholder="https://images.unsplash.com/..."
                           value="<?= htmlspecialchars($_POST['image'] ?? '') ?>">
                    <p class="av-hint">Use a direct image URL e.g. from Unsplash</p>
                </div>

                <div class="av-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus" style="margin-right:6px;"></i>Add Vehicle
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
