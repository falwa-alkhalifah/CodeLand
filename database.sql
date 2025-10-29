-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Oct 27, 2025 at 09:23 PM
-- Server version: 5.7.24
-- PHP Version: 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `database`
--

-- --------------------------------------------------------

--
-- Table structure for table `quiz`
--

CREATE TABLE `quiz` (
  `id` int(11) NOT NULL,
  `educatorID` int(11) DEFAULT NULL,
  `topicID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `quizfeedback`
--

CREATE TABLE `quizfeedback` (
  `id` int(11) NOT NULL,
  `quizID` int(11) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT NULL,
  `comments` text,
  `date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `quizquestion`
--

CREATE TABLE `quizquestion` (
  `id` int(11) NOT NULL,
  `quizID` int(11) DEFAULT NULL,
  `question` text,
  `questionFigureFileName` varchar(100) DEFAULT NULL,
  `answerA` text,
  `answerB` text,
  `answerC` text,
  `answerD` text,
  `correctAnswer` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `recommendedquestion`
--

CREATE TABLE `recommendedquestion` (
  `id` int(11) NOT NULL,
  `quizID` int(11) DEFAULT NULL,
  `learnerID` int(11) DEFAULT NULL,
  `question` text,
  `questionFigureFileName` varchar(100) DEFAULT NULL,
  `answerA` text,
  `answerB` text,
  `answerC` text,
  `answerD` text,
  `correctAnswer` char(1) DEFAULT NULL,
  `status` enum('pending','approved','disapproved') DEFAULT NULL,
  `comments` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `takenquiz`
--

CREATE TABLE `takenquiz` (
  `id` int(11) NOT NULL,
  `quizID` int(11) DEFAULT NULL,
  `score` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `topic`
--

CREATE TABLE `topic` (
  `id` int(11) NOT NULL,
  `topicName` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `firstName` varchar(50) DEFAULT NULL,
  `lastName` varchar(50) DEFAULT NULL,
  `emailAddress` varchar(100) DEFAULT NULL,
  `password` varchar(50) DEFAULT NULL,
  `photoFileName` varchar(100) DEFAULT NULL,
  `userType` enum('educator','learner') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `quiz`
--
ALTER TABLE `quiz`
  ADD PRIMARY KEY (`id`),
  ADD KEY `educatorID` (`educatorID`),
  ADD KEY `topicID` (`topicID`);

--
-- Indexes for table `quizfeedback`
--
ALTER TABLE `quizfeedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quizID` (`quizID`);

--
-- Indexes for table `quizquestion`
--
ALTER TABLE `quizquestion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quizID` (`quizID`);

--
-- Indexes for table `recommendedquestion`
--
ALTER TABLE `recommendedquestion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quizID` (`quizID`),
  ADD KEY `learnerID` (`learnerID`);

--
-- Indexes for table `takenquiz`
--
ALTER TABLE `takenquiz`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quizID` (`quizID`);

--
-- Indexes for table `topic`
--
ALTER TABLE `topic`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `quiz`
--
ALTER TABLE `quiz`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quizfeedback`
--
ALTER TABLE `quizfeedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quizquestion`
--
ALTER TABLE `quizquestion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recommendedquestion`
--
ALTER TABLE `recommendedquestion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `takenquiz`
--
ALTER TABLE `takenquiz`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `topic`
--
ALTER TABLE `topic`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `quiz`
--
ALTER TABLE `quiz`
  ADD CONSTRAINT `quiz_ibfk_1` FOREIGN KEY (`educatorID`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `quiz_ibfk_2` FOREIGN KEY (`topicID`) REFERENCES `topic` (`id`);

--
-- Constraints for table `quizfeedback`
--
ALTER TABLE `quizfeedback`
  ADD CONSTRAINT `quizfeedback_ibfk_1` FOREIGN KEY (`quizID`) REFERENCES `quiz` (`id`);

--
-- Constraints for table `quizquestion`
--
ALTER TABLE `quizquestion`
  ADD CONSTRAINT `quizquestion_ibfk_1` FOREIGN KEY (`quizID`) REFERENCES `quiz` (`id`);

--
-- Constraints for table `recommendedquestion`
--
ALTER TABLE `recommendedquestion`
  ADD CONSTRAINT `recommendedquestion_ibfk_1` FOREIGN KEY (`quizID`) REFERENCES `quiz` (`id`),
  ADD CONSTRAINT `recommendedquestion_ibfk_2` FOREIGN KEY (`learnerID`) REFERENCES `user` (`id`);

--
-- Constraints for table `takenquiz`
--
ALTER TABLE `takenquiz`
  ADD CONSTRAINT `takenquiz_ibfk_1` FOREIGN KEY (`quizID`) REFERENCES `quiz` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
