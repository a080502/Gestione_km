-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Set 26, 2025 alle 22:51
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `chilometri`
--
CREATE DATABASE IF NOT EXISTS `chilometri` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `chilometri`;

-- --------------------------------------------------------

--
-- Struttura della tabella `chilometri`
--

DROP TABLE IF EXISTS `chilometri`;
CREATE TABLE `chilometri` (
  `id` int(11) NOT NULL,
  `data` date NOT NULL,
  `chilometri_iniziali` int(11) NOT NULL,
  `chilometri_finali` float NOT NULL,
  `note` text DEFAULT NULL,
  `litri_carburante` varchar(255) DEFAULT NULL,
  `euro_spesi` decimal(10,2) DEFAULT NULL,
  `percorso_cedolino` varchar(255) DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `targa_mezzo` varchar(255) NOT NULL,
  `divisione` varchar(255) NOT NULL,
  `filiale` varchar(255) NOT NULL,
  `livello` int(1) NOT NULL,
  `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `chilometri`
--

INSERT INTO `chilometri` (`id`, `data`, `chilometri_iniziali`, `chilometri_finali`, `note`, `litri_carburante`, `euro_spesi`, `percorso_cedolino`, `username`, `targa_mezzo`, `divisione`, `filiale`, `livello`, `timestamp`) VALUES
(13, '2024-12-29', 12000, 24000, '', '1.00', 1.00, NULL, 'broll', 'LB000LB', 'SP09', 'TRENTO STORAGE', 3, '2025-03-26 22:45:35'),
(17, '2024-07-23', 1, 12000, NULL, '1000.00', 550.00, NULL, 'broll', 'LB000LB', 'SP09', 'TRENTO STORAGE', 3, '2025-03-26 22:45:35'),
(18, '2025-03-23', 24000, 44000, NULL, '1212.00', 1212.00, NULL, 'broll', 'LB000LB', 'SP09', 'TRENTO STORAGE', 3, '2025-03-26 22:45:35'),
(33, '2025-03-30', 0, 100, NULL, '50', 500.00, NULL, 'test1', 'TI000TI', 'SP10', 'BORGO LARES', 3, '2025-03-30 11:57:39'),
(34, '2025-03-30', 100, 200, NULL, '522', 522.00, NULL, 'test1', 'TI000TI', 'SP10', 'BORGO LARES', 3, '2025-03-30 11:57:56'),
(35, '2025-03-30', 200, 400, NULL, '50', 50.00, NULL, 'test1', 'TI000TI', 'SP10', 'BORGO LARES', 3, '2025-03-30 11:58:06'),
(36, '2024-02-01', 5, 5000, NULL, '1', 1.00, NULL, 'test1', 'TI000TI', 'SP10', 'BORGO LARES', 3, '2025-03-30 12:00:59'),
(37, '2025-02-25', 5000, 5001, NULL, '55', 55.00, NULL, 'test1', 'TI000TI', 'SP10', 'BORGO LARES', 3, '2025-03-31 11:25:58'),
(38, '2025-05-31', 5001, 5006, NULL, '25', 25.00, NULL, 'test1', 'TI000TI', 'SP10', 'BORGO LARES', 3, '2025-03-31 11:26:11'),
(39, '2025-06-25', 5006, 5008, NULL, '22', 22.00, NULL, 'test1', 'TI000TI', 'SP10', 'BORGO LARES', 3, '2025-03-31 11:26:27'),
(44, '2025-03-31', 43899, 44555, '', '48.84', 81.03, NULL, 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE', 1, '2025-04-01 09:11:06'),
(46, '2025-03-13', 42587, 43243, '', '49.35', 82.76, NULL, 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE', 1, '2025-04-01 09:12:32'),
(47, '2025-03-20', 43243, 43899, '', '48.72', 82.29, NULL, 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE', 1, '2025-04-01 09:13:10'),
(50, '2025-03-04', 41931, 42587, '', '47.75', 82.56, NULL, 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE', 3, '2025-04-01 10:23:47'),
(59, '2025-04-07', 44553, 45110, 'AD BLUE', '8.62', 9.40, 'uploads/cedolini/cedolino_denis_1745398951.jpg', 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE', 3, '2025-04-23 11:02:31'),
(60, '2025-04-07', 45110, 45110, NULL, '45.26', 75.00, 'uploads/cedolini/cedolino_denis_1745398989.jpg', 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE', 3, '2025-04-23 11:03:09'),
(61, '2025-04-11', 45110, 45876, '', '47.99', 74.82, 'uploads/cedolini/cedolino_denis_1745399045.jpg', 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE', 3, '2025-04-23 11:04:05'),
(65, '2024-03-31', 0, 1050, NULL, '0', 0.00, NULL, 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE', 0, '2025-04-23 12:16:48'),
(66, '2024-04-30', 1050, 5570, NULL, '0', 0.00, NULL, 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE', 0, '2025-04-23 12:17:24'),
(67, '2024-05-31', 5570, 9050, NULL, '0', 0.00, NULL, 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE', 0, '2025-04-23 12:17:53'),
(68, '2024-06-30', 9050, 13270, NULL, '0', 0.00, NULL, 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE', 0, '2025-04-23 12:18:31'),
(69, '2024-07-31', 13270, 16985, NULL, '0', 0.00, NULL, 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE', 0, '2025-04-23 12:18:59'),
(70, '2024-08-31', 16985, 20700, NULL, '0', 0.00, NULL, 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE', 0, '2025-04-23 12:19:25'),
(71, '2024-09-30', 20700, 24600, NULL, '0', 0.00, NULL, 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE', 0, '2025-04-23 12:19:49'),
(72, '2024-10-31', 24600, 29170, NULL, '0', 0.00, NULL, 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE', 0, '2025-04-23 12:20:14'),
(73, '2024-11-30', 29170, 33740, NULL, '0', 0.00, NULL, 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE', 0, '2025-04-23 12:20:36'),
(74, '2024-12-31', 33740, 37000, NULL, '0', 0.00, NULL, 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE', 0, '2025-04-23 12:21:10'),
(75, '2025-01-31', 37000, 39309, NULL, '140.66', 245.00, NULL, 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE', 0, '2025-04-23 12:30:00'),
(76, '2025-02-28', 39309, 41931, '', '156', 250.00, NULL, 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE', 0, '2025-04-23 12:36:52'),
(77, '2025-04-16', 45876, 46583, NULL, '43.78', 70.00, 'uploads/cedolini/cedolino_denis_1745405802.jpg', 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE', 0, '2025-04-23 12:56:42'),
(78, '2025-04-24', 46583, 47299, NULL, '44.26', 72.01, 'uploads/cedolini/cedolino_denis_1746427430.jpg', 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE', 0, '2025-05-05 08:43:50'),
(79, '2025-04-30', 47299, 48326, NULL, '0', 0.00, NULL, 'DENIS', 'GS495ZE', 'SP09', 'TRENTO STORAGE', 0, '2025-05-05 08:53:44'),
(80, '2025-05-05', 48326, 48327, NULL, '47.45', 75.78, 'uploads/cedolini/cedolino_denis_1749018429.jpg', 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE', 0, '2025-06-04 08:27:09'),
(81, '2025-05-13', 48327, 48328, 'ADBLUE', '9.01', 9.82, 'uploads/cedolini/cedolino_denis_1749018520.jpg', 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE', 0, '2025-06-04 08:28:40'),
(82, '2025-05-14', 48328, 49100, NULL, '62.27', 85.14, 'uploads/cedolini/cedolino_denis_1749018590.jpg', 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE', 0, '2025-06-04 08:29:50'),
(83, '2025-05-18', 49100, 49101, NULL, '47.98', 83.92, 'uploads/cedolini/cedolino_denis_1749018673.jpg', 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE', 0, '2025-06-04 08:31:13'),
(84, '2025-05-23', 49101, 50647, NULL, '48.52', 80.01, 'uploads/cedolini/cedolino_denis_1749018710.jpg', 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE', 0, '2025-06-04 08:31:50'),
(85, '2025-05-30', 50647, 51337, NULL, '43.02', 70.08, 'uploads/cedolini/cedolino_denis_1749018758.jpg', 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE', 0, '2025-06-04 08:32:38'),
(86, '2025-08-31', 51337, 60982, NULL, '1', 1.00, NULL, 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE', 0, '2025-09-02 20:32:32');

-- --------------------------------------------------------

--
-- Struttura della tabella `costo_extra`
--

DROP TABLE IF EXISTS `costo_extra`;
CREATE TABLE `costo_extra` (
  `id` int(11) NOT NULL,
  `targa_mezzo` varchar(255) NOT NULL,
  `costo` decimal(10,2) DEFAULT NULL,
  `time_stamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `costo_extra`
--

INSERT INTO `costo_extra` (`id`, `targa_mezzo`, `costo`, `time_stamp`) VALUES
(1, 'GS495ZE', 0.69, '2025-03-31 23:22:35'),
(2, 'LB000LB', 0.30, '2025-03-31 23:22:35'),
(3, 'TI000TI', 0.50, '2025-03-31 23:22:35'),
(6, 'PC000PC', 0.70, '2025-03-31 23:22:35'),
(7, 'LO000LO', 0.66, '2025-04-02 16:42:08'),
(8, '*', 0.00, '2025-04-17 11:45:41'),
(9, '', 0.00, '2025-04-17 11:46:30'),
(19, 'AA785AA', 0.99, '2025-06-04 21:43:46');

-- --------------------------------------------------------

--
-- Struttura della tabella `filiali`
--

DROP TABLE IF EXISTS `filiali`;
CREATE TABLE `filiali` (
  `id` int(255) NOT NULL,
  `divisione` varchar(255) DEFAULT NULL,
  `filiale` varchar(255) DEFAULT NULL,
  `time_stamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `filiali`
--

INSERT INTO `filiali` (`id`, `divisione`, `filiale`, `time_stamp`) VALUES
(1, 'SP09', 'TRENTO STORAGE', '2025-03-26 22:52:45'),
(2, 'SP10', 'BORGO LARES', '2025-03-26 22:59:16'),
(3, 'SP08', 'EXPRESS TRENTO', '2025-03-26 22:59:27'),
(4, 'SP01', 'PIACENZA-SEDE', '2025-03-27 23:16:46'),
(5, 'SP07', 'CALMASINO', '2025-03-27 23:17:32');

-- --------------------------------------------------------

--
-- Struttura della tabella `livelli_autorizzazione`
--

DROP TABLE IF EXISTS `livelli_autorizzazione`;
CREATE TABLE `livelli_autorizzazione` (
  `id` int(255) NOT NULL,
  `livello` varchar(255) NOT NULL,
  `descrizione_livello` varchar(255) NOT NULL,
  `_libero` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `livelli_autorizzazione`
--

INSERT INTO `livelli_autorizzazione` (`id`, `livello`, `descrizione_livello`, `_libero`) VALUES
(1, '1', 'Gruppo Admin', ''),
(2, '2', 'Gruppo Responsabile', ''),
(3, '3', 'Gruppo Utente', '');

-- --------------------------------------------------------

--
-- Struttura della tabella `target_annuale`
--

DROP TABLE IF EXISTS `target_annuale`;
CREATE TABLE `target_annuale` (
  `id` int(11) NOT NULL,
  `anno` int(11) NOT NULL,
  `target_chilometri` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `targa_mezzo` varchar(255) NOT NULL,
  `divisione` varchar(255) NOT NULL,
  `filiale` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `target_annuale`
--

INSERT INTO `target_annuale` (`id`, `anno`, `target_chilometri`, `username`, `targa_mezzo`, `divisione`, `filiale`) VALUES
(1, 2024, 45000, 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE'),
(2, 2025, 45000, 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE'),
(3, 2026, 45000, 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE'),
(4, 2027, 45000, 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE'),
(5, 2028, 45000, 'denis', 'GS495ZE', 'SP09', 'TRENTO STORAGE'),
(8, 2025, 40000, 'broll', 'LB000LB', 'SP09', 'TRENTO STORAGE'),
(9, 2026, 40000, 'broll', 'LB000LB', 'SP09', 'TRENTO STORAGE'),
(10, 2027, 40000, 'broll', 'LB000LB', 'SP09', 'TRENTO STORAGE'),
(11, 2028, 40000, 'broll', 'LB000LB', 'SP09', 'TRENTO STORAGE'),
(16, 2024, 4000, 'broll', 'LB000LB', 'SP09', 'TRENTO STORAGE'),
(17, 2024, 40000, 'test1', 'TI000TI', 'SP10', 'BORGO LARES'),
(18, 2025, 40000, 'test1', 'TI000TI', 'SP10', 'BORGO LARES'),
(19, 2026, 40000, 'test1', 'TI000TI', 'SP10', 'BORGO LARES'),
(20, 2027, 40000, 'test1', 'TI000TI', 'SP10', 'BORGO LARES');

-- --------------------------------------------------------

--
-- Struttura della tabella `utenti`
--

DROP TABLE IF EXISTS `utenti`;
CREATE TABLE `utenti` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `targa_mezzo` varchar(255) NOT NULL,
  `divisione` varchar(255) NOT NULL,
  `filiale` varchar(255) NOT NULL,
  `livello` varchar(255) NOT NULL,
  `Nome` varchar(255) NOT NULL,
  `Cognome` varchar(255) NOT NULL,
  `time_stamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `utenti`
--

INSERT INTO `utenti` (`id`, `username`, `password`, `targa_mezzo`, `divisione`, `filiale`, `livello`, `Nome`, `Cognome`, `time_stamp`) VALUES
(1, 'denis', '$2y$10$DaEow2.kNv2Cksu9ZKuelOCnGyKrD5e3maPJQyJ35zMsSQTJSfyU.', 'GS495ZE', 'SP09', 'TRENTO STORAGE', '3', 'Denis', 'Demonte', '2025-03-26 23:03:54'),
(4, 'broll', '$2y$10$aQxpvY8NRSgNl6aTtt7qdemQuou1z94o3/HBIjDltqzn3WHRVlxGu', 'LB000LB', 'SP09', 'TRENTO STORAGE', '3', 'Lucaa', 'Brol', '2025-03-26 23:03:54'),
(17, 'test1', '$2y$10$rTuBY69SX6vyJvqkZTo39OM4jM6D30w3I8dis73LjaQD.sBjG4vlC', 'TI000TI', 'SP10', 'BORGO LARES', '3', 'TEST UTENTE TIONE', 'test1', '2025-03-27 23:13:57'),
(22, 'trento', '$2y$10$9LeoNaUQFKNbxyEYV6a62ORKR6GtZ8c/oN3nut/CCHf5y6VZglwv.', '', 'SP09', 'TRENTO STORAGE', '2', 'Filiale Trento', '*', '2025-03-30 11:07:51'),
(28, 'test3', '$2y$10$arrChdhk3fwxEQ/S7lR0X.uIx7nJgNBpNvAaN4NL7GiObLu/ggp5q', 'PC000PC', 'SP01', 'PIACENZA-SEDE', '3', 'test3', 'test3', '2025-03-31 23:22:35'),
(29, 'admin', '$2y$10$BGC8QdXaPnZsjPs9KtwVquwBtzv8NGXsgWncF7tUzvz3gVaujife.', '*', '', '', '1', 'AMMINISTRATORE', '*', '2025-06-04 19:43:11');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `chilometri`
--
ALTER TABLE `chilometri`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `costo_extra`
--
ALTER TABLE `costo_extra`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `filiali`
--
ALTER TABLE `filiali`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `livelli_autorizzazione`
--
ALTER TABLE `livelli_autorizzazione`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `target_annuale`
--
ALTER TABLE `target_annuale`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `utenti`
--
ALTER TABLE `utenti`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `chilometri`
--
ALTER TABLE `chilometri`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT per la tabella `costo_extra`
--
ALTER TABLE `costo_extra`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT per la tabella `filiali`
--
ALTER TABLE `filiali`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT per la tabella `livelli_autorizzazione`
--
ALTER TABLE `livelli_autorizzazione`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `target_annuale`
--
ALTER TABLE `target_annuale`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT per la tabella `utenti`
--
ALTER TABLE `utenti`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
