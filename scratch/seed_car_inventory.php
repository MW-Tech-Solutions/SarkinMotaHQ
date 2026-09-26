<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/divisions_helper.php';

$target_dir = __DIR__ . '/../assets/images/vehicles/';
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0755, true);
}

$source_g63 = 'C:/Users/muhdm/.gemini/antigravity-ide/brain/af1f0023-da4e-4065-9151-89452ee157f9/luxury_g63_amg_1790432751084.jpg';
$source_camry = 'C:/Users/muhdm/.gemini/antigravity-ide/brain/af1f0023-da4e-4065-9151-89452ee157f9/toyota_camry_2024_1790432780144.jpg';
$source_lx600 = 'C:/Users/muhdm/.gemini/antigravity-ide/brain/af1f0023-da4e-4065-9151-89452ee157f9/lexus_lx600_1790432805893.jpg';

if (file_exists($source_g63)) copy($source_g63, $target_dir . 'g63_amg.jpg');
if (file_exists($source_camry)) copy($source_camry, $target_dir . 'toyota_camry.jpg');
if (file_exists($source_lx600)) copy($source_lx600, $target_dir . 'lexus_lx600.jpg');

// Find Automobile Division ID
$stmt = $pdo->query("SELECT id FROM corporate_divisions WHERE slug = 'automobile'");
$auto_div_id = (int)$stmt->fetchColumn();

if ($auto_div_id > 0) {
    // Seed Car 1: Mercedes-Benz G63 AMG
    $ref1 = 'AUTO-2026-0001';
    $chk1 = $pdo->prepare("SELECT id FROM corporate_projects WHERE reference_number = ?");
    $chk1->execute([$ref1]);
    $p1_id = $chk1->fetchColumn();

    if (!$p1_id) {
        $ins1 = $pdo->prepare("INSERT INTO corporate_projects (division_id, title, reference_number, description, status, priority, created_by) VALUES (?, '2025 Mercedes-Benz G63 AMG V8 Biturbo', ?, 'Brand new 2025 Mercedes-Benz G63 AMG finished in Obsidian Black Metallic with Red Nappa Leather interior. Full options, Night Package, Burmester 3D Surround Sound.', 'active', 'Urgent', 1)");
        $ins1->execute([$auto_div_id, $ref1]);
        $p1_id = (int)$pdo->lastInsertId();
    }

    $det1 = $pdo->prepare("INSERT INTO automobile_project_details (project_id, make, model, year, vehicle_type, transmission, fuel_type, mileage, color, vehicle_condition, engine, price, location, cover_image) VALUES (?, 'Mercedes-Benz', 'G63 AMG', 2025, 'suv', 'automatic', 'petrol', '0 km', 'Obsidian Black', 'brand_new', '4.0L V8 Biturbo', 280000000.00, 'Victoria Island, Lagos', 'assets/images/vehicles/g63_amg.jpg') ON DUPLICATE KEY UPDATE price=VALUES(price), cover_image=VALUES(cover_image)");
    $det1->execute([$p1_id]);

    // Seed Car 2: Toyota Camry 2024
    $ref2 = 'AUTO-2026-0002';
    $chk2 = $pdo->prepare("SELECT id FROM corporate_projects WHERE reference_number = ?");
    $chk2->execute([$ref2]);
    $p2_id = $chk2->fetchColumn();

    if (!$p2_id) {
        $ins2 = $pdo->prepare("INSERT INTO corporate_projects (division_id, title, reference_number, description, status, priority, created_by) VALUES (?, '2024 Toyota Camry XSE AWD', ?, 'Brand new 2024 Toyota Camry XSE AWD in Pearl White with Panoramic Sunroof, JBL Audio, Red Leather Seats, and Safety Sense 3.0.', 'active', 'High', 1)");
        $ins2->execute([$auto_div_id, $ref2]);
        $p2_id = (int)$pdo->lastInsertId();
    }

    $det2 = $pdo->prepare("INSERT INTO automobile_project_details (project_id, make, model, year, vehicle_type, transmission, fuel_type, mileage, color, vehicle_condition, engine, price, location, cover_image) VALUES (?, 'Toyota', 'Camry XSE', 2024, 'car', 'automatic', 'petrol', '0 km', 'Pearl White', 'brand_new', '2.5L 4-Cylinder', 48000000.00, 'Ikeja, Lagos', 'assets/images/vehicles/toyota_camry.jpg') ON DUPLICATE KEY UPDATE price=VALUES(price), cover_image=VALUES(cover_image)");
    $det2->execute([$p2_id]);

    // Seed Car 3: Lexus LX600
    $ref3 = 'AUTO-2026-0003';
    $chk3 = $pdo->prepare("SELECT id FROM corporate_projects WHERE reference_number = ?");
    $chk3->execute([$ref3]);
    $p3_id = $chk3->fetchColumn();

    if (!$p3_id) {
        $ins3 = $pdo->prepare("INSERT INTO corporate_projects (division_id, title, reference_number, description, status, priority, created_by) VALUES (?, '2024 Lexus LX600 Ultra Luxury 7-Seater', ?, 'Ultra Luxury 2024 Lexus LX600 V6 Twin-Turbo. Atomic Silver exterior, Caramel Nappa Leather interior, Rear Entertainment screens, Mark Levinson sound.', 'active', 'Urgent', 1)");
        $ins3->execute([$auto_div_id, $ref3]);
        $p3_id = (int)$pdo->lastInsertId();
    }

    $det3 = $pdo->prepare("INSERT INTO automobile_project_details (project_id, make, model, year, vehicle_type, transmission, fuel_type, mileage, color, vehicle_condition, engine, price, location, cover_image) VALUES (?, 'Lexus', 'LX600 Ultra Luxury', 2024, 'suv', 'automatic', 'petrol', '0 km', 'Atomic Silver', 'brand_new', '3.5L V6 Twin-Turbo', 230000000.00, 'Abuja HQ Showroom', 'assets/images/vehicles/lexus_lx600.jpg') ON DUPLICATE KEY UPDATE price=VALUES(price), cover_image=VALUES(cover_image)");
    $det3->execute([$p3_id]);
}

echo "Car inventory seeded successfully.\n";
