-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 03, 2025 at 11:03 AM
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
-- Database: `hc+ja`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `dob` date NOT NULL,
  `phone` varchar(15) NOT NULL,
  `email` varchar(255) NOT NULL,
  `street` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zip` varchar(15) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `confirm_password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`user_id`, `full_name`, `gender`, `dob`, `phone`, `email`, `street`, `city`, `state`, `zip`, `country`, `username`, `password`, `confirm_password`, `created_at`) VALUES
(1, 'Admin', 'Other', '2000-09-07', '09218381723', 'example@gmail.com', '1234 pogi street', 'Metro Manila', 'Manila', '1234', 'Philippines', 'Admin', '$2y$10$j0q0WDGgXSGDS2GMcdZ.ueWE1BPawyHjQQ6Xt10O26tVFnGwLcqdG', '', '2025-07-03 07:44:11'),
(2, 'AdminJ', 'Other', '2000-09-07', '09123456789', 'example@domain.com', '1234 pogi street', 'Metro Manila', 'Manila', '4321', 'Philippines', 'Admin1', '$2y$10$pHOrJquu04sIvR7ngYQcMen9O6WId9NSRg9GrNXabWWdn3F7xbvD2', '', '2025-07-03 07:46:48');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `cart_quantity` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cart_id`, `session_id`, `cart_quantity`, `user_id`, `product_id`) VALUES
(4, 'k8i1ad60hnqero33c6c7mrj32e', 2, NULL, 23),
(17, 'tm832t2ecidksb2b25ubd4qj9u', 1, NULL, 16);

-- --------------------------------------------------------

--
-- Table structure for table `cart_products`
--

CREATE TABLE `cart_products` (
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `checkout`
--

CREATE TABLE `checkout` (
  `checkout_id` int(11) NOT NULL,
  `cart_id` int(11) DEFAULT NULL,
  `checkout_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `shipping_address` text NOT NULL,
  `billing_address` text NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `total_amount`, `payment_method`, `shipping_address`, `billing_address`, `order_date`, `status`) VALUES
(1, 1, 420.00, 'cod', '1234 pogi street, Metro Manila, Manila 1234, Philippines', '1234 pogi street, Metro Manila, Manila 1234, Philippines', '2025-07-03 08:05:24', 'pending'),
(2, 1, 420.00, 'cod', '1234 pogi street, Metro Manila, Manila 1234, Philippines', '1234 pogi street, Metro Manila, Manila 1234, Philippines', '2025-07-03 08:05:37', 'pending'),
(3, 1, 474.00, 'card', '1234 pogi street, Metro Manila, Manila 1234, Philippines', '1234 pogi street, Metro Manila, Manila 1234, Philippines', '2025-07-03 08:13:31', 'pending'),
(4, 1, 441.60, 'gcash', '1234 pogi street, Metro Manila, Manila 1234, Philippines', '1234 pogi street, Metro Manila, Manila 1234, Philippines', '2025-07-03 08:14:03', 'pending'),
(5, 1, 452.40, 'card', '1234 pogi street, Metro Manila, Manila 1234, Philippines', '1234 pogi street, Metro Manila, Manila 1234, Philippines', '2025-07-03 08:14:33', 'pending'),
(6, 1, 474.00, 'gcash', '1234 pogi street, Metro Manila, Manila 1234, Philippines', '1234 pogi street, Metro Manila, Manila 1234, Philippines', '2025-07-03 08:15:16', 'pending'),
(7, 1, 830.40, 'card', '1234 pogi street, Metro Manila, Manila 1234, Philippines', '1234 pogi street, Metro Manila, Manila 1234, Philippines', '2025-07-03 08:31:37', 'pending'),
(8, 1, 1035.60, 'gcash', '1234 pogi street, Metro Manila, Manila 1234, Philippines', '1234 pogi street, Metro Manila, Manila 1234, Philippines', '2025-07-03 08:58:20', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`item_id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 23, 1, 250.00),
(2, 2, 23, 1, 250.00),
(3, 3, 6, 1, 300.00),
(4, 4, 20, 1, 270.00),
(5, 5, 8, 1, 280.00),
(6, 6, 6, 1, 300.00),
(7, 7, 6, 1, 300.00),
(8, 7, 19, 1, 330.00),
(9, 8, 6, 1, 300.00),
(10, 8, 23, 1, 250.00),
(11, 8, 20, 1, 270.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `checkout_id` int(11) DEFAULT NULL,
  `payment_method` enum('Credit Card','PayPal','Bank Transfer','Cash on Delivery') NOT NULL,
  `payment_amount` decimal(10,2) NOT NULL,
  `payment_status` enum('Pending','Completed','Failed') NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 0 and 100),
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `product_price`, `quantity`, `rating`, `image`) VALUES
(6, 'Athletic Hoodie', 300.00, 20, 85, 'Athletic'),
(7, 'Black T-shirt', 350.00, 20, 90, 'Black'),
(8, 'Brown T-shirt', 280.00, 20, 75, 'brown'),
(9, 'Carbon-Grey T-shirt', 400.00, 20, 80, 'Carbon-Grey'),
(10, 'Cold-White12 T-shirt', 450.00, 20, 95, 'Cold-White12'),
(11, 'Cosmic-Latte T-shirt', 330.00, 20, 78, 'Cosmic-Latte'),
(12, 'Crewneck Sweatshirt', 380.00, 20, 88, 'Crewneck'),
(13, 'Flint Sweatshirt', 310.00, 20, 82, 'Flint'),
(14, 'Forest-Green T-shirt', 325.00, 20, 70, 'Forest-Green'),
(15, 'GreyZip Halfzip Jacket', 370.00, 20, 85, 'GreyZip'),
(16, 'HalfzipBlack Jacket', 360.00, 20, 79, 'HalfzipBlack'),
(17, 'MelangeGrey Halfzip Jacket', 340.00, 20, 92, 'MelangeGrey'),
(18, 'Oyster Necklace', 400.00, 20, 84, 'OYSTER'),
(19, 'MelangeGrey Sweatpants', 330.00, 20, 76, 'SMelangeGrey'),
(20, 'Black Sweatshorts', 270.00, 20, 70, 'SHORTS-BLACK'),
(21, 'Stag Ring', 420.00, 20, 87, 'Stag'),
(22, 'Black Sweatpants', 390.00, 20, 91, 'Sweatpant'),
(23, 'TankTop', 250.00, 20, 80, 'TankTop'),
(24, 'Black FullZip Hoodie', 460.00, 20, 94, 'ZipHoodie');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cart_products`
--
ALTER TABLE `cart_products`
  ADD PRIMARY KEY (`cart_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `checkout`
--
ALTER TABLE `checkout`
  ADD PRIMARY KEY (`checkout_id`),
  ADD KEY `cart_id` (`cart_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `checkout_id` (`checkout_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `checkout`
--
ALTER TABLE `checkout`
  MODIFY `checkout_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `accounts` (`user_id`);

--
-- Constraints for table `cart_products`
--
ALTER TABLE `cart_products`
  ADD CONSTRAINT `cart_products_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`cart_id`),
  ADD CONSTRAINT `cart_products_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `checkout`
--
ALTER TABLE `checkout`
  ADD CONSTRAINT `checkout_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`cart_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`checkout_id`) REFERENCES `checkout` (`checkout_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
