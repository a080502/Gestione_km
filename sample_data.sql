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
INSERT INTO `chilometri` (`data`, `chilometri_iniziali`, `chilometri_finali`, `note`, `litri_carburante`, `euro_spesi`, `username`, `targa_mezzo`, `divisione`, `filiale`, `livello`, `timestamp`) VALUES
-- Marco Rossi - AB123CD
('2024-01-03', 3000, 3450, 'Viaggio clienti Piacenza', '45.2', 75.94, 'marco.rossi', 'AB123CD', 'SP01', 'PIACENZA-SEDE', 3, '2024-01-03 08:15:00'),
('2024-01-08', 3450, 3590, 'Consegne urbane', '18.5', 31.40, 'marco.rossi', 'AB123CD', 'SP01', 'PIACENZA-SEDE', 3, '2024-01-08 09:30:00'),
('2024-01-15', 3590, 3780, 'Trasferta Milano', '24.1', 41.21, 'marco.rossi', 'AB123CD', 'SP01', 'PIACENZA-SEDE', 3, '2024-01-15 14:22:00'),
('2024-01-22', 3780, 3920, 'Giro clienti', '16.8', 28.56, 'marco.rossi', 'AB123CD', 'SP01', 'PIACENZA-SEDE', 3, '2024-01-22 16:45:00'),
('2024-01-29', 3920, 4070, 'Consegne settimanali', '18.3', 31.11, 'marco.rossi', 'AB123CD', 'SP01', 'PIACENZA-SEDE', 3, '2024-01-29 11:10:00'),

-- Anna Verdi - EF456GH
('2024-01-04', 2800, 2980, 'Rotta Calmasino-Verona', '21.1', 35.47, 'anna.verdi', 'EF456GH', 'SP07', 'CALMASINO', 3, '2024-01-04 10:20:00'),
('2024-01-11', 2980, 3140, 'Consegne lago di Garda', '19.8', 33.66, 'anna.verdi', 'EF456GH', 'SP07', 'CALMASINO', 3, '2024-01-11 15:35:00'),
('2024-01-18', 3140, 3310, 'Viaggio Brescia', '22.2', 37.74, 'anna.verdi', 'EF456GH', 'SP07', 'CALMASINO', 3, '2024-01-18 08:55:00'),
('2024-01-25', 3310, 3450, 'Giro clienti Verona', '17.7', 30.09, 'anna.verdi', 'EF456GH', 'SP07', 'CALMASINO', 3, '2024-01-25 13:25:00'),

-- Luigi Bianchi - IJ789KL (Express - più km)
('2024-01-05', 4000, 4280, 'Express consegne urgenti', '35.3', 59.22, 'luigi.bianchi', 'IJ789KL', 'SP08', 'EXPRESS TRENTO', 3, '2024-01-05 07:45:00'),
('2024-01-12', 4280, 4520, 'Trasferta Bologna', '28.7', 48.79, 'luigi.bianchi', 'IJ789KL', 'SP08', 'EXPRESS TRENTO', 3, '2024-01-12 16:20:00'),
('2024-01-19', 4520, 4740, 'Rotta Trento-Milano', '26.8', 45.56, 'luigi.bianchi', 'IJ789KL', 'SP08', 'EXPRESS TRENTO', 3, '2024-01-19 12:40:00'),
('2024-01-26', 4740, 4970, 'Consegne weekend', '29.2', 49.64, 'luigi.bianchi', 'IJ789KL', 'SP08', 'EXPRESS TRENTO', 3, '2024-01-26 09:15:00'),

-- Sofia Neri - MN012OP
('2024-01-06', 2700, 2850, 'Giro Borgo Lares', '14.4', 24.48, 'sofia.neri', 'MN012OP', 'SP10', 'BORGO LARES', 3, '2024-01-06 11:30:00'),
('2024-01-13', 2850, 2990, 'Consegne Val di Non', '16.9', 28.73, 'sofia.neri', 'MN012OP', 'SP10', 'BORGO LARES', 3, '2024-01-13 14:50:00'),
('2024-01-20', 2990, 3120, 'Viaggio Trento', '15.2', 25.84, 'sofia.neri', 'MN012OP', 'SP10', 'BORGO LARES', 3, '2024-01-20 10:25:00'),
('2024-01-27', 3120, 3260, 'Rotta turistica', '17.1', 29.07, 'sofia.neri', 'MN012OP', 'SP10', 'BORGO LARES', 3, '2024-01-27 15:40:00'),

-- FEBBRAIO 2024
('2024-02-05', 4070, 4230, 'Trasferta Lombardia', '19.3', 33.78, 'marco.rossi', 'AB123CD', 'SP01', 'PIACENZA-SEDE', 3, '2024-02-05 08:20:00'),
('2024-02-12', 4230, 4390, 'Consegne settimana', '18.6', 32.56, 'marco.rossi', 'AB123CD', 'SP01', 'PIACENZA-SEDE', 3, '2024-02-12 13:45:00'),
('2024-02-19', 4390, 4550, 'Giro clienti Emilia', '19.4', 33.95, 'marco.rossi', 'AB123CD', 'SP01', 'PIACENZA-SEDE', 3, '2024-02-19 16:30:00'),
('2024-02-26', 4550, 4700, 'Consegne fine mese', '17.8', 31.13, 'marco.rossi', 'AB123CD', 'SP01', 'PIACENZA-SEDE', 3, '2024-02-26 09:55:00'),

('2024-02-03', 3450, 3620, 'Rotta lago Garda', '20.2', 34.82, 'anna.verdi', 'EF456GH', 'SP07', 'CALMASINO', 3, '2024-02-03 11:15:00'),
('2024-02-10', 3620, 3780, 'Viaggio Mantova', '18.7', 32.24, 'anna.verdi', 'EF456GH', 'SP07', 'CALMASINO', 3, '2024-02-10 14:25:00'),
('2024-02-17', 3780, 3940, 'Consegne Verona', '19.8', 34.16, 'anna.verdi', 'EF456GH', 'SP07', 'CALMASINO', 3, '2024-02-17 08:40:00'),
('2024-02-24', 3940, 4100, 'Giro clienti', '18.9', 32.57, 'anna.verdi', 'EF456GH', 'SP07', 'CALMASINO', 3, '2024-02-24 12:50:00'),

-- MARZO 2024
('2024-03-04', 4970, 5230, 'Express Roma', '32.2', 55.10, 'luigi.bianchi', 'IJ789KL', 'SP08', 'EXPRESS TRENTO', 3, '2024-03-04 07:30:00'),
('2024-03-11', 5230, 5480, 'Consegne urgenti', '29.1', 49.79, 'luigi.bianchi', 'IJ789KL', 'SP08', 'EXPRESS TRENTO', 3, '2024-03-11 15:45:00'),
('2024-03-18', 5480, 5720, 'Trasferta Veneto', '27.7', 47.40, 'luigi.bianchi', 'IJ789KL', 'SP08', 'EXPRESS TRENTO', 3, '2024-03-18 11:20:00'),
('2024-03-25', 5720, 5960, 'Rotta Adriatico', '28.8', 49.28, 'luigi.bianchi', 'IJ789KL', 'SP08', 'EXPRESS TRENTO', 3, '2024-03-25 16:10:00'),

('2024-03-02', 3260, 3400, 'Giro valle', '16.6', 28.79, 'sofia.neri', 'MN012OP', 'SP10', 'BORGO LARES', 3, '2024-03-02 10:15:00'),
('2024-03-09', 3400, 3540, 'Consegne settimanali', '17.3', 29.20, 'sofia.neri', 'MN012OP', 'SP10', 'BORGO LARES', 3, '2024-03-09 13:30:00'),
('2024-03-16', 3540, 3680, 'Viaggio Bolzano', '16.8', 28.19, 'sofia.neri', 'MN012OP', 'SP10', 'BORGO LARES', 3, '2024-03-16 15:25:00'),
('2024-03-23', 3680, 3820, 'Rotta turistica', '18.1', 31.25, 'sofia.neri', 'MN012OP', 'SP10', 'BORGO LARES', 3, '2024-03-23 08:50:00'),
('2024-03-30', 3820, 3960, 'Consegne fine mese', '17.4', 29.58, 'sofia.neri', 'MN012OP', 'SP10', 'BORGO LARES', 3, '2024-03-30 12:35:00'),

-- APRILE 2024
('2024-04-06', 3800, 3980, 'Primo giro aprile', '22.3', 38.08, 'giuseppe.ferrari', 'QR345ST', 'SP09', 'TRENTO STORAGE', 3, '2024-04-06 09:25:00'),
('2024-04-13', 3980, 4160, 'Consegne storage', '21.7', 37.06, 'giuseppe.ferrari', 'QR345ST', 'SP09', 'TRENTO STORAGE', 3, '2024-04-13 14:15:00'),
('2024-04-20', 4160, 4340, 'Trasferta Innsbruck', '20.9', 35.71, 'giuseppe.ferrari', 'QR345ST', 'SP09', 'TRENTO STORAGE', 3, '2024-04-20 11:40:00'),
('2024-04-27', 4340, 4520, 'Giro clienti', '22.2', 37.94, 'giuseppe.ferrari', 'QR345ST', 'SP09', 'TRENTO STORAGE', 3, '2024-04-27 16:20:00'),

('2024-04-05', 2500, 2650, 'Avvio attività', '16.8', 28.81, 'elena.conti', 'UV678WX', 'SP01', 'PIACENZA-SEDE', 2, '2024-04-05 08:30:00'),
('2024-04-12', 2650, 2800, 'Supervisione zona', '15.2', 26.63, 'elena.conti', 'UV678WX', 'SP01', 'PIACENZA-SEDE', 2, '2024-04-12 13:20:00'),
('2024-04-19', 2800, 2950, 'Controlli qualità', '16.9', 29.52, 'elena.conti', 'UV678WX', 'SP01', 'PIACENZA-SEDE', 2, '2024-04-19 15:45:00'),
('2024-04-26', 2950, 3100, 'Riunioni clienti', '15.4', 26.12, 'elena.conti', 'UV678WX', 'SP01', 'PIACENZA-SEDE', 2, '2024-04-26 10:10:00'),

-- MAGGIO 2024
('2024-05-03', 4700, 4880, 'Maggio intenso', '20.1', 35.38, 'marco.rossi', 'AB123CD', 'SP01', 'PIACENZA-SEDE', 3, '2024-05-03 07:45:00'),
('2024-05-10', 4880, 5060, 'Trasferte lombarde', '19.8', 34.69, 'marco.rossi', 'AB123CD', 'SP01', 'PIACENZA-SEDE', 3, '2024-05-10 14:30:00'),
('2024-05-17', 5060, 5240, 'Consegne speciali', '20.6', 36.09, 'marco.rossi', 'AB123CD', 'SP01', 'PIACENZA-SEDE', 3, '2024-05-17 11:55:00'),
('2024-05-24', 5240, 5420, 'Giro esteso', '19.3', 33.84, 'marco.rossi', 'AB123CD', 'SP01', 'PIACENZA-SEDE', 3, '2024-05-24 16:15:00'),
('2024-05-31', 5420, 5600, 'Fine maggio', '18.7', 32.15, 'marco.rossi', 'AB123CD', 'SP01', 'PIACENZA-SEDE', 3, '2024-05-31 09:20:00'),

-- GIUGNO 2024
('2024-06-07', 4100, 4280, 'Inizio estate', '21.3', 37.76, 'anna.verdi', 'EF456GH', 'SP07', 'CALMASINO', 3, '2024-06-07 10:40:00'),
('2024-06-14', 4280, 4460, 'Stagione turistica', '22.1', 39.18, 'anna.verdi', 'EF456GH', 'SP07', 'CALMASINO', 3, '2024-06-14 13:25:00'),
('2024-06-21', 4460, 4640, 'Consegne estive', '21.8', 36.62, 'anna.verdi', 'EF456GH', 'SP07', 'CALMASINO', 3, '2024-06-21 15:50:00'),
('2024-06-28', 4640, 4820, 'Fine giugno', '20.2', 34.55, 'anna.verdi', 'EF456GH', 'SP07', 'CALMASINO', 3, '2024-06-28 08:35:00'),

-- LUGLIO 2024
('2024-07-05', 5960, 6240, 'Estate intensa', '34.4', 60.60, 'luigi.bianchi', 'IJ789KL', 'SP08', 'EXPRESS TRENTO', 3, '2024-07-05 07:20:00'),
('2024-07-12', 6240, 6520, 'Express estate', '32.1', 56.57, 'luigi.bianchi', 'IJ789KL', 'SP08', 'EXPRESS TRENTO', 3, '2024-07-12 14:45:00'),
('2024-07-19', 6520, 6800, 'Consegne urgenti', '31.8', 54.71, 'luigi.bianchi', 'IJ789KL', 'SP08', 'EXPRESS TRENTO', 3, '2024-07-19 11:30:00'),
('2024-07-26', 6800, 7080, 'Trasferte estive', '30.3', 53.05, 'luigi.bianchi', 'IJ789KL', 'SP08', 'EXPRESS TRENTO', 3, '2024-07-26 16:40:00'),

-- AGOSTO 2024
('2024-08-02', 3960, 4120, 'Agosto turistico', '18.7', 32.68, 'sofia.neri', 'MN012OP', 'SP10', 'BORGO LARES', 3, '2024-08-02 09:15:00'),
('2024-08-09', 4120, 4280, 'Peak season', '19.3', 33.21, 'sofia.neri', 'MN012OP', 'SP10', 'BORGO LARES', 3, '2024-08-09 12:50:00'),
('2024-08-16', 4280, 4440, 'Ferragosto lavoro', '18.6', 32.55, 'sofia.neri', 'MN012OP', 'SP10', 'BORGO LARES', 3, '2024-08-16 15:25:00'),
('2024-08-23', 4440, 4600, 'Consegne turistiche', '20.1', 35.13, 'sofia.neri', 'MN012OP', 'SP10', 'BORGO LARES', 3, '2024-08-23 10:45:00'),
('2024-08-30', 4600, 4760, 'Fine agosto', '19.8', 34.95, 'sofia.neri', 'MN012OP', 'SP10', 'BORGO LARES', 3, '2024-08-30 13:30:00'),

-- SETTEMBRE 2024
('2024-09-06', 4520, 4720, 'Ripresa settembre', '23.4', 40.18, 'giuseppe.ferrari', 'QR345ST', 'SP09', 'TRENTO STORAGE', 3, '2024-09-06 08:25:00'),
('2024-09-13', 4720, 4920, 'Intensificazione', '22.9', 39.35, 'giuseppe.ferrari', 'QR345ST', 'SP09', 'TRENTO STORAGE', 3, '2024-09-13 14:10:00'),
('2024-09-20', 4920, 5120, 'Consegne autunno', '24.1', 41.53, 'giuseppe.ferrari', 'QR345ST', 'SP09', 'TRENTO STORAGE', 3, '2024-09-20 11:35:00'),
('2024-09-27', 5120, 5320, 'Fine settembre', '23.6', 40.08, 'giuseppe.ferrari', 'QR345ST', 'SP09', 'TRENTO STORAGE', 3, '2024-09-27 16:50:00'),

-- OTTOBRE 2024
('2024-10-04', 3100, 3260, 'Supervisione ottobre', '17.2', 29.24, 'elena.conti', 'UV678WX', 'SP01', 'PIACENZA-SEDE', 2, '2024-10-04 09:40:00'),
('2024-10-11', 3260, 3420, 'Controlli mensili', '16.1', 27.64, 'elena.conti', 'UV678WX', 'SP01', 'PIACENZA-SEDE', 2, '2024-10-11 12:15:00'),
('2024-10-18', 3420, 3580, 'Riunioni trimestre', '15.8', 27.50, 'elena.conti', 'UV678WX', 'SP01', 'PIACENZA-SEDE', 2, '2024-10-18 15:20:00'),
('2024-10-25', 3580, 3740, 'Supervisione zone', '17.3', 30.86, 'elena.conti', 'UV678WX', 'SP01', 'PIACENZA-SEDE', 2, '2024-10-25 08:55:00'),

-- Registrazioni finali ottobre per completare l'anno
('2024-10-07', 5600, 5790, 'Ottobre finale', '20.1', 34.45, 'marco.rossi', 'AB123CD', 'SP01', 'PIACENZA-SEDE', 3, '2024-10-07 10:30:00'),
('2024-10-14', 4820, 5010, 'Autunno lago', '21.6', 37.30, 'anna.verdi', 'EF456GH', 'SP07', 'CALMASINO', 3, '2024-10-14 13:45:00'),
('2024-10-21', 7080, 7380, 'Express autunno', '33.8', 58.53, 'luigi.bianchi', 'IJ789KL', 'SP08', 'EXPRESS TRENTO', 3, '2024-10-21 09:10:00'),
('2024-10-28', 4760, 4920, 'Fine ottobre', '19.4', 33.50, 'sofia.neri', 'MN012OP', 'SP10', 'BORGO LARES', 3, '2024-10-28 14:25:00'),
('2024-10-15', 5320, 5520, 'Storage autunno', '24.2', 41.53, 'giuseppe.ferrari', 'QR345ST', 'SP09', 'TRENTO STORAGE', 3, '2024-10-15 11:45:00'),
('2024-10-29', 3740, 3900, 'Chiusura mese', '16.7', 29.11, 'elena.conti', 'UV678WX', 'SP01', 'PIACENZA-SEDE', 2, '2024-10-29 16:30:00');

-- ====================================================================
-- FINE INSERIMENTO DATI DI ESEMPIO
-- Totale registrazioni: ~70 voci per 6 utenti su 10 mesi
-- Include progressioni realistiche chilometriche e consumi carburante
-- ====================================================================