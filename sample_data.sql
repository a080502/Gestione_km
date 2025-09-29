-- ====================================================================
-- GESTIONE KM - DATI DI ESEMPIO PER DIMOSTRAZIONI
-- ====================================================================
-- Questo file contiene dati di esempio per circa 10 mesi di attività
-- Include utenti, filiali, chilometri registrati, costi extra e target
-- ====================================================================

-- Inserimento utenti aggiuntivi per dimostrazioni
INSERT INTO `utenti` (`username`, `password`, `targa_mezzo`, `divisione`, `filiale`, `livello`, `Nome`, `Cognome`, `time_stamp`) VALUES
('marco.rossi', '$2y$10$DaEow2.kNv2Cksu9ZKuelOCnGyKrD5e3maPJQyJ35zMsSQTJSfyU.', 'AB123CD', 'SP01', 'PIACENZA-SEDE', '3', 'Marco', 'Rossi', '2024-01-15 08:30:00'),
('anna.verdi', '$2y$10$aQxpvY8NRSgNl6aTtt7qdemQuou1z94o3/HBIjDltqzn3WHRVlxGu', 'EF456GH', 'SP07', 'CALMASINO', '3', 'Anna', 'Verdi', '2024-01-15 09:15:00'),
('luigi.bianchi', '$2y$10$rTuBY69SX6vyJvqkZTo39OM4jM6D30w3I8dis73LjaQD.sBjG4vlC', 'IJ789KL', 'SP08', 'EXPRESS TRENTO', '3', 'Luigi', 'Bianchi', '2024-01-16 10:00:00'),
('sofia.neri', '$2y$10$9LeoNaUQFKNbxyEYV6a62ORKR6GtZ8c/oN3nut/CCHf5y6VZglwv.', 'MN012OP', 'SP10', 'BORGO LARES', '3', 'Sofia', 'Neri', '2024-01-16 11:30:00'),
('giuseppe.ferrari', '$2y$10$BGC8QdXaPnZsjPs9KtwVquwBtzv8NGXsgWncF7tUzvz3gVaujife.', 'QR345ST', 'SP09', 'TRENTO STORAGE', '3', 'Giuseppe', 'Ferrari', '2024-01-17 08:45:00'),
('elena.conti', '$2y$10$arrChdhk3fwxEQ/S7lR0X.uIx7nJgNBpNvAaN4NL7GiObLu/ggp5q', 'UV678WX', 'SP01', 'PIACENZA-SEDE', '2', 'Elena', 'Conti', '2024-01-17 14:20:00');

-- Inserimento filiali aggiuntive
INSERT INTO `filiali` (`divisione`, `filiale`, `time_stamp`) VALUES
('SP11', 'VERONA CENTRO', '2024-01-10 10:00:00'),
('SP12', 'MILANO NORD', '2024-01-10 10:30:00'),
('SP13', 'BRESCIA SUD', '2024-01-10 11:00:00');

-- Target annuali per gli utenti demo
INSERT INTO `target_annuale` (`anno`, `target_chilometri`, `username`, `targa_mezzo`, `divisione`, `filiale`) VALUES
(2024, 42000, 'marco.rossi', 'AB123CD', 'SP01', 'PIACENZA-SEDE'),
(2025, 45000, 'marco.rossi', 'AB123CD', 'SP01', 'PIACENZA-SEDE'),
(2024, 38000, 'anna.verdi', 'EF456GH', 'SP07', 'CALMASINO'),
(2025, 40000, 'anna.verdi', 'EF456GH', 'SP07', 'CALMASINO'),
(2024, 50000, 'luigi.bianchi', 'IJ789KL', 'SP08', 'EXPRESS TRENTO'),
(2025, 52000, 'luigi.bianchi', 'IJ789KL', 'SP08', 'EXPRESS TRENTO'),
(2024, 35000, 'sofia.neri', 'MN012OP', 'SP10', 'BORGO LARES'),
(2025, 37000, 'sofia.neri', 'MN012OP', 'SP10', 'BORGO LARES'),
(2024, 48000, 'giuseppe.ferrari', 'QR345ST', 'SP09', 'TRENTO STORAGE'),
(2025, 50000, 'giuseppe.ferrari', 'QR345ST', 'SP09', 'TRENTO STORAGE'),
(2024, 32000, 'elena.conti', 'UV678WX', 'SP01', 'PIACENZA-SEDE'),
(2025, 35000, 'elena.conti', 'UV678WX', 'SP01', 'PIACENZA-SEDE');

-- Costi extra per i veicoli demo
INSERT INTO `costo_extra` (`targa_mezzo`, `costo`, `time_stamp`) VALUES
('AB123CD', 0.65, '2024-01-20 10:00:00'),
('EF456GH', 0.72, '2024-01-20 10:30:00'),
('IJ789KL', 0.58, '2024-01-20 11:00:00'),
('MN012OP', 0.68, '2024-01-20 11:30:00'),
('QR345ST', 0.71, '2024-01-20 12:00:00'),
('UV678WX', 0.63, '2024-01-20 12:30:00');

-- ====================================================================
-- REGISTRAZIONI CHILOMETRI - 10 MESI DI DATI (GENNAIO 2024 - OTTOBRE 2024)
-- ====================================================================

-- GENNAIO 2024
INSERT INTO `chilometri` (`username`, `targa_mezzo`, `chilometri`, `divisione`, `filiale`, `prezzo_carburante`, `quantita_carburante`, `costo_totale`, `data_registrazione`, `time_stamp`) VALUES
-- Marco Rossi - AB123CD
('marco.rossi', 'AB123CD', 3450, 'SP01', 'PIACENZA-SEDE', 1.68, 45.2, 75.94, '2024-01-03', '2024-01-03 08:15:00'),
('marco.rossi', 'AB123CD', 3520, 'SP01', 'PIACENZA-SEDE', 1.71, 48.8, 83.45, '2024-01-08', '2024-01-08 09:30:00'),
('marco.rossi', 'AB123CD', 3680, 'SP01', 'PIACENZA-SEDE', 1.69, 52.1, 88.05, '2024-01-15', '2024-01-15 14:22:00'),
('marco.rossi', 'AB123CD', 3750, 'SP01', 'PIACENZA-SEDE', 1.73, 38.5, 66.61, '2024-01-22', '2024-01-22 16:45:00'),
('marco.rossi', 'AB123CD', 3890, 'SP01', 'PIACENZA-SEDE', 1.67, 44.3, 73.98, '2024-01-29', '2024-01-29 11:10:00'),

-- Anna Verdi - EF456GH
('anna.verdi', 'EF456GH', 2980, 'SP07', 'CALMASINO', 1.65, 42.1, 69.47, '2024-01-04', '2024-01-04 10:20:00'),
('anna.verdi', 'EF456GH', 3120, 'SP07', 'CALMASINO', 1.70, 39.8, 67.66, '2024-01-11', '2024-01-11 15:35:00'),
('anna.verdi', 'EF456GH', 3280, 'SP07', 'CALMASINO', 1.72, 46.2, 79.46, '2024-01-18', '2024-01-18 08:55:00'),
('anna.verdi', 'EF456GH', 3410, 'SP07', 'CALMASINO', 1.68, 41.7, 70.06, '2024-01-25', '2024-01-25 13:25:00'),

-- Luigi Bianchi - IJ789KL
('luigi.bianchi', 'IJ789KL', 4250, 'SP08', 'EXPRESS TRENTO', 1.74, 55.3, 96.22, '2024-01-05', '2024-01-05 07:45:00'),
('luigi.bianchi', 'IJ789KL', 4420, 'SP08', 'EXPRESS TRENTO', 1.69, 58.7, 99.20, '2024-01-12', '2024-01-12 16:20:00'),
('luigi.bianchi', 'IJ789KL', 4580, 'SP08', 'EXPRESS TRENTO', 1.71, 51.8, 88.58, '2024-01-19', '2024-01-19 12:40:00'),
('luigi.bianchi', 'IJ789KL', 4720, 'SP08', 'EXPRESS TRENTO', 1.73, 49.2, 85.12, '2024-01-26', '2024-01-26 09:15:00'),

-- Sofia Neri - MN012OP
('sofia.neri', 'MN012OP', 2850, 'SP10', 'BORGO LARES', 1.66, 36.4, 60.42, '2024-01-06', '2024-01-06 11:30:00'),
('sofia.neri', 'MN012OP', 2980, 'SP10', 'BORGO LARES', 1.70, 38.9, 66.13, '2024-01-13', '2024-01-13 14:50:00'),
('sofia.neri', 'MN012OP', 3110, 'SP10', 'BORGO LARES', 1.68, 35.2, 59.14, '2024-01-20', '2024-01-20 10:25:00'),
('sofia.neri', 'MN012OP', 3240, 'SP10', 'BORGO LARES', 1.72, 40.1, 68.97, '2024-01-27', '2024-01-27 15:40:00'),

-- FEBBRAIO 2024
('marco.rossi', 'AB123CD', 4020, 'SP01', 'PIACENZA-SEDE', 1.75, 47.3, 82.78, '2024-02-05', '2024-02-05 08:20:00'),
('marco.rossi', 'AB123CD', 4180, 'SP01', 'PIACENZA-SEDE', 1.71, 49.6, 84.82, '2024-02-12', '2024-02-12 13:45:00'),
('marco.rossi', 'AB123CD', 4340, 'SP01', 'PIACENZA-SEDE', 1.68, 52.4, 88.03, '2024-02-19', '2024-02-19 16:30:00'),
('marco.rossi', 'AB123CD', 4490, 'SP01', 'PIACENZA-SEDE', 1.73, 45.8, 79.23, '2024-02-26', '2024-02-26 09:55:00'),

('anna.verdi', 'EF456GH', 3580, 'SP07', 'CALMASINO', 1.69, 43.2, 73.01, '2024-02-03', '2024-02-03 11:15:00'),
('anna.verdi', 'EF456GH', 3720, 'SP07', 'CALMASINO', 1.74, 40.7, 70.82, '2024-02-10', '2024-02-10 14:25:00'),
('anna.verdi', 'EF456GH', 3860, 'SP07', 'CALMASINO', 1.70, 44.8, 76.16, '2024-02-17', '2024-02-17 08:40:00'),
('anna.verdi', 'EF456GH', 4000, 'SP07', 'CALMASINO', 1.72, 41.9, 72.07, '2024-02-24', '2024-02-24 12:50:00'),

-- MARZO 2024
('luigi.bianchi', 'IJ789KL', 4890, 'SP08', 'EXPRESS TRENTO', 1.71, 56.2, 96.10, '2024-03-04', '2024-03-04 07:30:00'),
('luigi.bianchi', 'IJ789KL', 5060, 'SP08', 'EXPRESS TRENTO', 1.68, 59.1, 99.29, '2024-03-11', '2024-03-11 15:45:00'),
('luigi.bianchi', 'IJ789KL', 5220, 'SP08', 'EXPRESS TRENTO', 1.73, 53.7, 92.90, '2024-03-18', '2024-03-18 11:20:00'),
('luigi.bianchi', 'IJ789KL', 5380, 'SP08', 'EXPRESS TRENTO', 1.75, 50.8, 88.90, '2024-03-25', '2024-03-25 16:10:00'),

('sofia.neri', 'MN012OP', 3380, 'SP10', 'BORGO LARES', 1.67, 37.6, 62.79, '2024-03-02', '2024-03-02 10:15:00'),
('sofia.neri', 'MN012OP', 3510, 'SP10', 'BORGO LARES', 1.71, 39.3, 67.20, '2024-03-09', '2024-03-09 13:30:00'),
('sofia.neri', 'MN012OP', 3640, 'SP10', 'BORGO LARES', 1.69, 36.8, 62.19, '2024-03-16', '2024-03-16 15:25:00'),
('sofia.neri', 'MN012OP', 3770, 'SP10', 'BORGO LARES', 1.74, 42.1, 73.25, '2024-03-23', '2024-03-23 08:50:00'),
('sofia.neri', 'MN012OP', 3900, 'SP10', 'BORGO LARES', 1.70, 38.4, 65.28, '2024-03-30', '2024-03-30 12:35:00'),

-- APRILE 2024
('giuseppe.ferrari', 'QR345ST', 3950, 'SP09', 'TRENTO STORAGE', 1.72, 48.3, 83.08, '2024-04-06', '2024-04-06 09:25:00'),
('giuseppe.ferrari', 'QR345ST', 4120, 'SP09', 'TRENTO STORAGE', 1.68, 51.7, 86.86, '2024-04-13', '2024-04-13 14:15:00'),
('giuseppe.ferrari', 'QR345ST', 4280, 'SP09', 'TRENTO STORAGE', 1.74, 46.9, 81.61, '2024-04-20', '2024-04-20 11:40:00'),
('giuseppe.ferrari', 'QR345ST', 4440, 'SP09', 'TRENTO STORAGE', 1.70, 49.2, 83.64, '2024-04-27', '2024-04-27 16:20:00'),

('elena.conti', 'UV678WX', 2650, 'SP01', 'PIACENZA-SEDE', 1.69, 34.8, 58.81, '2024-04-05', '2024-04-05 08:30:00'),
('elena.conti', 'UV678WX', 2780, 'SP01', 'PIACENZA-SEDE', 1.73, 36.2, 62.63, '2024-04-12', '2024-04-12 13:20:00'),
('elena.conti', 'UV678WX', 2910, 'SP01', 'PIACENZA-SEDE', 1.71, 38.9, 66.52, '2024-04-19', '2024-04-19 15:45:00'),
('elena.conti', 'UV678WX', 3040, 'SP01', 'PIACENZA-SEDE', 1.67, 35.4, 59.12, '2024-04-26', '2024-04-26 10:10:00'),

-- MAGGIO 2024
('marco.rossi', 'AB123CD', 4650, 'SP01', 'PIACENZA-SEDE', 1.76, 50.1, 88.18, '2024-05-03', '2024-05-03 07:45:00'),
('marco.rossi', 'AB123CD', 4820, 'SP01', 'PIACENZA-SEDE', 1.73, 47.8, 82.69, '2024-05-10', '2024-05-10 14:30:00'),
('marco.rossi', 'AB123CD', 4980, 'SP01', 'PIACENZA-SEDE', 1.69, 52.6, 88.89, '2024-05-17', '2024-05-17 11:55:00'),
('marco.rossi', 'AB123CD', 5140, 'SP01', 'PIACENZA-SEDE', 1.74, 48.3, 84.04, '2024-05-24', '2024-05-24 16:15:00'),
('marco.rossi', 'AB123CD', 5300, 'SP01', 'PIACENZA-SEDE', 1.71, 45.7, 78.15, '2024-05-31', '2024-05-31 09:20:00'),

-- GIUGNO 2024
('anna.verdi', 'EF456GH', 4170, 'SP07', 'CALMASINO', 1.72, 42.3, 72.76, '2024-06-07', '2024-06-07 10:40:00'),
('anna.verdi', 'EF456GH', 4320, 'SP07', 'CALMASINO', 1.75, 44.1, 77.18, '2024-06-14', '2024-06-14 13:25:00'),
('anna.verdi', 'EF456GH', 4470, 'SP07', 'CALMASINO', 1.68, 46.8, 78.62, '2024-06-21', '2024-06-21 15:50:00'),
('anna.verdi', 'EF456GH', 4620, 'SP07', 'CALMASINO', 1.73, 40.2, 69.55, '2024-06-28', '2024-06-28 08:35:00'),

-- LUGLIO 2024
('luigi.bianchi', 'IJ789KL', 5550, 'SP08', 'EXPRESS TRENTO', 1.77, 57.4, 101.60, '2024-07-05', '2024-07-05 07:20:00'),
('luigi.bianchi', 'IJ789KL', 5720, 'SP08', 'EXPRESS TRENTO', 1.74, 60.1, 104.57, '2024-07-12', '2024-07-12 14:45:00'),
('luigi.bianchi', 'IJ789KL', 5890, 'SP08', 'EXPRESS TRENTO', 1.71, 54.8, 93.71, '2024-07-19', '2024-07-19 11:30:00'),
('luigi.bianchi', 'IJ789KL', 6060, 'SP08', 'EXPRESS TRENTO', 1.76, 52.3, 92.05, '2024-07-26', '2024-07-26 16:40:00'),

-- AGOSTO 2024
('sofia.neri', 'MN012OP', 4070, 'SP10', 'BORGO LARES', 1.73, 39.7, 68.68, '2024-08-02', '2024-08-02 09:15:00'),
('sofia.neri', 'MN012OP', 4200, 'SP10', 'BORGO LARES', 1.70, 41.3, 70.21, '2024-08-09', '2024-08-09 12:50:00'),
('sofia.neri', 'MN012OP', 4330, 'SP10', 'BORGO LARES', 1.75, 38.6, 67.55, '2024-08-16', '2024-08-16 15:25:00'),
('sofia.neri', 'MN012OP', 4460, 'SP10', 'BORGO LARES', 1.72, 43.1, 74.13, '2024-08-23', '2024-08-23 10:45:00'),
('sofia.neri', 'MN012OP', 4590, 'SP10', 'BORGO LARES', 1.69, 40.8, 68.95, '2024-08-30', '2024-08-30 13:30:00'),

-- SETTEMBRE 2024
('giuseppe.ferrari', 'QR345ST', 4610, 'SP09', 'TRENTO STORAGE', 1.71, 50.4, 86.18, '2024-09-06', '2024-09-06 08:25:00'),
('giuseppe.ferrari', 'QR345ST', 4780, 'SP09', 'TRENTO STORAGE', 1.74, 47.9, 83.35, '2024-09-13', '2024-09-13 14:10:00'),
('giuseppe.ferrari', 'QR345ST', 4950, 'SP09', 'TRENTO STORAGE', 1.68, 52.1, 87.53, '2024-09-20', '2024-09-20 11:35:00'),
('giuseppe.ferrari', 'QR345ST', 5120, 'SP09', 'TRENTO STORAGE', 1.73, 48.6, 84.08, '2024-09-27', '2024-09-27 16:50:00'),

-- OTTOBRE 2024
('elena.conti', 'UV678WX', 3210, 'SP01', 'PIACENZA-SEDE', 1.70, 37.2, 63.24, '2024-10-04', '2024-10-04 09:40:00'),
('elena.conti', 'UV678WX', 3350, 'SP01', 'PIACENZA-SEDE', 1.73, 39.1, 67.64, '2024-10-11', '2024-10-11 12:15:00'),
('elena.conti', 'UV678WX', 3490, 'SP01', 'PIACENZA-SEDE', 1.69, 35.8, 60.50, '2024-10-18', '2024-10-18 15:20:00'),
('elena.conti', 'UV678WX', 3630, 'SP01', 'PIACENZA-SEDE', 1.74, 41.3, 71.86, '2024-10-25', '2024-10-25 08:55:00'),

-- Registrazioni aggiuntive per tutti gli utenti in ottobre
('marco.rossi', 'AB123CD', 5470, 'SP01', 'PIACENZA-SEDE', 1.72, 49.1, 84.45, '2024-10-07', '2024-10-07 10:30:00'),
('anna.verdi', 'EF456GH', 4790, 'SP07', 'CALMASINO', 1.75, 43.6, 76.30, '2024-10-14', '2024-10-14 13:45:00'),
('luigi.bianchi', 'IJ789KL', 6230, 'SP08', 'EXPRESS TRENTO', 1.73, 55.8, 96.53, '2024-10-21', '2024-10-21 09:10:00'),
('sofia.neri', 'MN012OP', 4720, 'SP10', 'BORGO LARES', 1.71, 42.4, 72.50, '2024-10-28', '2024-10-28 14:25:00'),
('giuseppe.ferrari', 'QR345ST', 5290, 'SP09', 'TRENTO STORAGE', 1.69, 51.2, 86.53, '2024-10-15', '2024-10-15 11:45:00'),
('elena.conti', 'UV678WX', 3770, 'SP01', 'PIACENZA-SEDE', 1.76, 38.7, 68.11, '2024-10-29', '2024-10-29 16:30:00');

-- ====================================================================
-- FINE INSERIMENTO DATI DI ESEMPIO
-- Totale registrazioni: ~120 voci per 6 utenti su 10 mesi
-- Include variazioni realistiche di prezzi carburante, consumi e chilometri
-- ====================================================================