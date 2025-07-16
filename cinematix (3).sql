-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1cinematixcinematixcinematix
-- Generation Time: May 01, 2025 at 07:05 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cinematix`
--

-- --------------------------------------------------------

--
-- Table structure for table `booked_seats`
--

CREATE TABLE `booked_seats` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `seat_code` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booked_seats`
--

INSERT INTO `booked_seats` (`id`, `booking_id`, `seat_code`) VALUES
(9, 10, 'A1'),
(10, 11, 'A1'),
(11, 12, 'A1'),
(12, 13, 'A1'),
(13, 13, 'A2'),
(14, 14, 'A1'),
(15, 14, 'A5');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `film_id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `customer_phone` varchar(15) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `seat_count` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `studio` int(11) NOT NULL,
  `seat_code` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cinema_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `film_id`, `customer_name`, `customer_email`, `customer_phone`, `schedule_id`, `seat_count`, `total_price`, `payment_method`, `studio`, `seat_code`, `created_at`, `cinema_id`) VALUES
(10, 6, 'nabil', 'nabil@gmail.com', '083152726536', 418, 1, 50000.00, 'GoPay', 3, 'A1', '2025-04-29 15:27:34', 101),
(11, 3, 'arzaki', 'arzaki@gmail.com', '0899226655187', 419, 1, 55000.00, 'ShopeePay', 3, 'A1', '2025-04-29 15:32:44', 101),
(12, 8, 'nabil', 'nabil@gmail.com', '083152726536', 421, 1, 50000.00, 'QRIS', 3, 'A1', '2025-04-30 16:32:02', 101),
(13, 7, 'intan', 'intanazahra11@gmail.com', '089922665511', 417, 2, 100000.00, 'DANA', 1, 'A1,A2', '2025-05-01 04:30:34', 101),
(14, 6, 'adit', 'aditjarwo@gmail.com', '083196089559', 416, 2, 100000.00, 'GoPay', 3, 'A1,A5', '2025-05-01 04:48:19', 101);

-- --------------------------------------------------------

--
-- Table structure for table `booking_success`
--

CREATE TABLE `booking_success` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `seat_count` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `seats_selected` text DEFAULT NULL,
  `booking_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cinemas`
--

CREATE TABLE `cinemas` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `capacity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image` varchar(255) DEFAULT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `studio_id` int(11) NOT NULL,
  `film_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cinemas`
--

INSERT INTO `cinemas` (`id`, `name`, `location`, `capacity`, `created_at`, `image`, `schedule_id`, `studio_id`, `film_id`) VALUES
(101, 'Cinema XXI Transmart Padang', 'Jln.Khatib Sulaiman', 600, '2025-04-28 10:34:28', NULL, 415, 1111, 2);

-- --------------------------------------------------------

--
-- Table structure for table `films`
--

CREATE TABLE `films` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `genre_name` varchar(11) DEFAULT NULL,
  `duration` int(11) NOT NULL,
  `rating` varchar(10) NOT NULL,
  `synopsis` text NOT NULL,
  `poster` varchar(255) NOT NULL,
  `price` int(11) NOT NULL,
  `studio` int(100) NOT NULL,
  `cinema_id` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `films`
--

INSERT INTO `films` (`id`, `title`, `genre_name`, `duration`, `rating`, `synopsis`, `poster`, `price`, `studio`, `cinema_id`, `created_at`) VALUES
(1, 'Ancika 1995', '333', 120, '4.9', 'Kisah cinta Ancika dan Dilan', 'ancika.jpg', 50000, 1, '101', '2025-04-28 08:44:23'),
(2, 'Home Sweat Loan', '666', 93, '4', 'Seorang pegawai kantoran dari kelas menengah yang tinggal bersama keluarga besar di sebuah rumah sederhana. ', 'home.jpg', 50000, 2, '101', '2025-04-28 08:44:23'),
(3, 'Fast & Furious 9', '111', 145, '4.3', 'Petualangan Dominic Toretto', 'fast9.jpg', 50000, 3, '101', '2025-04-28 08:44:23'),
(4, 'Avengers: Endgame', '111', 180, '4.8', 'Pertarungan terakhir Avengers', 'avengers.jpg', 50000, 1, '101', '2025-04-28 08:44:23'),
(5, 'Pengabdi Setan 2', '222', 107, '4.6', 'Kisah horor keluarga di pedesaan', 'pengabdi2.jpg', 50000, 2, '101', '2025-04-28 08:44:23'),
(6, 'Racun Sangga', '222', 105, '4.3', 'Santet dari orang di masa lalu yang menghancurkan rumah tangga', 'racun.jpg', 50000, 3, '101', '2025-04-28 08:44:23'),
(7, 'Jumbo', '444', 105, '4.7', 'Kisah Jumbo', 'jumbo.jpg', 50000, 1, '101', '2025-04-28 16:09:07'),
(8, 'KangMak', '555', 106, '4.1', 'Premis ceritanya mengangkat kisah seorang pejuang bernama Makmur yang kembali dari medan perang dan menemukan istrinya yang ternyata telah menjadi hantu.', 'kangmak.jpg', 50000, 3, '101', '2025-04-30 06:19:43');

-- --------------------------------------------------------

--
-- Table structure for table `genres`
--

CREATE TABLE `genres` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `genres`
--

INSERT INTO `genres` (`id`, `name`) VALUES
(111, 'Action'),
(222, 'Horror'),
(333, 'Romance'),
(444, 'Cartoon'),
(555, 'Comedy'),
(666, 'Drama');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `payment_method` enum('ShopeePay','DANA','GoPay','QRIS') NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `booking_id`, `customer_name`, `total_price`, `payment_method`, `order_date`) VALUES
(9, 10, 'nabil', 45000.00, 'GoPay', '2025-04-29 15:27:34'),
(10, 11, 'arzaki', 55000.00, 'ShopeePay', '2025-04-29 15:32:44'),
(11, 12, 'nabil', 50000.00, 'QRIS', '2025-04-30 16:32:02'),
(12, 13, 'intan', 100000.00, 'DANA', '2025-05-01 04:30:34'),
(13, 14, 'adit', 100000.00, 'GoPay', '2025-05-01 04:48:19');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(11) NOT NULL,
  `film_id` int(11) NOT NULL,
  `cinema_id` int(11) NOT NULL,
  `studio_id` int(100) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `available_seats` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `film_id`, `cinema_id`, `studio_id`, `date`, `time`, `available_seats`, `created_at`) VALUES
(412, 1, 101, 3333, '2025-04-29', '13:15:00', 100, '2025-04-28 12:11:18'),
(413, 3, 101, 2222, '2025-04-29', '12:30:00', 100, '2025-04-28 12:22:58'),
(414, 2, 101, 1111, '2025-04-29', '15:35:00', 100, '2025-04-28 12:22:58'),
(415, 5, 101, 1111, '2025-04-29', '16:45:00', 100, '2025-04-28 12:22:58'),
(416, 6, 101, 3333, '2025-04-29', '19:15:00', 100, '2025-04-28 12:26:01'),
(417, 7, 101, 1111, '2025-04-29', '20:30:00', 100, '2025-04-28 16:35:27'),
(418, 6, 101, 3333, '2025-04-30', '15:00:00', 100, '2025-04-29 15:25:25'),
(419, 3, 101, 2222, '2025-04-30', '11:30:00', 100, '2025-04-29 15:31:15'),
(420, 7, 101, 2222, '2025-04-01', '15:30:00', 100, '2025-04-30 16:20:23'),
(421, 8, 101, 3333, '2025-04-01', '11:00:00', 100, '2025-04-30 16:29:20'),
(422, 4, 101, 2222, '2025-04-01', '14:00:00', 100, '2025-05-01 03:53:50');

-- --------------------------------------------------------

--
-- Table structure for table `seats`
--

CREATE TABLE `seats` (
  `id` int(11) NOT NULL,
  `film_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `seat_code` varchar(10) NOT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seats`
--

INSERT INTO `seats` (`id`, `film_id`, `schedule_id`, `seat_code`, `is_available`, `created_at`) VALUES
(4413, 4, 413, 'A1', 1, '2025-04-28 12:13:55'),
(4416, 6, 415, 'A2', 1, '2025-04-29 12:06:55'),
(4425, 6, 418, 'A1', 0, '2025-04-29 15:21:36'),
(4426, 3, 419, 'A1', 0, '2025-04-29 15:31:59'),
(4428, 7, 420, 'A1', 1, '2025-04-30 16:20:52'),
(4429, 8, 421, 'A1', 0, '2025-04-30 16:30:23'),
(4430, 8, 421, 'A2', 1, '2025-05-01 02:52:59'),
(4431, 8, 421, 'A3', 1, '2025-05-01 02:53:08'),
(4432, 1, 412, 'A2', 1, '2025-05-01 02:56:13'),
(4433, 1, 412, 'A3', 1, '2025-05-01 02:56:26'),
(4434, 1, 412, 'A4', 1, '2025-05-01 02:56:33'),
(4435, 1, 412, 'A5', 1, '2025-05-01 02:57:40'),
(4436, 2, 414, 'A1', 1, '2025-05-01 02:59:00'),
(4437, 2, 414, 'A2', 1, '2025-05-01 02:59:08'),
(4438, 2, 414, 'A3', 1, '2025-05-01 02:59:17'),
(4439, 3, 419, 'A2', 1, '2025-05-01 03:01:56'),
(4440, 3, 419, 'A3', 1, '2025-05-01 03:02:05'),
(4441, 3, 419, 'A4', 1, '2025-05-01 03:02:17'),
(4442, 3, 419, 'A5', 1, '2025-05-01 03:02:27'),
(4443, 3, 422, 'A1', 1, '2025-05-01 03:03:38'),
(4463, 4, 422, 'A2', 1, '2025-05-01 03:59:20'),
(4464, 4, 422, 'A3', 1, '2025-05-01 04:00:17'),
(4465, 4, 422, 'A4', 1, '2025-05-01 04:04:29'),
(4466, 4, 422, 'A5', 1, '2025-05-01 04:05:34'),
(4467, 5, 415, 'A1', 1, '2025-05-01 04:07:17'),
(4468, 5, 415, 'A3', 1, '2025-05-01 04:07:39'),
(4469, 5, 415, 'A4', 1, '2025-05-01 04:07:49'),
(4470, 5, 415, 'A5', 1, '2025-05-01 04:07:58'),
(4471, 6, 416, 'A1', 0, '2025-05-01 04:08:31'),
(4472, 6, 416, 'A2', 1, '2025-05-01 04:09:29'),
(4473, 6, 416, 'A3', 1, '2025-05-01 04:09:41'),
(4474, 6, 416, 'A4', 1, '2025-05-01 04:10:13'),
(4475, 6, 416, 'A5', 0, '2025-05-01 04:10:28'),
(4476, 6, 418, 'A2', 1, '2025-05-01 04:11:22'),
(4477, 6, 418, 'A3', 1, '2025-05-01 04:11:31'),
(4478, 6, 418, 'A4', 1, '2025-05-01 04:11:40'),
(4479, 6, 418, 'A5', 1, '2025-05-01 04:11:49'),
(4485, 7, 417, 'A1', 0, '2025-05-01 04:26:27'),
(4486, 7, 417, 'A2', 0, '2025-05-01 04:26:36'),
(4487, 7, 417, 'A3', 1, '2025-05-01 04:26:44'),
(4488, 7, 417, 'A4', 1, '2025-05-01 04:26:51'),
(4489, 7, 417, 'A5', 1, '2025-05-01 04:26:58');

-- --------------------------------------------------------

--
-- Table structure for table `studio`
--

CREATE TABLE `studio` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `capacity` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `studio`
--

INSERT INTO `studio` (`id`, `name`, `capacity`, `created_at`) VALUES
(1111, 'Studio 1', 100, '2025-04-28 09:26:13'),
(2222, 'Studio 2', 100, '2025-04-28 16:04:31'),
(3333, 'Studio 3', 100, '2025-04-28 16:04:31');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'intan1', '$2y$10$DFaGsiGrLMJM26Rw08o8peD9rcoeGRKhzr31/qfu/3UdTx1Tkmxm6', 'user', '2025-04-28 08:53:52'),
(2, 'intan', '$2y$10$Ts8zRBCzgEYsI4CSaQhmPezjYlPozFqRQ1xUvsL7AGNfF3ErNQ9Q2', 'admin', '2025-04-28 12:53:06');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `booked_seats`
--
ALTER TABLE `booked_seats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `film_id` (`film_id`),
  ADD KEY `schedule_id` (`schedule_id`);

--
-- Indexes for table `booking_success`
--
ALTER TABLE `booking_success`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `cinemas`
--
ALTER TABLE `cinemas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_schedule_id` (`schedule_id`);

--
-- Indexes for table `films`
--
ALTER TABLE `films`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `genres`
--
ALTER TABLE `genres`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `film_id` (`film_id`);

--
-- Indexes for table `seats`
--
ALTER TABLE `seats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `film_id` (`film_id`),
  ADD KEY `schedule_id` (`schedule_id`);

--
-- Indexes for table `studio`
--
ALTER TABLE `studio`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `booked_seats`
--
ALTER TABLE `booked_seats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `booking_success`
--
ALTER TABLE `booking_success`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cinemas`
--
ALTER TABLE `cinemas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT for table `films`
--
ALTER TABLE `films`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `genres`
--
ALTER TABLE `genres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5556;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=423;

--
-- AUTO_INCREMENT for table `seats`
--
ALTER TABLE `seats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4490;

--
-- AUTO_INCREMENT for table `studio`
--
ALTER TABLE `studio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3334;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booked_seats`
--
ALTER TABLE `booked_seats`
  ADD CONSTRAINT `booked_seats_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`);

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`film_id`) REFERENCES `films` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `booking_success`
--
ALTER TABLE `booking_success`
  ADD CONSTRAINT `booking_success_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`);

--
-- Constraints for table `cinemas`
--
ALTER TABLE `cinemas`
  ADD CONSTRAINT `fk_schedule_id` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`);

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`film_id`) REFERENCES `films` (`id`);

--
-- Constraints for table `seats`
--
ALTER TABLE `seats`
  ADD CONSTRAINT `seats_ibfk_1` FOREIGN KEY (`film_id`) REFERENCES `films` (`id`),
  ADD CONSTRAINT `seats_ibfk_2` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
cinematix