-- DriveRent Premium Car Rental Database Schema & Demo Seed Data
-- Database: driverent

CREATE DATABASE IF NOT EXISTS `driverent` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `driverent`;

SET FOREIGN_KEY_CHECKS = 0;


-- --------------------------------------------------------
-- Table structure for `users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `saved_cars`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `user_profiles`;
DROP TABLE IF EXISTS `cars`;
DROP TABLE IF EXISTS `contacts`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('customer', 'admin') DEFAULT 'customer',
  `profile_image` VARCHAR(255) DEFAULT 'default_avatar.png',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `user_profiles`
-- --------------------------------------------------------
CREATE TABLE `user_profiles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `dob` DATE DEFAULT NULL,
  `gender` ENUM('Male', 'Female', 'Other') DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `city` VARCHAR(50) DEFAULT 'Surat',
  `state` VARCHAR(50) DEFAULT 'Gujarat',
  `pincode` VARCHAR(10) DEFAULT NULL,
  `driving_license` VARCHAR(50) DEFAULT NULL,
  `license_expiry` DATE DEFAULT NULL,
  `bio` TEXT DEFAULT NULL,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `saved_cars`
-- --------------------------------------------------------
CREATE TABLE `saved_cars` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `car_id` INT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `user_car_unique` (`user_id`, `car_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`car_id`) REFERENCES `cars`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `notifications`
-- --------------------------------------------------------
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `password_resets`
-- --------------------------------------------------------
CREATE TABLE `password_resets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(100) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `otp` VARCHAR(10) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `sessions`
-- --------------------------------------------------------
CREATE TABLE `sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `token` VARCHAR(255) NOT NULL UNIQUE,
  `user_agent` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `cars`
-- --------------------------------------------------------
CREATE TABLE `cars` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `brand` VARCHAR(50) NOT NULL,
  `model` VARCHAR(50) NOT NULL,
  `category` ENUM('Economy', 'Sedan', 'SUV', 'Luxury', 'Sports', 'Electric') NOT NULL,
  `year` INT NOT NULL,
  `price_per_day` DECIMAL(10, 2) NOT NULL,
  `price_per_hour` DECIMAL(10, 2) NOT NULL,
  `seats` INT NOT NULL,
  `transmission` ENUM('Automatic', 'Manual') NOT NULL,
  `fuel_type` ENUM('Petrol', 'Diesel', 'Electric', 'Hybrid') NOT NULL,
  `mileage` VARCHAR(50) NOT NULL,
  `color` VARCHAR(30) NOT NULL,
  `description` TEXT NOT NULL,
  `image` VARCHAR(255) NOT NULL,
  `status` ENUM('available', 'booked', 'maintenance') DEFAULT 'available',
  `featured` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `bookings`
-- --------------------------------------------------------
CREATE TABLE `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` VARCHAR(30) NOT NULL UNIQUE,
  `user_id` INT NOT NULL,
  `car_id` INT NOT NULL,
  `pickup_location` VARCHAR(100) NOT NULL,
  `drop_location` VARCHAR(100) NOT NULL,
  `pickup_date` DATE NOT NULL,
  `pickup_time` TIME NOT NULL,
  `return_date` DATE NOT NULL,
  `return_time` TIME NOT NULL,
  `rental_days` INT NOT NULL,
  `price_per_day` DECIMAL(10, 2) NOT NULL,
  `subtotal` DECIMAL(10, 2) NOT NULL,
  `tax` DECIMAL(10, 2) NOT NULL,
  `security_deposit` DECIMAL(10, 2) NOT NULL,
  `total_amount` DECIMAL(10, 2) NOT NULL,
  `payment_status` ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
  `booking_status` ENUM('pending', 'confirmed', 'active', 'completed', 'cancelled') DEFAULT 'pending',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`car_id`) REFERENCES `cars`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `payments`
-- --------------------------------------------------------
CREATE TABLE `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL,
  `transaction_id` VARCHAR(100) NOT NULL UNIQUE,
  `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'paid',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `reviews`
-- --------------------------------------------------------
CREATE TABLE `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `car_id` INT NOT NULL,
  `rating` INT NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
  `review` TEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`car_id`) REFERENCES `cars`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `contacts`
-- --------------------------------------------------------
CREATE TABLE `contacts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `subject` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- SEED DATA
-- --------------------------------------------------------

-- Default Admin & Customer Accounts
-- Admin Pass: admin123
-- User Pass: user123
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`, `profile_image`, `created_at`) VALUES
(1, 'DriveRent Administrator', 'admin@driverent.com', '+91 98765 43210', '$2y$12$3n3AA7iOdXo6.e1kFgqMsOv2VxUjUvzpz.x.VeIkM1TsahoSZvqwO', 'admin', 'admin_avatar.png', NOW()),
(2, 'Alex Morgan', 'customer@driverent.com', '+91 91234 56789', '$2y$12$M6/ad4JXYXrqeHbzT6knAemQnCaHHPeNUxQ2lUFlSnBmuWzvuI2jS', 'customer', 'customer_avatar.png', NOW());

-- Cars Fleet Data (60 Premium Vehicles: 10 per category across Surat)
INSERT INTO `cars` (`id`, `brand`, `model`, `category`, `year`, `price_per_day`, `price_per_hour`, `seats`, `transmission`, `fuel_type`, `mileage`, `color`, `description`, `image`, `status`, `featured`, `created_at`) VALUES
(1, 'Mercedes-Benz', 'C-Class', 'Luxury', 2025, 6499.00, 749.00, 5, 'Automatic', 'Petrol', '14 KM/L', 'Obsidian Black', 'Unmatched comfort and executive sophistication in Surat. Modern digital cockpit with ambient lighting and ultra-smooth ride.', 'mercedes_cclass.jpg', 'available', 1, NOW()),
(2, 'BMW', '5 Series', 'Luxury', 2025, 6999.00, 799.00, 5, 'Automatic', 'Petrol', '15 KM/L', 'Phytonic Blue', 'The BMW 5 Series combines executive luxury, dynamic driving performance, and cutting-edge digital technology for Surat executives.', 'bmw_5series.jpg', 'available', 1, NOW()),
(3, 'Audi', 'A6 Matrix', 'Luxury', 2024, 6199.00, 699.00, 5, 'Automatic', 'Petrol', '14 KM/L', 'Glacier White', 'Futuristic Matrix LED headlights, Quattro all-wheel drive, and dual touchscreen cockpit for executive travel across Surat.', 'audi_a6.jpg', 'available', 1, NOW()),
(4, 'Mercedes-Benz', 'S-Class S450', 'Luxury', 2025, 18999.00, 2199.00, 5, 'Automatic', 'Petrol', '11 KM/L', 'Onyx Black', 'The pinnacle of luxury mobility. Chauffeur lounge seats, Burmester 4D surround sound, and airmatic suspension.', 'mercedes_sclass.jpg', 'available', 1, NOW()),
(5, 'BMW', '7 Series 740Li', 'Luxury', 2025, 17499.00, 1999.00, 5, 'Automatic', 'Petrol', '12 KM/L', 'Mineral White', 'Theatre screen in the rear, executive lounge seating, and whisper-quiet V8 twin-turbo engineering for high-profile Surat visits.', 'bmw_7series.jpg', 'available', 1, NOW()),
(6, 'Audi', 'A8 L Quattro', 'Luxury', 2025, 16999.00, 1899.00, 5, 'Automatic', 'Petrol', '11 KM/L', 'Mythos Black', 'Flagship luxury sedan with foot massage seats, OLED tail lamps, and predictive active suspension for seamless Surat transit.', 'audi_a8.jpg', 'available', 0, NOW()),
(7, 'Lexus', 'ES 300h Luxury', 'Luxury', 2024, 6799.00, 799.00, 5, 'Automatic', 'Hybrid', '22 KM/L', 'Deep Blue', 'Ultra-quiet Japanese luxury hybrid sedan with Mark Levinson 17-speaker audio and semi-aniline leather seats.', 'lexus_es300h.jpg', 'available', 0, NOW()),
(8, 'Volvo', 'XC90 Excellence', 'Luxury', 2025, 9499.00, 1149.00, 7, 'Automatic', 'Hybrid', '18 KM/L', 'Crystal White', 'Scandinavian luxury flagship with Bowers & Wilkins crystal audio, massage seats, and Pilot Assist safety.', 'volvo_xc90.jpg', 'available', 0, NOW()),
(9, 'Jaguar', 'XF Portfolio', 'Luxury', 2024, 6299.00, 749.00, 5, 'Automatic', 'Petrol', '13 KM/L', 'British Racing Green', 'Sleek British proportions, Pivi Pro infotainment, and Meridian audio delivering an unmatched grand touring experience.', 'jaguar_xf.jpg', 'available', 0, NOW()),
(10, 'Mercedes-Benz', 'Maybach S580', 'Luxury', 2025, 24999.00, 2999.00, 4, 'Automatic', 'Petrol', '9 KM/L', 'Two-Tone Obsidian & Gold', 'The ultimate VIP statement. Reclining rear first-class suites, champagne flutes, silver calf rests, and silent cabin isolation.', 'maybach_s580.jpg', 'available', 1, NOW()),
(11, 'Toyota', 'Fortuner Legender', 'SUV', 2025, 4999.00, 599.00, 7, 'Automatic', 'Diesel', '12 KM/L', 'Super White & Black', 'Commanding presence, rugged 4x4 capability, and 7-seater executive lounge comfort for family trips and highway cruising.', 'toyota_fortuner.jpg', 'available', 1, NOW()),
(12, 'Range Rover', 'Evoque', 'SUV', 2024, 8499.00, 999.00, 5, 'Automatic', 'Petrol', '11 KM/L', 'Carpathian Grey', 'Iconic British luxury SUV with panoramic sunroof, Meridian surround audio, and refined terrain response tech.', 'rangerover_evoque.jpg', 'available', 1, NOW()),
(13, 'Land Rover', 'Defender 110', 'SUV', 2025, 11999.00, 1399.00, 7, 'Automatic', 'Diesel', '10 KM/L', 'Pangea Green', 'Unstoppable off-road capability matched with 7-seater executive lounge luxury and air suspension.', 'defender_110.jpg', 'available', 1, NOW()),
(14, 'Mercedes-Benz', 'GLE 450 AMG', 'SUV', 2025, 9999.00, 1199.00, 5, 'Automatic', 'Petrol', '10 KM/L', 'Emerald Green', 'Spacious high-performance luxury SUV equipped with MBUX interior assistant and adaptive suspension.', 'mercedes_gle.jpg', 'available', 0, NOW()),
(15, 'BMW', 'X5 xDrive', 'SUV', 2024, 8999.00, 1049.00, 5, 'Automatic', 'Diesel', '12 KM/L', 'Carbon Black', 'Versatile luxury SAV with intelligent all-wheel drive, laser lights, and dynamic athletic driving feel.', 'bmw_x5.jpg', 'available', 0, NOW()),
(16, 'Audi', 'Q5 Sportback', 'SUV', 2024, 7499.00, 899.00, 5, 'Automatic', 'Petrol', '13 KM/L', 'Navarra Blue', 'Coupe-inspired roofline, high stance, refined luxury cabin, and responsive TFSI performance.', 'audi_q5.jpg', 'available', 0, NOW()),
(17, 'Mahindra', 'XUV700 AX7L', 'SUV', 2025, 3499.00, 419.00, 7, 'Automatic', 'Diesel', '15 KM/L', 'Midnight Black', 'Level 2 ADAS safety, dual HD screens, Sony 3D sound system, and memory seats for premium travel across Surat.', 'mahindra_xuv700.jpg', 'available', 0, NOW()),
(18, 'Mahindra', 'Thar LX 4x4', 'SUV', 2024, 2899.00, 349.00, 4, 'Manual', 'Petrol', '13 KM/L', 'Napoli Black', 'Rugged 4x4 adventure SUV with hardtop, high ground clearance, and unstoppable road presence on Dumas road.', 'mahindra_thar.jpg', 'available', 0, NOW()),
(19, 'Tata', 'Safari Dark Edition', 'SUV', 2025, 3299.00, 389.00, 7, 'Automatic', 'Diesel', '14 KM/L', 'Oberon Black', 'Flagship 7-seater lounge SUV with ventilated captain seats, JBL surround audio, and panoramic sunroof.', 'tata_safari.jpg', 'available', 0, NOW()),
(20, 'Hyundai', 'Tucson Signature', 'SUV', 2024, 3799.00, 449.00, 5, 'Automatic', 'Diesel', '16 KM/L', 'Polar White', 'Futuristic jewel parametric grille, HTRAC AWD, and intelligent safety features for seamless Surat city travel.', 'hyundai_tucson.jpg', 'available', 0, NOW()),
(21, 'Porsche', '911 Carrera S', 'Sports', 2025, 14999.00, 1799.00, 2, 'Automatic', 'Petrol', '9 KM/L', 'Guards Red', 'Pure thrill and engineering excellence. 450 HP twin-turbo flat-six engine delivering supercar acceleration on Surat highways.', 'porsche_911.jpg', 'available', 1, NOW()),
(22, 'Ford', 'Mustang GT 5.0 V8', 'Sports', 2025, 13499.00, 1599.00, 4, 'Automatic', 'Petrol', '7 KM/L', 'Race Red', 'Iconic V8 American muscle coupe with line-lock burnout control, digital instrument panel, and raw exhaust note.', 'mustang_gt.jpg', 'available', 1, NOW()),
(23, 'Jaguar', 'F-Type R Convertible', 'Sports', 2025, 15999.00, 1899.00, 2, 'Automatic', 'Petrol', '8 KM/L', 'Firenze Red', 'Pure British supercar performance with a supercharged 575 HP V8 engine and active quad exhaust sound.', 'jaguar_ftype.jpg', 'available', 1, NOW()),
(24, 'BMW', 'M4 Competition', 'Sports', 2025, 14499.00, 1749.00, 4, 'Automatic', 'Petrol', '9 KM/L', 'Sao Paulo Yellow', '510 HP track-honed precision coupe with carbon bucket seats and M xDrive all-wheel drive system.', 'bmw_m4.jpg', 'available', 0, NOW()),
(25, 'Audi', 'RS7 Sportback', 'Sports', 2025, 16499.00, 1999.00, 5, 'Automatic', 'Petrol', '8 KM/L', 'Daytona Grey', '600 HP twin-turbo V8 sports saloon combining supercar speed with 5-seater luxury space.', 'audi_rs7.jpg', 'available', 0, NOW()),
(26, 'Mercedes-AMG', 'GT Coupe', 'Sports', 2025, 17999.00, 2149.00, 2, 'Automatic', 'Petrol', '8 KM/L', 'Magno Selenite Grey', 'Handcrafted 4.0L V8 Biturbo powerhouse delivering brutal acceleration and razor-sharp handling on VIP Road.', 'mercedes_amggt.jpg', 'available', 0, NOW()),
(27, 'Porsche', '718 Boxster GTS', 'Sports', 2024, 12499.00, 1499.00, 2, 'Automatic', 'Petrol', '10 KM/L', 'Miami Blue', 'Mid-engine open-top roadster with telepathic balance, sports exhaust, and instant throttle response.', 'porsche_boxster.jpg', 'available', 0, NOW()),
(28, 'Chevrolet', 'Corvette Stingray C8', 'Sports', 2024, 13999.00, 1649.00, 2, 'Automatic', 'Petrol', '9 KM/L', 'Torch Red', 'Mid-engine 6.2L naturally aspirated V8 with fighter-jet cockpit and removable targa roof for weekend cruising.', 'corvette_stingray.jpg', 'available', 0, NOW()),
(29, 'Maserati', 'Ghibli Trofeo', 'Sports', 2024, 15499.00, 1849.00, 5, 'Automatic', 'Petrol', '8 KM/L', 'Blu Emozione', 'Ferrari-built 580 HP Twin Turbo V8 engine with unmistakable Italian roar and bespoke Pieno Fiore leather.', 'maserati_ghibli.jpg', 'available', 0, NOW()),
(30, 'Nissan', 'GT-R Nismo Track', 'Sports', 2024, 16999.00, 1999.00, 4, 'Automatic', 'Petrol', '8 KM/L', 'Ultimate Silver', 'The legendary Godzilla. ATTESA E-TS all-wheel drive, 600 HP hand-assembled engine with launch control.', 'nissan_gtr.jpg', 'available', 0, NOW()),
(31, 'Honda', 'City ZX', 'Sedan', 2025, 1999.00, 249.00, 5, 'Automatic', 'Petrol', '18 KM/L', 'Golden Brown', 'India’s most celebrated executive sedan. Unmatched rear seat comfort, Honda Sensing ADAS, and smooth CVT.', 'honda_city.jpg', 'available', 0, NOW()),
(32, 'Hyundai', 'Verna Turbo SX(O)', 'Sedan', 2025, 2199.00, 269.00, 5, 'Automatic', 'Petrol', '19 KM/L', 'Starry Night', 'Fastback design, 160 HP 1.5L Turbo petrol engine, dual panoramic screens, and Bose 8-speaker system.', 'hyundai_verna.jpg', 'available', 0, NOW()),
(33, 'Volkswagen', 'Virtus GT Plus', 'Sedan', 2025, 2399.00, 289.00, 5, 'Automatic', 'Petrol', '17 KM/L', 'Wild Cherry Red', 'German engineering at its finest. 1.5 TSI Evo engine with DSG gearbox and 5-star Global NCAP safety rating.', 'volkswagen_virtus.jpg', 'available', 0, NOW()),
(34, 'Skoda', 'Slavia Style', 'Sedan', 2024, 2299.00, 279.00, 5, 'Automatic', 'Petrol', '18 KM/L', 'Crystal Blue', 'European elegance with ventilated seats, massive 521-liter boot, and high ground clearance for Surat city.', 'skoda_slavia.jpg', 'available', 0, NOW()),
(35, 'Toyota', 'Camry Hybrid', 'Sedan', 2025, 4499.00, 549.00, 5, 'Automatic', 'Hybrid', '23 KM/L', 'Platinum White', 'Self-charging executive hybrid sedan with reclinable rear power seats and whisper-silent urban cruising.', 'toyota_camry.jpg', 'available', 0, NOW()),
(36, 'Maruti Suzuki', 'Ciaz Alpha', 'Sedan', 2024, 1799.00, 219.00, 5, 'Automatic', 'Petrol', '20 KM/L', 'Nexa Blue', 'Spacious executive saloon with Smart Hybrid tech, comfortable legroom, and effortless city drive.', 'maruti_ciaz.jpg', 'available', 0, NOW()),
(37, 'Audi', 'A4 Technology', 'Sedan', 2024, 4999.00, 599.00, 5, 'Automatic', 'Petrol', '15 KM/L', 'Ibis White', 'Virtual cockpit, Bang & Olufsen 3D sound, and 2.0 TFSI punch delivering quintessential German prestige.', 'audi_a4.jpg', 'available', 0, NOW()),
(38, 'BMW', '3 Series Gran Limousine', 'Sedan', 2025, 5499.00, 649.00, 5, 'Automatic', 'Petrol', '15 KM/L', 'Portimao Blue', 'Long wheelbase luxury sedan with panoramic glass sunroof, Harman Kardon audio, and sporty dynamics.', 'bmw_3series.jpg', 'available', 0, NOW()),
(39, 'Mercedes-Benz', 'A-Class Limousine', 'Sedan', 2024, 4899.00, 589.00, 5, 'Automatic', 'Petrol', '16 KM/L', 'Polar White', 'Compact executive star featuring widescreen digital cockpit, voice assistant, and refined leatherette cabin.', 'mercedes_aclass.jpg', 'available', 0, NOW()),
(40, 'Skoda', 'Superb L&K', 'Sedan', 2024, 4299.00, 519.00, 5, 'Automatic', 'Petrol', '15 KM/L', 'Lava Blue', 'Limousine-grade rear legroom with Canton 12-speaker audio, massage driver seat, and virtual pedal boot.', 'skoda_superb.jpg', 'available', 0, NOW()),
(41, 'Hyundai', 'Creta SX (O)', 'Economy', 2024, 2499.00, 299.00, 5, 'Automatic', 'Petrol', '17 KM/L', 'Titan Grey', 'Best-in-class compact SUV with ventilated seats, panoramic sunroof, and Bose premium audio system in Surat.', 'hyundai_creta.jpg', 'available', 0, NOW()),
(42, 'Kia', 'Seltos GT Line', 'Economy', 2024, 2599.00, 319.00, 5, 'Automatic', 'Diesel', '18 KM/L', 'Intense Red', 'Sporty design, smart dual 10.25-inch screens, head-up display, and agile urban performance.', 'kia_seltos.jpg', 'available', 0, NOW()),
(43, 'Maruti Suzuki', 'Swift ZXi Plus', 'Economy', 2025, 1299.00, 159.00, 5, 'Manual', 'Petrol', '24 KM/L', 'Luster Blue', 'Brand new Z-Series 1.2L engine, 9-inch SmartPlay Pro+ screen, 6 airbags standard, and unbeatable city efficiency.', 'maruti_swift.jpg', 'available', 0, NOW()),
(44, 'Tata', 'Altroz XZ+ Dark', 'Economy', 2024, 1399.00, 169.00, 5, 'Manual', 'Petrol', '20 KM/L', 'Cosmo Dark', 'Gold standard 5-star safety hatchback with 90-degree opening doors, Harman acoustics, and solid highway poise.', 'tata_altroz.jpg', 'available', 0, NOW()),
(45, 'Hyundai', 'i20 Asta (O)', 'Economy', 2024, 1499.00, 179.00, 5, 'Automatic', 'Petrol', '19 KM/L', 'Fiery Red', 'Premium city hatchback with electric sunroof, ambient lighting, wireless phone charging, and smooth IVT automatic.', 'hyundai_i20.jpg', 'available', 0, NOW()),
(46, 'Maruti Suzuki', 'Baleno Alpha', 'Economy', 2024, 1449.00, 175.00, 5, 'Automatic', 'Petrol', '22 KM/L', 'Celestial Blue', 'Spacious cabin with Head-Up Display (HUD), 360-degree camera, and ultra-high fuel efficiency for Surat city.', 'maruti_baleno.jpg', 'available', 0, NOW()),
(47, 'Tata', 'Nexon Fearless Plus', 'Economy', 2025, 2099.00, 249.00, 5, 'Automatic', 'Petrol', '17 KM/L', 'Creative Ocean', '5-star BNCAP safety rated compact SUV with sequential LED DRLs, ventilated seats, and 10.25-inch touchscreen.', 'tata_nexon.jpg', 'available', 0, NOW()),
(48, 'Maruti Suzuki', 'Brezza ZXi Plus', 'Economy', 2024, 1999.00, 239.00, 5, 'Automatic', 'Petrol', '19 KM/L', 'Brave Khaki', 'Bulletproof reliability, electric sunroof, 360 camera, and smooth 6-speed torque converter automatic.', 'maruti_brezza.jpg', 'available', 0, NOW()),
(49, 'Kia', 'Sonet GTX Plus', 'Economy', 2024, 2199.00, 259.00, 5, 'Automatic', 'Diesel', '20 KM/L', 'Pewter Olive', 'Wild by design. Level 1 ADAS safety, ventilated front seats, Bose 7-speaker sound, and paddle shifters.', 'kia_sonet.jpg', 'available', 0, NOW()),
(50, 'Renault', 'Kiger RXZ Turbo', 'Economy', 2024, 1499.00, 179.00, 5, 'Automatic', 'Petrol', '18 KM/L', 'Radiant Red', 'Sporty 1.0L turbo engine with X-TRONIC CVT, drive modes, 405L best-in-class boot space, and high ground clearance.', 'renault_kiger.jpg', 'available', 0, NOW()),
(51, 'Tata', 'Nexon.ev Empowered', 'Electric', 2025, 2699.00, 319.00, 5, 'Automatic', 'Electric', '465 KM Range', 'Intensi-Teal', 'India’s #1 EV. 40.5 kWh battery with fast DC charging, V2V & V2L power bank functionality, and zero emissions.', 'tata_nexonev.jpg', 'available', 1, NOW()),
(52, 'Tata', 'Curvv.ev 55', 'Electric', 2025, 3199.00, 379.00, 5, 'Automatic', 'Electric', '585 KM Range', 'Virtual Sunrise', 'Stunning SUV Coupe with 55 kWh long-range battery, gesture tailgate, Level 2 ADAS, and 123 kW fast charging.', 'tata_curvvev.jpg', 'available', 0, NOW()),
(53, 'MG', 'ZS EV Exclusive Pro', 'Electric', 2024, 2999.00, 359.00, 5, 'Automatic', 'Electric', '461 KM Range', 'Starry Black', '50.3 kWh prismatic battery, panoramic dual-pane sunroof, Level 2 autonomous driving, and i-SMART connected car suite.', 'mg_zsev.jpg', 'available', 0, NOW()),
(54, 'BYD', 'Seal Performance AWD', 'Electric', 2025, 5999.00, 719.00, 5, 'Automatic', 'Electric', '580 KM Range', 'Arctic Blue', '0 to 100 km/h in 3.8 seconds! 530 HP dual-motor AWD with ultra-safe Blade Battery and rotatable 15.6-inch screen.', 'byd_seal.jpg', 'available', 1, NOW()),
(55, 'Hyundai', 'Ioniq 5 AWD', 'Electric', 2025, 6499.00, 779.00, 5, 'Automatic', 'Electric', '631 KM Range', 'Gravity Gold Matte', 'World Car of the Year. 800V ultra-fast charging (10-80% in 18 mins), relaxation seats, and futuristic parametric design.', 'hyundai_ioniq5.jpg', 'available', 0, NOW()),
(56, 'Kia', 'EV6 GT-Line AWD', 'Electric', 2025, 6999.00, 829.00, 5, 'Automatic', 'Electric', '708 KM Range', 'Moonscape Matte', 'Supercar acceleration with 325 HP AWD, Meridian 14-speaker audio, augmented reality HUD, and ultra-high range.', 'kia_ev6.jpg', 'available', 0, NOW()),
(57, 'BMW', 'i4 eDrive40 M Sport', 'Electric', 2025, 8499.00, 999.00, 5, 'Automatic', 'Electric', '590 KM Range', 'Sanremo Green', 'Pure electric Gran Coupe with curved display, iconic Hans Zimmer electric sounds, and legendary BMW rear-wheel drive.', 'bmw_i4.jpg', 'available', 0, NOW()),
(58, 'Mercedes-Benz', 'EQE 500 4MATIC SUV', 'Electric', 2025, 11499.00, 1349.00, 5, 'Automatic', 'Electric', '550 KM Range', 'Sodalite Blue', 'Next-generation electric luxury SUV featuring MBUX Hyperscreen, HEPA air filtration, and 10-degree rear-axle steering.', 'mercedes_eqe.jpg', 'available', 0, NOW()),
(59, 'Audi', 'e-tron 55 Quattro', 'Electric', 2024, 10999.00, 1299.00, 5, 'Automatic', 'Electric', '484 KM Range', 'Catalunya Red', 'Dual electric motors with boost mode, virtual side mirrors, adaptive air suspension, and whisper-quiet cabin.', 'audi_etron.jpg', 'available', 0, NOW()),
(60, 'Porsche', 'Taycan 4S Cross Turismo', 'Electric', 2025, 15999.00, 1899.00, 4, 'Automatic', 'Electric', '512 KM Range', 'Frozen Blue Metallic', '530 HP electric sports powerhouse with 2-speed transmission on the rear axle, gravel mode, and launch control.', 'porsche_taycan.jpg', 'available', 1, NOW());

-- Sample Booking Data
INSERT INTO `bookings` (`id`, `booking_id`, `user_id`, `car_id`, `pickup_location`, `drop_location`, `pickup_date`, `pickup_time`, `return_date`, `return_time`, `rental_days`, `price_per_day`, `subtotal`, `tax`, `security_deposit`, `total_amount`, `payment_status`, `booking_status`, `created_at`) VALUES
(1, 'DR-2026-X892', 2, 1, 'Surat International Airport (STV)', 'Surat International Airport (STV)', '2026-09-01', '10:00:00', '2026-09-04', '10:00:00', 3, 6999.00, 20997.00, 3779.46, 5000.00, 29776.46, 'paid', 'confirmed', NOW());

-- Sample Payment
INSERT INTO `payments` (`id`, `booking_id`, `user_id`, `amount`, `payment_method`, `transaction_id`, `payment_status`, `created_at`) VALUES
(1, 1, 2, 29776.46, 'Credit Card', 'TXN-9823410982', 'paid', NOW());

-- Sample Reviews
INSERT INTO `reviews` (`id`, `user_id`, `car_id`, `rating`, `review`, `created_at`) VALUES
(1, 2, 1, 5, 'Rented the BMW 5 Series for a business weekend in Surat. Smooth check-in, spotless car, and incredible driving dynamics. Highly recommended!', NOW());

-- Sample Contact Form Message
INSERT INTO `contacts` (`id`, `name`, `email`, `phone`, `subject`, `message`, `created_at`) VALUES
(1, 'David Miller', 'david.m@example.com', '+91 99887 76655', 'Corporate Booking Inquiry', 'Hello DriveRent team, we are interested in long-term luxury car rentals for our executive team. Please contact us.', NOW());

-- Seed User Profiles
INSERT INTO `user_profiles` (`id`, `user_id`, `dob`, `gender`, `address`, `city`, `state`, `pincode`, `driving_license`, `license_expiry`, `bio`, `updated_at`) VALUES
(1, 1, '1990-05-15', 'Male', 'DriveRent Corporate HQ, Vesu Main Road', 'Surat', 'Gujarat', '395007', 'GJ-05-2012-9876543', '2032-05-15', 'Lead Administrator for DriveRent Fleet Operations in Surat.', NOW()),
(2, 2, '1995-08-20', 'Male', '402 Sunset Heights, Piplod', 'Surat', 'Gujarat', '395007', 'GJ-05-2018-1234567', '2038-08-20', 'Frequent executive traveler & car enthusiast.', NOW());

-- Seed Saved Cars
INSERT INTO `saved_cars` (`id`, `user_id`, `car_id`, `created_at`) VALUES
(1, 2, 1, NOW()),
(2, 2, 6, NOW());

-- Seed Notifications
INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 2, 'Booking Confirmed!', 'Your reservation for BMW 5 Series (DR-2026-X892) has been confirmed.', 'success', 0, NOW()),
(2, 2, 'Welcome to DriveRent', 'Complete your profile details to enjoy 1-click doorstep car rentals in Surat.', 'info', 1, NOW());

SET FOREIGN_KEY_CHECKS = 1;

