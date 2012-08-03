-- phpMyAdmin SQL Dump
-- version 3.4.10.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Czas wygenerowania: 03 Sie 2012, 03:54
-- Wersja serwera: 5.1.57
-- Wersja PHP: 5.2.9

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Baza danych: `ig130098_sm`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla  `ci_sessions`
--

CREATE TABLE IF NOT EXISTS `ci_sessions` (
  `session_id` varchar(40) CHARACTER SET latin2 NOT NULL DEFAULT '0',
  `ip_address` varchar(45) CHARACTER SET latin2 NOT NULL DEFAULT '0',
  `user_agent` varchar(120) CHARACTER SET latin2 NOT NULL,
  `last_activity` int(10) unsigned NOT NULL DEFAULT '0',
  `user_data` text CHARACTER SET latin2 NOT NULL,
  PRIMARY KEY (`session_id`),
  KEY `last_activity_idx` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

--
-- Zrzut danych tabeli `ci_sessions`
--

INSERT INTO `ci_sessions` (`session_id`, `ip_address`, `user_agent`, `last_activity`, `user_data`) VALUES
('0458647da37dddc676d3b2d382b2c9ee', '69.171.230.248', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 1343958194, ''),
('0f86a04ab252a88343a8889c0e65f19b', '89.72.136.96', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:12.0) Gecko/20100101 Firefox/12.0 FirePHP/0.7.1', 1343954349, 'a:4:{s:9:"user_data";s:0:"";s:7:"user_id";s:1:"9";s:8:"username";s:5:"cichy";s:6:"status";s:1:"1";}'),
('151bc489d9264a9072afafda8de0a30a', '69.171.230.248', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 1343958193, ''),
('20e3672778d5a277b3434f3e53fed744', '89.72.136.96', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:12.0) Gecko/20100101 Firefox/12.0 FirePHP/0.7.1', 1343950589, 'a:4:{s:9:"user_data";s:0:"";s:7:"user_id";s:1:"9";s:8:"username";s:5:"cichy";s:6:"status";s:1:"1";}'),
('28347bcb814ca9b7147697ac93daa6db', '74.125.19.44', '0', 1343957933, ''),
('99824ee965488b9603f080a8ac52fe86', '89.72.136.96', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:12.0) Gecko/20100101 Firefox/12.0', 1343954112, 'a:4:{s:9:"user_data";s:0:"";s:7:"user_id";s:1:"9";s:8:"username";s:5:"cichy";s:6:"status";s:1:"1";}'),
('a5c907c2e595829368eca41c4a6c31b8', '69.171.242.250', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 1343958184, ''),
('c8dc469b04c9459ba212aba6156d9c4e', '69.171.242.250', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 1343958184, ''),
('e461551457377f6c7323bb1edff58be5', '89.72.136.96', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:12.0) Gecko/20100101 Firefox/12.0', 1343957717, 'a:4:{s:9:"user_data";s:0:"";s:7:"user_id";s:1:"9";s:8:"username";s:5:"cichy";s:6:"status";s:1:"1";}');

-- --------------------------------------------------------

--
-- Struktura tabeli dla  `conversations`
--

CREATE TABLE IF NOT EXISTS `conversations` (
  `conversation_id` int(8) NOT NULL AUTO_INCREMENT,
  `conversation_subject` varchar(128) NOT NULL,
  PRIMARY KEY (`conversation_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin2 AUTO_INCREMENT=5 ;

--
-- Zrzut danych tabeli `conversations`
--

INSERT INTO `conversations` (`conversation_id`, `conversation_subject`) VALUES
(4, 'Testing...');

-- --------------------------------------------------------

--
-- Struktura tabeli dla  `conversations_members`
--

CREATE TABLE IF NOT EXISTS `conversations_members` (
  `conversation_id` int(8) NOT NULL,
  `user_id` int(11) NOT NULL,
  `conversation_last_view` int(10) NOT NULL,
  `conversation_deleted` int(1) NOT NULL,
  UNIQUE KEY `conversation_linking_unique` (`conversation_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin2;

--
-- Zrzut danych tabeli `conversations_members`
--

INSERT INTO `conversations_members` (`conversation_id`, `user_id`, `conversation_last_view`, `conversation_deleted`) VALUES
(4, 9, 1343957717, 0),
(4, 11, 0, 0),
(4, 12, 0, 0);

-- --------------------------------------------------------

--
-- Struktura tabeli dla  `conversations_messages`
--

CREATE TABLE IF NOT EXISTS `conversations_messages` (
  `message_id` int(15) NOT NULL AUTO_INCREMENT,
  `conversation_id` int(8) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message_date` int(10) NOT NULL,
  `message_text` text NOT NULL,
  `message_attachment_id` int(8) NOT NULL,
  PRIMARY KEY (`message_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin2 AUTO_INCREMENT=7 ;

--
-- Zrzut danych tabeli `conversations_messages`
--

INSERT INTO `conversations_messages` (`message_id`, `conversation_id`, `user_id`, `message_date`, `message_text`, `message_attachment_id`) VALUES
(1, 4, 9, 1343932154, 'Super Duper Test', 0),
(2, 4, 9, 1343956065, '''siemanko''', 0),
(3, 4, 9, 1343956180, '''siemanko''', 0),
(4, 4, 9, 1343957310, '''elo?''', 0),
(5, 4, 9, 1343957361, '''\\''OR = 0''', 0),
(6, 4, 9, 1343957717, '''\\''OR = 0''', 0);

-- --------------------------------------------------------

--
-- Struktura tabeli dla  `login_attempts`
--

CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(40) COLLATE utf8_bin NOT NULL,
  `login` varchar(50) COLLATE utf8_bin NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=19 ;

-- --------------------------------------------------------

--
-- Struktura tabeli dla  `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8_bin NOT NULL,
  `password` varchar(255) COLLATE utf8_bin NOT NULL,
  `email` varchar(100) COLLATE utf8_bin NOT NULL,
  `activated` tinyint(1) NOT NULL DEFAULT '1',
  `banned` tinyint(1) NOT NULL DEFAULT '0',
  `ban_reason` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `new_password_key` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  `new_password_requested` datetime DEFAULT NULL,
  `new_email` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `new_email_key` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  `last_ip` varchar(40) COLLATE utf8_bin NOT NULL,
  `last_login` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=13 ;

--
-- Zrzut danych tabeli `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `activated`, `banned`, `ban_reason`, `new_password_key`, `new_password_requested`, `new_email`, `new_email_key`, `last_ip`, `last_login`, `created`, `modified`) VALUES
(9, 'cichy', '$2a$08$ELAXnQRAHSiHe3Sa1VcD6OB5QfdclX9ykWgpcRwzHQC0vI9p.5a/W', 'cichy69@gmail.com', 1, 0, NULL, NULL, NULL, 'm.m.swiderski@gmail.com', '9ba8d1da1631163b6c9f228f233f176f', '89.72.136.96', '2012-08-03 03:28:09', '2012-08-01 06:07:18', '2012-08-03 01:28:09'),
(11, 'cichy2', '$2a$08$zdBFe.gxWYL5lbjnEdnVieCFaCeRIiNY1/nUr0qSZTeTc.PUpifTm', '', 1, 0, NULL, NULL, NULL, NULL, NULL, '', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2012-08-02 18:03:31'),
(12, 'cichy3', '$2a$08$zdBFe.gxWYL5lbjnEdnVieCFaCeRIiNY1/nUr0qSZTeTc.PUpifTm', '', 1, 0, NULL, NULL, NULL, NULL, NULL, '', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2012-08-02 18:03:39');

-- --------------------------------------------------------

--
-- Struktura tabeli dla  `user_autologin`
--

CREATE TABLE IF NOT EXISTS `user_autologin` (
  `key_id` char(32) COLLATE utf8_bin NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `user_agent` varchar(150) COLLATE utf8_bin NOT NULL,
  `last_ip` varchar(40) COLLATE utf8_bin NOT NULL,
  `last_login` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Zrzut danych tabeli `user_autologin`
--

INSERT INTO `user_autologin` (`key_id`, `user_id`, `user_agent`, `last_ip`, `last_login`) VALUES
('6fcdf76305a88b97585c408a03e5e3d3', 9, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:12.0) Gecko/20100101 Firefox/12.0', '89.72.136.96', '2012-08-03 01:28:09');

-- --------------------------------------------------------

--
-- Struktura tabeli dla  `user_profiles`
--

CREATE TABLE IF NOT EXISTS `user_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `country` varchar(20) COLLATE utf8_bin DEFAULT NULL,
  `website` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=6 ;

--
-- Zrzut danych tabeli `user_profiles`
--

INSERT INTO `user_profiles` (`id`, `user_id`, `country`, `website`) VALUES
(1, 6, NULL, NULL),
(2, 7, NULL, NULL),
(3, 8, NULL, NULL),
(4, 9, NULL, NULL),
(5, 10, NULL, NULL);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
