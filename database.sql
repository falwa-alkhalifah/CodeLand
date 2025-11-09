-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 09, 2025 at 07:17 PM
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

--
-- Dumping data for table `quiz`
--

INSERT INTO `quiz` (`id`, `educatorID`, `topicID`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 3, 3);

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

--
-- Dumping data for table `quizfeedback`
--

INSERT INTO `quizfeedback` (`id`, `quizID`, `rating`, `comments`, `date`) VALUES
(1, 1, '5.0', 'Loved it! really benifical', '2025-11-01'),
(2, 2, '4.5', 'please add easier questions for begginers', '2025-11-04'),
(3, 1, '3.5', NULL, '2025-11-07');

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
  `correctAnswer` enum('A','B','C','D') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `quizquestion`
--

INSERT INTO `quizquestion` (`id`, `quizID`, `question`, `questionFigureFileName`, `answerA`, `answerB`, `answerC`, `answerD`, `correctAnswer`) VALUES
(1, 1, 'Which HTML tag is used to create a hyperlink?', NULL, '<a>', '<link>', '<href>', '<url>', 'A'),
(2, 1, 'What does HTML stand for?', NULL, 'Hyperlinks and Text Markup Language', 'Home Tool Markup Language', 'Hyper Text Markup Language', 'Hyper Tool Markup Language', 'C'),
(3, 1, 'Which tag is used to display the largest heading?', NULL, '<h6>', '<h1>', '<title>', '<head>', 'B'),
(4, 1, 'The <br> tag is used to insert a ...', NULL, 'paragraph', 'line break', 'horizontal rule', 'new section', 'B'),
(5, 1, 'Which attribute specifies alternative text for an image?', NULL, 'title', 'description', 'caption', 'alt', 'D'),
(6, 2, 'Which property is used to change the text color of an element?', NULL, 'font-color', 'text-style', 'color', 'text-color', 'C'),
(7, 2, 'How do you select an element with the id \"header\" in CSS?', NULL, '.header', 'header', '#header', '*header', 'C'),
(8, 2, 'Which property is used to change the background color?', NULL, 'background-style', 'background-color', 'bg-color', 'color-background', 'B'),
(9, 2, 'What does the display: none; property do?', NULL, 'Hides the element, but it still takes up space', 'Deletes the element from the HTML', 'Hides the element, and it does not take up space', 'Changes the element to inline', 'C'),
(10, 2, 'Which CSS property controls the size of text?', NULL, 'text-size', 'font-style', 'font-size', 'text-style', 'C'),
(11, 2, 'How do you apply a style to all <p> elements inside a <div>?', NULL, 'p div { }', 'div p { }', 'div.p { }', 'p > div { }', 'B'),
(12, 2, 'Which CSS property should be used to create the dotted border shown in the image?', 'CSS-Q.png', 'border-line: dotted;', 'border: dashed;', 'border-style: dotted;', 'outline-style: dotted;', 'C'),
(13, 3, 'Which keyword is used to define a function in Python?', NULL, 'func\r\n', 'define', 'def', 'function', 'C'),
(14, 3, 'What is the output of print(type(5)) in Python?', NULL, '<class \'int\'>', '<type \'int\'>', 'integer', 'number', 'A'),
(15, 3, 'Which of the following is used to create a comment in Python?', NULL, '// comment', '# comment', '/* comment */', '<!-- comment -->', 'B'),
(16, 3, 'Which of the following data types is immutable in Python?', NULL, 'List', 'Dictionary', 'Tuple', 'Set', 'C'),
(17, 3, 'Which operator is used for exponentiation in Python?', NULL, '^', '**', 'exp()', '//', 'B'),
(18, 3, 'What will be the output of len(\"Hello World\")?', NULL, '10', '11', '12', '9', 'B');

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
  `correctAnswer` enum('A','B','C','D') NOT NULL,
  `status` enum('pending','approved','disapproved') DEFAULT NULL,
  `comments` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `recommendedquestion`
--

INSERT INTO `recommendedquestion` (`id`, `quizID`, `learnerID`, `question`, `questionFigureFileName`, `answerA`, `answerB`, `answerC`, `answerD`, `correctAnswer`, `status`, `comments`) VALUES
(1, 1, 2, 'Which tag is used to create an unordered list?', NULL, '<ul>', '<ol>', '<list>', '<li>', 'A', 'pending', 'I think it is a good question'),
(2, 1, 2, 'Which attribute is used to define inline CSS styles in HTML?', NULL, 'class', 'style', 'id', 'css', 'B', 'approved', NULL),
(3, 2, 2, 'Which CSS property is used to hide an element without removing it from the document layout?', NULL, 'visibility: hidden;', 'display: none;', 'opacity: 0;', 'hidden: true;', 'A', 'disapproved', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `takenquiz`
--

CREATE TABLE `takenquiz` (
  `id` int(11) NOT NULL,
  `quizID` int(11) DEFAULT NULL,
  `score` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `takenquiz`
--

INSERT INTO `takenquiz` (`id`, `quizID`, `score`) VALUES
(1, 1, 5),
(2, 2, 2),
(3, 1, 4);

-- --------------------------------------------------------

--
-- Table structure for table `topic`
--

CREATE TABLE `topic` (
  `id` int(11) NOT NULL,
  `topicName` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `topic`
--

INSERT INTO `topic` (`id`, `topicName`) VALUES
(1, 'HTML'),
(2, 'CSS'),
(3, 'Python'),
(4, 'Java');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `firstName` varchar(50) DEFAULT NULL,
  `lastName` varchar(50) DEFAULT NULL,
  `emailAddress` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `photoFileName` varchar(100) DEFAULT NULL,
  `userType` enum('educator','learner') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `firstName`, `lastName`, `emailAddress`, `password`, `photoFileName`, `userType`) VALUES
(1, 'Amal', 'Ahmed', 'hussah.x1@gmail.com', '$2y$10$LgnTkKHlSPIgG1377Pkciuy5rPZ3WljAZ9j4OfzBgweaj81ejkvMm', 'default_user.png', 'educator'),
(2, 'Sarah', 'Ali', 'xhussahx@outlook.com', '$2y$10$SYzzACcQPZktBTbHA07RReZ.iK4PBRdPb83NFmsEjoz9a3liWhjCm', 'default_user.png', 'learner'),
(3, 'Norah', 'Saud', 'norah@gmail.com', '$2y$10$4hflAXnT9/rj2t9dnEBSIexYlpQ3i1VGYLEJtlFnZXFUoGIhvCso6', 'u_1762714268_7d279727.jpg', 'educator');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `quizfeedback`
--
ALTER TABLE `quizfeedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `quizquestion`
--
ALTER TABLE `quizquestion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `recommendedquestion`
--
ALTER TABLE `recommendedquestion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `takenquiz`
--
ALTER TABLE `takenquiz`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `topic`
--
ALTER TABLE `topic`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
