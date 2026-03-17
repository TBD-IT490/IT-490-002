-- MySQL dump 10.13  Distrib 8.0.45, for Linux (x86_64)
--
-- Host: localhost    Database: noetic
-- ------------------------------------------------------
-- Server version	8.0.45-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `book_clubs`
--

DROP TABLE IF EXISTS `book_clubs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `book_clubs` (
  `club_id` int NOT NULL AUTO_INCREMENT,
  `club_name` varchar(100) NOT NULL,
  `group_desc` text,
  `invite_code` varchar(20) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`club_id`),
  UNIQUE KEY `invite_code` (`invite_code`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `book_clubs_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `book_reviews`
--

DROP TABLE IF EXISTS `book_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `book_reviews` (
  `review_id` int NOT NULL AUTO_INCREMENT,
  `book_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `rating` int DEFAULT NULL,
  `review_text` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`review_id`),
  UNIQUE KEY `unique_user_review` (`book_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `book_reviews_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`),
  CONSTRAINT `book_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `book_reviews_chk_1` CHECK ((`rating` between 1 and 5))
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `book_suggestions`
--

DROP TABLE IF EXISTS `book_suggestions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `book_suggestions` (
  `sug_id` int NOT NULL AUTO_INCREMENT,
  `club_id` int NOT NULL,
  `user_id` int NOT NULL,
  `book_id` int NOT NULL,
  `sug_note` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`sug_id`),
  KEY `club_id` (`club_id`),
  KEY `user_id` (`user_id`),
  KEY `book_id` (`book_id`),
  CONSTRAINT `book_suggestions_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `book_clubs` (`club_id`) ON DELETE CASCADE,
  CONSTRAINT `book_suggestions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `book_suggestions_ibfk_3` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `books`
--

DROP TABLE IF EXISTS `books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `books` (
  `book_id` int NOT NULL AUTO_INCREMENT,
  `isbn` varchar(13) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `description` text,
  `cover_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `api_book_id` varchar(50) DEFAULT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `published_year` year DEFAULT NULL,
  `genre` varchar(100) DEFAULT NULL,
  `maturity_rating` varchar(50) DEFAULT NULL,
  `content_version` varchar(50) DEFAULT NULL,
  `pages` int DEFAULT NULL,
  PRIMARY KEY (`book_id`),
  UNIQUE KEY `api_book_id` (`api_book_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1216 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `club_books`
--

DROP TABLE IF EXISTS `club_books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `club_books` (
  `id` int NOT NULL AUTO_INCREMENT,
  `club_id` int DEFAULT NULL,
  `book_id` int DEFAULT NULL,
  `schedule_date` date DEFAULT NULL,
  `status` enum('upcoming','reading','completed') DEFAULT 'upcoming',
  PRIMARY KEY (`id`),
  KEY `club_id` (`club_id`),
  KEY `book_id` (`book_id`),
  CONSTRAINT `club_books_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `book_clubs` (`club_id`),
  CONSTRAINT `club_books_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `club_meetings`
--

DROP TABLE IF EXISTS `club_meetings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `club_meetings` (
  `meeting_id` int NOT NULL AUTO_INCREMENT,
  `club_id` int NOT NULL,
  `book_id` int DEFAULT NULL,
  `event_title` varchar(255) NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time NOT NULL,
  `event_format` varchar(255) NOT NULL,
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`meeting_id`),
  KEY `club_id` (`club_id`),
  CONSTRAINT `club_meetings_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `book_clubs` (`club_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `club_members`
--

DROP TABLE IF EXISTS `club_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `club_members` (
  `id` int NOT NULL AUTO_INCREMENT,
  `club_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `role` enum('admin','member') DEFAULT 'admin',
  `joined_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_membership` (`club_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `club_members_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `book_clubs` (`club_id`),
  CONSTRAINT `club_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `discussion_replies`
--

DROP TABLE IF EXISTS `discussion_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `discussion_replies` (
  `reply_id` int NOT NULL AUTO_INCREMENT,
  `discussion_id` int NOT NULL,
  `club_id` int NOT NULL,
  `user_id` int NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`reply_id`),
  KEY `discussion_id` (`discussion_id`),
  KEY `user_id` (`user_id`),
  KEY `disc_reply_clubs_fk` (`club_id`),
  CONSTRAINT `disc_reply_clubs_fk` FOREIGN KEY (`club_id`) REFERENCES `book_clubs` (`club_id`) ON DELETE CASCADE,
  CONSTRAINT `discussion_replies_ibfk_1` FOREIGN KEY (`discussion_id`) REFERENCES `discussions` (`discussion_id`),
  CONSTRAINT `discussion_replies_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `discussions`
--

DROP TABLE IF EXISTS `discussions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `discussions` (
  `discussion_id` int NOT NULL AUTO_INCREMENT,
  `discussion_name` varchar(100) NOT NULL,
  `club_book_id` int DEFAULT NULL,
  `club_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `post_text` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`discussion_id`),
  KEY `club_book_id` (`club_book_id`),
  KEY `user_id` (`user_id`),
  KEY `disc_clubs_fk` (`club_id`),
  CONSTRAINT `disc_clubs_fk` FOREIGN KEY (`club_id`) REFERENCES `book_clubs` (`club_id`) ON DELETE CASCADE,
  CONSTRAINT `discussions_ibfk_1` FOREIGN KEY (`club_book_id`) REFERENCES `club_books` (`id`),
  CONSTRAINT `discussions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `session_key` varchar(64) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_key` (`session_key`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=138 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-17 21:49:09
