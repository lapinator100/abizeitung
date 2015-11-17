-- phpMyAdmin SQL Dump
-- version 4.4.12
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 29, 2015 at 07:15 PM
-- Server version: 5.5.44-0+deb7u1
-- PHP Version: 5.4.41-0+deb7u1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `Abizeitung`
--

-- --------------------------------------------------------

--
-- Table structure for table `mottos`
--

CREATE TABLE IF NOT EXISTS `mottos` (
  `id` int(11) NOT NULL,
  `author` int(11) NOT NULL,
  `text` varchar(256) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `mottos`
--

INSERT INTO `mottos` (`id`, `author`, `text`, `timestamp`) VALUES
(4, 3, 'WasABI - Der schärfste Jahrgang aller Zeiten!', '0000-00-00 00:00:00'),
(5, 1, 'Abi leave, I can fly', '0000-00-00 00:00:00'),
(6, 3, 'TrABI - 12 Jahre drauf gewartet', '0000-00-00 00:00:00'),
(23, 3, 'Abit&uuml;r - Raus hier!', '0000-00-00 00:00:00'),
(25, 3, 'CannABIs - Wir haben es durchgezogen!', '0000-00-00 00:00:00'),
(26, 2, 'RehABIlitation', '0000-00-00 00:00:00'),
(27, 1, 'Sparbier-Tour', '0000-00-00 00:00:00'),
(28, 2, 'Baum', '2015-07-26 13:21:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `mottos`
--
ALTER TABLE `mottos`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `mottos`
--
ALTER TABLE `mottos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=29;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
