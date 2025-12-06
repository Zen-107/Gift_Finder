
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `gift_finder`
--

-- --------------------------------------------------------

--
-- Table structure for table `age_ranges`
--

CREATE TABLE `age_ranges` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `min_age` int(11) DEFAULT NULL,
  `max_age` int(11) DEFAULT NULL,
  `display_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `age_ranges`
--

INSERT INTO `age_ranges` (`id`, `name`, `min_age`, `max_age`, `display_name`) VALUES
(1, 'any', 0, 150, 'All Ages'),
(2, '0-3y', 0, 3, '0-3y'),
(3, '4-6y', 4, 6, '4-6y'),
(4, '7-12y', 7, 12, '7-12y'),
(5, '13-18y', 13, 18, '13-18y'),
(6, '19-25y', 19, 25, '19-25y'),
(7, '26-35y', 26, 35, '26-35y'),
(8, '36-50y', 36, 50, '36-50y'),
(9, '50y+', 51, 150, '50y+');

-- --------------------------------------------------------

--
-- Table structure for table `blog_posts`
--

CREATE TABLE `blog_posts` (
  `id` int(11) NOT NULL,
  `title` varchar(190) NOT NULL,
  `slug` varchar(190) NOT NULL,
  `content` mediumtext DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookmarks`
--

CREATE TABLE `bookmarks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `folder_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookmark_folders`
--

CREATE TABLE `bookmark_folders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(190) NOT NULL DEFAULT 'รายการของฉัน',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Table structure for table `budget_options`
--

CREATE TABLE `budget_options` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `min_price` decimal(10,2) DEFAULT 0.00,
  `max_price` decimal(10,2) DEFAULT 999999.99,
  `display_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(190) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

--
-- Table structure for table `genders`
--

CREATE TABLE `genders` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `display_name` varchar(50) NOT NULL DEFAULT '',
  `icon` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Table structure for table `gift_recipients`
--

CREATE TABLE `gift_recipients` (
  `id` int(11) NOT NULL,
  `name` varchar(190) DEFAULT NULL,
  `relationship` varchar(190) DEFAULT NULL,
  `gender` varchar(50) DEFAULT NULL,
  `age_range` varchar(50) DEFAULT NULL,
  `budget` varchar(50) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `interests`
--

CREATE TABLE `interests` (
  `id` int(11) NOT NULL,
  `name` varchar(190) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(190) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_budgets`
--

CREATE TABLE `product_budgets` (
  `product_id` int(11) NOT NULL,
  `budget_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_external_urls`
--

CREATE TABLE `product_external_urls` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `url` varchar(1000) NOT NULL,
  `source_name` varchar(100) DEFAULT NULL,
  `min_price` decimal(10,2) NOT NULL,
  `max_price` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'THB',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `product_interests`
--

CREATE TABLE `product_interests` (
  `product_id` int(11) NOT NULL,
  `interest_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_target_audiences`
--

CREATE TABLE `product_target_audiences` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `relationship_id` int(11) DEFAULT NULL,
  `gender_id` int(11) DEFAULT NULL,
  `age_range_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `relationships`
--

CREATE TABLE `relationships` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `relationships`
--

INSERT INTO `relationships` (`id`, `name`, `display_name`, `icon`) VALUES
(1, 'Friend', 'เพื่อน', NULL),
(2, 'Lover / Partner', 'คู่รัก/คนคุย', NULL),
(3, 'Family', 'ครอบครัว', NULL),
(4, 'Respectful Adults', 'ผู้ใหญ่ที่เคารพ', NULL),
(5, 'self-geft', 'ตัวเอง', NULL),
(6, 'Guests', 'ลูกค้า', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `content` text DEFAULT NULL,
  `author_name` varchar(190) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `name` varchar(190) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `password_hash` varchar(255) NOT NULL DEFAULT '',
  `google_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `age_ranges`
--
ALTER TABLE `age_ranges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `bookmarks`
--
ALTER TABLE `bookmarks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `folder_id` (`folder_id`);

--
-- Indexes for table `bookmark_folders`
--
ALTER TABLE `bookmark_folders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `budget_options`
--
ALTER TABLE `budget_options`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `genders`
--
ALTER TABLE `genders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `gift_recipients`
--
ALTER TABLE `gift_recipients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `interests`
--
ALTER TABLE `interests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_budgets`
--
ALTER TABLE `product_budgets`
  ADD PRIMARY KEY (`product_id`,`budget_id`),
  ADD KEY `budget_id` (`budget_id`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`product_id`,`category_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_external_urls`
--
ALTER TABLE `product_external_urls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_interests`
--
ALTER TABLE `product_interests`
  ADD PRIMARY KEY (`product_id`,`interest_id`),
  ADD KEY `interest_id` (`interest_id`);

--
-- Indexes for table `product_target_audiences`
--
ALTER TABLE `product_target_audiences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product_audience` (`product_id`,`relationship_id`,`gender_id`,`age_range_id`),
  ADD KEY `relationship_id` (`relationship_id`),
  ADD KEY `gender_id` (`gender_id`),
  ADD KEY `age_range_id` (`age_range_id`);

--
-- Indexes for table `relationships`
--
ALTER TABLE `relationships`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `age_ranges`
--
ALTER TABLE `age_ranges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `blog_posts`
--
ALTER TABLE `blog_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookmarks`
--
ALTER TABLE `bookmarks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `bookmark_folders`
--
ALTER TABLE `bookmark_folders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `budget_options`
--
ALTER TABLE `budget_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `genders`
--
ALTER TABLE `genders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `gift_recipients`
--
ALTER TABLE `gift_recipients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `interests`
--
ALTER TABLE `interests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `product_external_urls`
--
ALTER TABLE `product_external_urls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_target_audiences`
--
ALTER TABLE `product_target_audiences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=228;

--
-- AUTO_INCREMENT for table `relationships`
--
ALTER TABLE `relationships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookmarks`
--
ALTER TABLE `bookmarks`
  ADD CONSTRAINT `bookmarks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookmarks_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookmarks_ibfk_3` FOREIGN KEY (`folder_id`) REFERENCES `bookmark_folders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bookmarks_ibfk_4` FOREIGN KEY (`folder_id`) REFERENCES `bookmark_folders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bookmarks_ibfk_5` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookmarks_ibfk_6` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bookmark_folders`
--
ALTER TABLE `bookmark_folders`
  ADD CONSTRAINT `bookmark_folders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookmark_folders_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `gift_recipients`
--
ALTER TABLE `gift_recipients`
  ADD CONSTRAINT `gift_recipients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_budgets`
--
ALTER TABLE `product_budgets`
  ADD CONSTRAINT `product_budgets_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_budgets_ibfk_2` FOREIGN KEY (`budget_id`) REFERENCES `budget_options` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD CONSTRAINT `product_categories_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_external_urls`
--
ALTER TABLE `product_external_urls`
  ADD CONSTRAINT `product_external_urls_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_interests`
--
ALTER TABLE `product_interests`
  ADD CONSTRAINT `product_interests_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_interests_ibfk_2` FOREIGN KEY (`interest_id`) REFERENCES `interests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_target_audiences`
--
ALTER TABLE `product_target_audiences`
  ADD CONSTRAINT `product_target_audiences_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_target_audiences_ibfk_2` FOREIGN KEY (`relationship_id`) REFERENCES `relationships` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_target_audiences_ibfk_3` FOREIGN KEY (`gender_id`) REFERENCES `genders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_target_audiences_ibfk_4` FOREIGN KEY (`age_range_id`) REFERENCES `age_ranges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
