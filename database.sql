-- DSALINGO MySQL Database Schema & Sample Data Initialization (Language Independent Version)
-- Database: `dsalingo_db`

CREATE DATABASE IF NOT EXISTS `dsalingo_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `dsalingo_db`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `level` INT DEFAULT 1,
  `xp` INT DEFAULT 0,
  `streak` INT DEFAULT 0,
  `hearts` INT DEFAULT 8,
  `crowns` INT DEFAULT 0,
  `is_pro` TINYINT(1) DEFAULT 0,
  `daily_goal` INT DEFAULT 50,
  `join_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Achievements Table
CREATE TABLE IF NOT EXISTS `achievements` (
  `id` VARCHAR(50) PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `icon` VARCHAR(50) NOT NULL, -- emoji
  `xp_reward` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. User Achievements (Associative)
CREATE TABLE IF NOT EXISTS `user_achievements` (
  `user_id` INT NOT NULL,
  `achievement_id` VARCHAR(50) NOT NULL,
  `unlocked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `achievement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Data Structure Categories
CREATE TABLE IF NOT EXISTS `categories` (
  `id` VARCHAR(50) PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `icon` VARCHAR(50) NOT NULL, -- emoji
  `color` VARCHAR(20) NOT NULL, -- hex string (e.g. '0xFF1CB0F6')
  `is_coming_soon` TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Interactive Questions Table (Language Independent)
CREATE TABLE IF NOT EXISTS `questions` (
  `id` VARCHAR(50) PRIMARY KEY,
  `category_id` VARCHAR(50) NOT NULL,
  `type` VARCHAR(30) NOT NULL, -- THEORY, MULTIPLE_CHOICE, CODE_COMPLETION, DRAG_DROP, FILL_BLANK, ARRAY_INTERACTION
  `question` TEXT NOT NULL,
  `options` TEXT, -- JSON array of strings
  `correct_answer` TEXT NOT NULL, -- JSON integer, string, or array
  `explanation` TEXT NOT NULL,
  `code` TEXT,
  `blanks` TEXT, -- JSON array of strings
  `items` TEXT, -- JSON array of strings
  `correct_order` TEXT, -- JSON array of integers
  `array_data` TEXT, -- JSON array of strings
  `image_url` VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Curated Code Challenges (LeetCode / Striver A2Z)
CREATE TABLE IF NOT EXISTS `challenges` (
  `id` VARCHAR(50) PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `difficulty` VARCHAR(20) NOT NULL, -- EASY, MEDIUM, HARD
  `category` VARCHAR(50) NOT NULL,
  `xp_reward` INT DEFAULT 0,
  `time_limit` INT DEFAULT NULL,
  `link` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. User Challenges Progress
CREATE TABLE IF NOT EXISTS `user_challenges` (
  `user_id` INT NOT NULL,
  `challenge_id` VARCHAR(50) NOT NULL,
  `is_completed` TINYINT(1) DEFAULT 1,
  `completed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `challenge_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==========================================
-- INSERT SAMPLE DATA
-- ==========================================

-- Populate standard achievements
INSERT INTO `achievements` (`id`, `title`, `description`, `icon`, `xp_reward`) VALUES
('first_step', 'First Step', 'Earn your first 50 XP in DSALINGO!', '👣', 50),
('streak_3', 'Flame on!', 'Maintain a 3-day learning streak.', '🔥', 100),
('array_master', 'Array Conqueror', 'Complete the entire Array category!', '📊', 200),
('leetcode_slayer', 'LeetCode Slayer', 'Solve your first Striver challenge.', '⚔️', 150),
('perfect_hearts', 'Flawless Learner', 'Complete a lesson without losing any hearts.', '💖', 100);

-- Populate categories
INSERT INTO `categories` (`id`, `title`, `icon`, `color`, `is_coming_soon`) VALUES
('basics', 'DS Basics', '🐍', '0xFF4B8BBE', 0),
('array', 'Array Foundations', '📊', '0xFF1CB0F6', 0),
('string', 'String Secrets', '🔤', '0xFFCE82FF', 0),
('linkedlist', 'Chain Links', '🔗', '0xFF58CC02', 0),
('stack', 'Stack It Up', '📚', '0xFFEA2B2B', 0),
('queue', 'Get in Line', '👥', '0xFFFF9600', 0),
('tree', 'Binary Trees', '🌳', '0xFF00B050', 1);

-- Insert DS Basics Questions (Language Independent)
INSERT INTO `questions` (`id`, `category_id`, `type`, `question`, `options`, `correct_answer`, `explanation`, `code`, `blanks`, `items`, `correct_order`, `array_data`) VALUES
('b1', 'basics', 'MULTIPLE_CHOICE', 'What is the time complexity to access an element in an array if you know the index?', '["O(1)", "O(log N)", "O(N)", "O(N log N)"]', '0', 'Accessing an element in an array by its index takes constant time, which is O(1).', NULL, NULL, NULL, NULL, NULL),
('b2', 'basics', 'FILL_BLANK', 'A list where elements sit side-by-side in contiguous memory is called an ______.', NULL, '"array"', 'An array is stored in contiguous memory slots sitting right next to each other.', NULL, NULL, NULL, NULL, NULL),
('b3', 'basics', 'MULTIPLE_CHOICE', 'Which of these is a LIFO (Last In First Out) data structure?', '["Queue", "Stack", "Array", "Linked List"]', '1', 'A Stack is a Last In First Out (LIFO) data structure.', NULL, NULL, NULL, NULL, NULL),
('b4', 'basics', 'FILL_BLANK', 'A queue is a FIFO data structure. FIFO stands for First In First ______.', NULL, '"Out"', 'FIFO stands for First In First Out.', NULL, NULL, NULL, NULL, NULL),
('b5', 'basics', 'MULTIPLE_CHOICE', 'What is the index of the first element in a standard array?', '["1", "-1", "0", "Any of these"]', '2', 'Standard array indexing is 0-based, so the first element is at index 0.', NULL, NULL, NULL, NULL, NULL);

-- Insert Array Foundations Questions (Language Independent 10-Step Progression)
INSERT INTO `questions` (`id`, `category_id`, `type`, `question`, `options`, `correct_answer`, `explanation`, `code`, `blanks`, `items`, `correct_order`, `array_data`) VALUES
('a1', 'array', 'THEORY', 'What is an Array? 📊', NULL, '""', 'An Array is a contiguous block of memory cells sitting side-by-side. Elements can be directly accessed in O(1) time using their numeric index.', 'int[] scores = {10, 20, 30, 40};', NULL, NULL, NULL, '["10", "20", "30", "40"]'),
('a2', 'array', 'MULTIPLE_CHOICE', 'Array Indexing Rule 🏁: If an array has 4 items [10, 20, 30, 40], what is the index of the first and last elements?', '["First: 1, Last: 4", "First: 0, Last: 3", "First: 0, Last: 4", "First: 1, Last: 3"]', '1', 'Array indexing is 0-based. For N elements, valid indices range from 0 to N-1 (0 to 3).', '// Index 0: 10\n// Index 3: 40', NULL, NULL, NULL, '["10", "20", "30", "40"]'),
('a3', 'array', 'ARRAY_INTERACTION', 'Access Challenge 🎯: Tap the element sitting at index 2 to retrieve its value.', NULL, '["10", "20", "30", "40"]', 'Great job! scores[2] accesses the 3rd element which is 30.', 'int value = scores[2]; // value = 30', NULL, '["30"]', NULL, '["10", "20", "30", "40"]'),
('a4', 'array', 'ARRAY_INTERACTION', 'Update Challenge ✏️: Replace the element at index 1 with 99.', NULL, '["10", "99", "30", "40"]', 'Correct! Updating an element by index overwrites the previous value in O(1) time.', 'scores[1] = 99;\n// array is now [10, 99, 30, 40]', NULL, '["99"]', NULL, '["10", "20", "30", "40"]'),
('a5', 'array', 'ARRAY_INTERACTION', 'Append Challenge ➕: Append 50 to the end of the array.', NULL, '["10", "20", "30", "40", "50"]', 'Awesome! Appending places the new item at the next available index at the end.', 'scores.append(50);\n// array is now [10, 20, 30, 40, 50]', NULL, '["50"]', NULL, '["10", "20", "30", "40"]'),
('a6', 'array', 'ARRAY_INTERACTION', 'Insert Challenge 🏃: Insert 25 at index 2. Notice how elements at and after index 2 shift right!', NULL, '["10", "20", "25", "30", "40"]', 'Well done! Inserting at index 2 requires shifting subsequent elements right, taking O(N) time.', 'scores.insert(2, 25);\n// [10, 20, 25, 30, 40]', NULL, '["25"]', NULL, '["10", "20", "30", "40"]'),
('a7', 'array', 'ARRAY_INTERACTION', 'Delete Challenge ✂️: Delete the element 30 from the array.', NULL, '["10", "20", "40"]', 'Element deleted! The remaining elements shift left to fill the empty slot in O(N) time.', 'scores.remove(2); // deletes 30', NULL, '["30"]', NULL, '["10", "20", "30", "40"]'),
('a8', 'array', 'ARRAY_INTERACTION', 'Search Challenge 🔍: Linear search for target 30 in the array.', NULL, '["10", "20", "30", "40"]', 'Target found at index 2! Linear search scans from index 0 until the target matches in O(N) time.', 'int index = scores.indexOf(30); // returns 2', NULL, '["30"]', NULL, '["10", "20", "30", "40"]'),
('a9', 'array', 'MULTIPLE_CHOICE', 'Time Complexity Challenge ⏱️: What are the average time complexities for Index Access vs Middle Insertion?', '["Access: O(1), Insert: O(N)", "Access: O(N), Insert: O(1)", "Access: O(1), Insert: O(1)", "Access: O(N), Insert: O(N)"]', '0', 'Access by index is instant O(1) through memory offsets. Insertion in the middle requires shifting elements O(N).', NULL, NULL, NULL, NULL, '["10", "20", "30", "40"]'),
('a10', 'array', 'ARRAY_INTERACTION', 'Array Boss Challenge 👑: Transform array [10, 20, 30, 40, 50] by deleting 30, inserting 25 at index 2, and updating 50 to 60!', NULL, '["10", "20", "25", "40", "60"]', 'CONGRATULATIONS! You have mastered Array operations and defeated the Array Boss! 👑 +50 XP and a Crown awarded!', '// Final array after Boss sequence:\n// [10, 20, 25, 40, 60]', NULL, '["25", "60"]', NULL, '["10", "20", "30", "40", "50"]');

-- Curated Code Challenges
INSERT INTO `challenges` (`id`, `title`, `description`, `difficulty`, `category`, `xp_reward`, `time_limit`, `link`) VALUES
('c1', 'Two Sum', 'Given an array of integers nums and an integer target, return indices of the two numbers such that they add up to target.', 'EASY', 'Array', 50, NULL, 'https://leetcode.com/problems/two-sum/'),
('c2', 'Reverse String', 'Write a function that reverses a string. The input string is given as an array of characters s.', 'EASY', 'String', 50, NULL, 'https://leetcode.com/problems/reverse-string/'),
('c3', 'Maximum Subarray (Kadane)', 'Given an integer array nums, find the subarray with the largest sum, and return its sum.', 'MEDIUM', 'Array', 75, NULL, 'https://leetcode.com/problems/maximum-subarray/'),
('c4', 'Container With Most Water', 'Find two lines that together with the x-axis form a container, such that the container contains the most water.', 'MEDIUM', 'Array', 75, NULL, 'https://leetcode.com/problems/container-with-most-water/'),
('c5', 'Merge k Sorted Lists', 'You are given an array of k linked-lists lists, each linked-list is sorted in ascending order. Merge all the linked-lists into one sorted linked-list and return it.', 'HARD', 'LinkedList', 100, NULL, 'https://leetcode.com/problems/merge-k-sorted-lists/'),
('c6', 'Valid Parentheses', 'Given a string s containing just the characters \'(\', \')\', \'{\', \'}\', \'[\' and \']\', determine if the input string is valid.', 'EASY', 'Stack', 50, NULL, 'https://leetcode.com/problems/valid-parentheses/');

-- Mock Users (No preferred language context)
INSERT INTO `users` (`id`, `username`, `email`, `password`, `level`, `xp`, `streak`, `hearts`, `crowns`, `is_pro`, `daily_goal`) VALUES
(1, 'dsa_wizard', 'wizard@dsalingo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 12, 1420, 15, 10, 4, 1, 50),
(2, 'code_ninja', 'ninja@dsalingo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 8, 920, 7, 8, 2, 0, 50),
(3, 'java_junkie', 'java@dsalingo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, 480, 2, 7, 1, 0, 30),
(4, 'cpp_god', 'cpp@dsalingo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 15, 2350, 42, 10, 8, 1, 100),
(5, 'newbie_dev', 'newbie@dsalingo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 120, 1, 8, 0, 0, 50);

-- Unlocked achievements
INSERT INTO `user_achievements` (`user_id`, `achievement_id`) VALUES
(1, 'first_step'),
(1, 'streak_3'),
(1, 'array_master'),
(2, 'first_step'),
(2, 'streak_3'),
(4, 'first_step'),
(4, 'streak_3'),
(4, 'leetcode_slayer');
