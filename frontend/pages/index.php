<?php
require_once '../../backend/config/db.php';
require_once '../../backend/config/session.php';
require_once '../../backend/config/app.php';
require_once '../../backend/models/Vehicle.php';

$user = getCurrentUser();

// Fetch popular vehicles from DB (up to 6, mix of cars & bikes)
$vehicle_obj = new Vehicle($conn);
$all_vehicles = $vehicle_obj->getAll();
$popular_vehicles = array_slice($all_vehicles, 0, 6);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SpinGo - Premium car and bike rentals with unbeatable prices. Choose from our diverse fleet and experience the freedom of the road.">
    <title>SpinGo | Premium Car & Bike Rentals</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700;800&display=swap&font-display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/home.css">
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar" id="navbar">
        <div class="nav-content">
            <a href="index.php" class="logo" id="logo-link">
                <div class="logo-icon">
                    <i class="fas fa-car"></i>
                </div>
                SpinGo
            </a>
            <ul class="nav-links" id="nav-links">
                <li><a href="index.php" class="nav-active">Home</a></li>
                <li><a href="fleet.php">Fleet</a></li>
                <?php if ($user): ?>
                <li><a href="<?= $user['role'] === 'admin' ? 'admin.php' : 'dashboard.php' ?>">Dashboard</a></li>
                <?php endif; ?>
                <li><a href="#cities">Cities</a></li>
                <li><a href="#about">About</a></li>
            </ul>
            <div class="auth-buttons">
                <?php if ($user): ?>
                    <span class="user-greeting">Hello, <?= htmlspecialchars($user['name']) ?></span>
                    <a href="logout.php" class="btn btn-outline">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">Login</a>
                    <a href="register.php" class="btn btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
            <button class="nav-hamburger" id="hamburger-btn" aria-label="Toggle menu">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Hero Section -->
    <header id="home" class="hero">
        <video
            id="promo-video"
            class="promo-video"
            autoplay
            muted
            loop
            playsinline
            poster="../images/video-poster.jpg">
            <source src="../videos/promo.mp4" type="video/mp4">
        </video>
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Drive Your Dreams<br>Today</h1>
            <p>Premium cars and bikes in Nepal — explore our diverse fleet and book in minutes.</p>
            <a href="fleet.php" class="btn btn-hero">Explore Our Fleet &rarr;</a>
            <div class="hero-trust">
                <span class="hero-trust-item"><i class="fas fa-shield-alt"></i> Fully Insured</span>
                <span class="hero-trust-item"><i class="fas fa-bolt"></i> Instant Booking</span>
                <span class="hero-trust-item"><i class="fas fa-eye"></i> All Major Nepali Cities</span>
            </div>
        </div>
    </header>

    <!-- Categories Section -->
    <section class="section categories-section" id="categories">
        <div class="container">
            <div class="section-header">
                <h2>Drive by Category</h2>
                <p>From luxury sedans to rugged SUVs, find the perfect vehicle for your journey</p>
            </div>
            <div class="categories-grid">
                <a href="fleet.php?type=luxury" class="category-card" id="cat-luxury">
                    <div class="category-img-wrap">
                        <div class="category-img cat-luxury-bg"></div>
                    </div>
                    <div class="category-label">Luxury</div>
                </a>
                <a href="fleet.php?type=suv" class="category-card" id="cat-suv">
                    <div class="category-img-wrap">
                        <div class="category-img cat-suv-bg"></div>
                    </div>
                    <div class="category-label">SUV</div>
                </a>
                <a href="fleet.php?type=sports" class="category-card" id="cat-sports">
                    <div class="category-img-wrap">
                        <div class="category-img cat-sports-bg"></div>
                    </div>
                    <div class="category-label">Sports</div>
                </a>
                <a href="fleet.php?type=convertible" class="category-card" id="cat-convertible">
                    <div class="category-img-wrap">
                        <div class="category-img cat-convertible-bg"></div>
                    </div>
                    <div class="category-label">Convertible</div>
                </a>
            </div>
        </div>
    </section>

    <!-- Popular Vehicles Section -->
    <section class="section popular-section" id="popular">
        <div class="container">
            <div class="section-header">
                <h2>Popular Vehicles</h2>
                <p>Top-rated cars and bikes from our fleet</p>
            </div>

            <?php if (!empty($popular_vehicles)): ?>
            <div class="popular-grid" id="popular-vehicles-grid">
                <?php foreach ($popular_vehicles as $v): ?>
                <div class="pop-vehicle-card<?= $v['type'] === 'bike' ? ' pop-bike-card' : '' ?>">
                    <div class="pop-vehicle-img pop-vehicle-img-wrap">
                        <?php if (!empty($v['image']) && str_starts_with($v['image'], 'http')): ?>
                            <img
                                src="<?= htmlspecialchars($v['image']) ?>"
                                alt="<?= htmlspecialchars($v['name']) ?>"
                                class="pop-vehicle-photo"
                                loading="lazy"
                                onerror="this.style.display='none';this.nextElementSibling.style.display='block';"
                            >
                            <i class="fas <?= $v['type'] === 'bike' ? 'fa-motorcycle' : 'fa-car-side' ?> pop-icon" style="display:none;"></i>
                        <?php elseif ($v['type'] === 'bike'): ?>
                            <i class="fas fa-motorcycle pop-icon"></i>
                        <?php else: ?>
                            <i class="fas fa-car-side pop-icon"></i>
                        <?php endif; ?>
                        <span class="pop-type-badge pop-type-<?= $v['type'] ?>">
                            <?= ucfirst(htmlspecialchars($v['type'])) ?>
                        </span>
                    </div>
                    <div class="pop-vehicle-body">
                        <span class="pop-vehicle-type<?= $v['type'] === 'bike' ? ' pop-type-bike' : '' ?>">
                            <?php if ($v['type'] === 'bike'): ?>
                                <i class="fas fa-motorcycle"></i>
                            <?php endif; ?>
                            <?= htmlspecialchars(ucwords($v['type'] === 'bike' ? 'Motorcycle' : 'Car')) ?>
                        </span>
                        <h3><?= htmlspecialchars($v['name']) ?></h3>
                        <div class="pop-vehicle-specs">
                            <span><i class="fas fa-users"></i> <?= htmlspecialchars($v['seats']) ?></span>
                            <span><i class="fas fa-gas-pump"></i> <?= htmlspecialchars($v['fuel']) ?></span>
                            <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($v['city']) ?></span>
                        </div>
                        <div class="pop-vehicle-footer">
                            <div class="pop-vehicle-price">
                                <span class="currency">Rs.</span><?= number_format($v['price'], 0) ?><span class="period">/day</span>
                            </div>
                            <a href="vehicle-details.php?id=<?= urlencode($v['id']) ?>" class="btn btn-book">Book Now</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div style="text-align:center; padding: 60px 20px; color: var(--ink-3);">
                <i class="fas fa-car" style="font-size:48px; opacity:0.3; display:block; margin-bottom:16px;"></i>
                <p>No vehicles available right now. Check back soon!</p>
            </div>
            <?php endif; ?>

            <div class="section-cta">
                <a href="fleet.php" class="btn btn-view-all">View All Vehicles</a>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="section how-it-works-section" id="how-it-works">
        <div class="container">
            <div class="section-header">
                <h2>How It Works</h2>
                <p>Rent a car in just four simple steps</p>
            </div>
            <div class="steps-grid">
                <div class="step-card" id="step-1">
                    <div class="step-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3>Choose Location</h3>
                    <p>Select your pickup and drop-off locations</p>
                </div>
                <div class="step-card" id="step-2">
                    <div class="step-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3>Select Dates</h3>
                    <p>Pick your rental period and time</p>
                </div>
                <div class="step-card" id="step-3">
                    <div class="step-icon">
                        <i class="fas fa-car"></i>
                    </div>
                    <h3>Choose Your Car</h3>
                    <p>Browse our fleet and select your vehicle</p>
                </div>
                <div class="step-card" id="step-4">
                    <div class="step-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <h3>Book & Drive</h3>
                    <p>Complete payment and hit the road</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Top Cities Section -->
    <section class="section cities-section" id="cities">
        <div class="container">
            <div class="section-header">
                <h2>Available in Top Cities</h2>
                <p>Find us in major cities around the world</p>
            </div>
            <div class="cities-grid">
                <a href="fleet.php?city=Kathmandu" class="city-card">
                    <div class="city-img" style="background-image: linear-gradient(rgba(0,0,0,0.2), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1541414779316-956a5084c0d4?w=600&q=80'); background-size: cover;"></div>
                    <div class="city-info">
                        <h3>Kathmandu</h3>
                        <p>Explore vehicles in Kathmandu</p>
                    </div>
                </a>
                <a href="fleet.php?city=Pokhara" class="city-card">
                    <div class="city-img" style="background-image: linear-gradient(rgba(0,0,0,0.2), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1588693951525-63721346004b?w=600&q=80'); background-size: cover;"></div>
                    <div class="city-info">
                        <h3>Pokhara</h3>
                        <p>Explore vehicles in Pokhara</p>
                    </div>
                </a>
                <a href="fleet.php?city=Lalitpur" class="city-card">
                    <div class="city-img" style="background-image: linear-gradient(rgba(0,0,0,0.2), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1628172909280-e8316c024508?w=600&q=80'); background-size: cover;"></div>
                    <div class="city-info">
                        <h3>Lalitpur</h3>
                        <p>Explore vehicles in Lalitpur</p>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- Trust / Why Choose Us Section -->
    <section class="trust-section" id="about">
        <div class="container">
            <div class="trust-grid">
                <div class="trust-item" id="trust-1">
                    <div class="trust-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3>24/7 Support</h3>
                    <p>Always here to help you on the road</p>
                </div>
                <div class="trust-item" id="trust-2">
                    <div class="trust-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3>Instant Booking</h3>
                    <p>Reserve your car in minutes</p>
                </div>
                <div class="trust-item" id="trust-3">
                    <div class="trust-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Fully Insured</h3>
                    <p>Drive with peace of mind</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="footer">
        <div class="footer-grid">
            <div class="footer-brand">
                <div class="footer-logo">
                    <div class="logo-icon">
                        <i class="fas fa-car"></i>
                    </div>
                    SpinGo
                </div>
                <p>Your trusted partner for premium car rentals. Experience the freedom of the road with our diverse fleet.</p>
                <div class="footer-socials">
                    <a href="#" id="footer-fb" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" id="footer-tw" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" id="footer-ig" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="fleet.php">Our Fleet</a></li>
                    <li><a href="#cities">Locations</a></li>
                    <li><a href="#about">About Us</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Services</h4>
                <ul>
                    <li><a href="fleet.php">Daily Rentals</a></li>
                    <li><a href="fleet.php">Weekly Rentals</a></li>
                    <li><a href="fleet.php">Monthly Rentals</a></li>
                    <li><a href="fleet.php">Airport Pickup</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Contact Us</h4>
                <ul class="footer-contact">
                    <li><i class="fas fa-map-marker-alt"></i> 123 Drive Street, City, ST 12345</li>
                    <li><i class="fas fa-phone"></i> +1 (555) 123-4567</li>
                    <li><i class="fas fa-envelope"></i> hello@spingo.com</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; 2026 SpinGo. All rights reserved.</span>
            <div class="footer-bottom-links">
                <a href="privacy.php">Privacy Policy</a>
                <a href="terms.php">Terms of Service</a>
            </div>
        </div>
    </footer>

    <script>
        const APP_URL = '<?= APP_URL ?? "" ?>';
        const API_BASE_URL = '<?= API_URL ?? "" ?>';
        const CURRENT_USER = <?= $user ? json_encode($user) : 'null' ?>;
    </script>
    <script src="../js/app.js"></script>
    <script src="../js/home.js"></script>
</body>
</html>
