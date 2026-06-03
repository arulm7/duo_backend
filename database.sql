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
  `array_data` TEXT -- JSON array of strings
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

-- Insert Array Foundations Questions (Language Independent)
INSERT INTO `questions` (`id`, `category_id`, `type`, `question`, `options`, `correct_answer`, `explanation`, `code`, `blanks`, `items`, `correct_order`, `array_data`) VALUES
('a1', 'array', 'THEORY', 'What is an Array? 🎬', NULL, '""', 'Imagine the movie theater seats inside your phone. An Array is a reserved block of seats sitting side-by-side (contiguous memory).', '// Declaring a simple array\nString[] cast = {"Tom Cruise", "Zendaya"};', NULL, NULL, NULL, NULL),
('a2', 'array', 'THEORY', 'The Indexing Rule! 🏁', NULL, '""', 'We don\'t start counting at 1. We start at 0! Index 0 is the first seat, Index 1 is the second, and so on.', '// Tom is at index 0\n// Zendaya is at index 1', NULL, NULL, NULL, NULL),
('a3', 'array', 'ARRAY_INTERACTION', 'Zendaya wants the Lead Actor seat (Index 0). Drag her there!', NULL, '["Zendaya", "Tom Cruise"]', 'Perfect! In Arrays, the first position is always index 0.', 'System.out.println(cast[0]); // Outputs: Zendaya', NULL, '["Zendaya"]', NULL, '["(empty slot)", "Tom Cruise"]'),
('a4', 'array', 'MULTIPLE_CHOICE', 'If an array has 5 elements, what is the index of the very last element?', '["5", "4", "0", "1"]', '1', 'Correct! Since indexing is 0-based, the 5th element sits at index 4 (N-1).', NULL, NULL, NULL, NULL, NULL),
('a5', 'array', 'THEORY', 'The Swap! 🏗️', NULL, '""', 'You can replace an element at any time by assigning a new value to its index. The old value is overwritten!', 'cast[0] = "Spider-Man";\n// Zendaya is replaced by Spider-Man', NULL, NULL, NULL, NULL),
('a6', 'array', 'ARRAY_INTERACTION', 'Replace \'Tom Cruise\' with \'Robert\' at Index 1.', NULL, '["Zendaya", "Robert"]', 'Update complete! You\'ve modified the array content at index 1.', 'cast[1] = "Robert";', NULL, '["Robert"]', NULL, '["Zendaya", "Tom Cruise"]'),
('a7', 'array', 'THEORY', 'Appending Elements! ✨', NULL, '""', 'Appending adds an element to the very END of the list. It is fast and runs in O(1) time.', 'list.append("The Rock");\n// ["Zendaya", "Robert", "The Rock"]', NULL, NULL, NULL, NULL),
('a8', 'array', 'ARRAY_INTERACTION', 'Add \'The Rock\' to the end of the film strip.', NULL, '["Zendaya", "Robert", "The Rock"]', 'Great! Appending always targets the last available position.', 'list.append("The Rock");', NULL, '["The Rock"]', NULL, '["Zendaya", "Robert"]'),
('a9', 'array', 'THEORY', 'The Big Squeeze! 🏃‍♂️', NULL, '""', 'Inserting in the middle is "expensive" O(N). Everyone to the right has to shift over by one slot to make room!', 'list.insert(1, "Spider-Man");\n// Everyone from index 1 shifts right!', NULL, NULL, NULL, NULL),
('a10', 'array', 'ARRAY_INTERACTION', 'Squeeze \'Spider-Man\' between \'Zendaya\' and \'Robert\'.', NULL, '["Zendaya", "Spider-Man", "Robert"]', 'Shifting was required! You can see why inserting in a large array takes more time.', 'list.insert(1, "Spider-Man");', NULL, '["Spider-Man"]', NULL, '["Zendaya", "Robert"]'),
('a11', 'array', 'THEORY', 'Popping Elements! ✂️', NULL, '""', 'Popping removes the last element from the list. It takes O(1) time because no elements need to be shifted.', 'list.pop(); // Removes "The Rock"', NULL, NULL, NULL, NULL),
('a12', 'array', 'ARRAY_INTERACTION', 'Remove the \'Blooper\' from the end.', NULL, '["Action", "Drama"]', 'Scene deleted! pop() kept the core movie safe.', 'list.pop();', NULL, '["Blooper"]', NULL, '["Action", "Drama", "Blooper"]'),
('a13', 'array', 'THEORY', 'Sorting the Array! 🏆', NULL, '""', 'Sorting organizes your array elements in ascending order, which takes O(N log N) time.', 'ratings.sort(); // [2, 5, 8]', NULL, NULL, NULL, NULL),
('a14', 'array', 'ARRAY_INTERACTION', 'Sort these alphabetically: Avengers, Batman, Cars.', NULL, '["Avengers", "Batman", "Cars"]', 'Perfectly sorted! Your library is now organized.', 'movies.sort();', NULL, '["Batman", "Cars", "Avengers"]', NULL, '["(empty slot)", "(empty slot)", "(empty slot)"]');

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
