-- phpMyAdmin SQL Dump
-- version 4.8.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 11, 2018 at 08:55 AM
-- Server version: 5.6.25
-- PHP Version: 7.2.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cps_dlvn`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `ID` int(2) UNSIGNED NOT NULL,
  `USER_ID` varchar(20) DEFAULT NULL,
  `PASSWORD` varchar(512) DEFAULT NULL,
  `FULLNAME` varchar(100) DEFAULT NULL,
  `EMAIL` varchar(255) DEFAULT NULL,
  `ROLE` int(1) UNSIGNED DEFAULT NULL,
  `ACTIVE` tinyint(1) DEFAULT '1',
  `PW_CHG_DATE` datetime DEFAULT NULL,
  `PREV_PW1` varchar(512) DEFAULT NULL,
  `PREV_PW2` varchar(512) DEFAULT NULL,
  `PREV_PW3` varchar(512) DEFAULT NULL,
  `PREV_PW4` varchar(512) DEFAULT NULL,
  `PREV_PW5` varchar(512) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`ID`, `USER_ID`, `PASSWORD`, `FULLNAME`, `EMAIL`, `ROLE`, `ACTIVE`, `PW_CHG_DATE`, `PREV_PW1`, `PREV_PW2`, `PREV_PW3`, `PREV_PW4`, `PREV_PW5`) VALUES
(1, 'admin', '$2a$08$ibpNi.hKUNzFxHye70NX0uQgXmWKPTisVDHun803y.O5Dr7.SyYXi', 'administrator', NULL, 8, 1, NULL, NULL, NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `username` (`USER_ID`),
  ADD UNIQUE KEY `EMAIL` (`EMAIL`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `ID` int(2) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
