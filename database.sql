-- Tworzenie bazy danych
CREATE DATABASE IF NOT EXISTS ubezpieczenia_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ubezpieczenia_db;

-- Usuwanie tabel jeśli istnieją, aby zacząć od nowa z nowymi danymi
DROP TABLE IF EXISTS Szkody, Polisy, Kalkulacje, Pojazdy, Klienci, Agenci;

-- Tabela Agenci
CREATE TABLE Agenci (
    IDPracownika INT PRIMARY KEY AUTO_INCREMENT,
    ImieNazwisko VARCHAR(100) NOT NULL,
    Stanowisko VARCHAR(50),
    TelefonSluzbowy VARCHAR(20),
    EmailSluzbowy VARCHAR(100) UNIQUE,
    Login VARCHAR(50) UNIQUE NOT NULL,
    Haslo VARCHAR(255) NOT NULL
);

-- Tabela Klienci
CREATE TABLE Klienci (
    IDKlienta INT PRIMARY KEY AUTO_INCREMENT,
    ImieNazwisko VARCHAR(100) NOT NULL,
    PESEL VARCHAR(11) UNIQUE,
    DataUrodzenia DATE,
    Ulica VARCHAR(100),
    NumerDomu VARCHAR(10),
    KodPocztowy VARCHAR(6),
    Miasto VARCHAR(50),
    Telefon VARCHAR(20),
    Email VARCHAR(100) UNIQUE NOT NULL,
    DataPrawkoJazdy DATE,
    DataPierwszejPolisy DATE,
    LataBezSzkody INT DEFAULT 0,
    HistoriaUbezpieczenia TEXT,
    Haslo VARCHAR(255) NOT NULL
);

-- Tabela Pojazdy
CREATE TABLE Pojazdy (
    IDPojazdu INT PRIMARY KEY AUTO_INCREMENT,
    IDKlienta INT,
    NrRejestracyjny VARCHAR(20) UNIQUE NOT NULL,
    Marka VARCHAR(50),
    Model VARCHAR(50),
    TypPojazdu VARCHAR(30) DEFAULT 'osobowy',
    RokProdukcji INT,
    PojemnoscSilnika INT,
    MocSilnika INT,
    RodzajPaliwa VARCHAR(20),
    Przebieg INT,
    WartoscRynkowa DECIMAL(10,2),
    VIN VARCHAR(17) UNIQUE NOT NULL, -- Zgodnie ze standardem
    DataPierwszejRejestracji DATE,
    FOREIGN KEY (IDKlienta) REFERENCES Klienci(IDKlienta) ON DELETE SET NULL
);

-- Tabela Kalkulacje składek
CREATE TABLE Kalkulacje (
    IDKalkulacji INT PRIMARY KEY AUTO_INCREMENT,
    IDKlienta INT,
    IDPojazdu INT,
    DataKalkulacji DATETIME DEFAULT CURRENT_TIMESTAMP,
    RodzajUbezpieczenia VARCHAR(10),
    SkladkaBazowa DECIMAL(10,2),
    WspolczynnikiRyzyka DECIMAL(5,2),
    ZnizkiZwyzki DECIMAL(10,2),
    SkladkaKoncowa DECIMAL(10,2),
    FOREIGN KEY (IDKlienta) REFERENCES Klienci(IDKlienta) ON DELETE CASCADE,
    FOREIGN KEY (IDPojazdu) REFERENCES Pojazdy(IDPojazdu) ON DELETE CASCADE
);

-- Tabela Polisy
CREATE TABLE Polisy (
    IDPolisy INT PRIMARY KEY AUTO_INCREMENT,
    IDKlienta INT,
    IDPojazdu INT,
    IDKalkulacji INT UNIQUE,
    NrPolisy VARCHAR(50) UNIQUE NOT NULL,
    TypUbezpieczenia VARCHAR(10),
    DataRozpoczecia DATE,
    DataZakonczenia DATE,
    Status VARCHAR(20) DEFAULT 'aktywna',
    SumaUbezpieczenia DECIMAL(12,2),
    SkladkaKoncowa DECIMAL(10,2),
    IDAgenta INT,
    FOREIGN KEY (IDKlienta) REFERENCES Klienci(IDKlienta) ON DELETE SET NULL,
    FOREIGN KEY (IDPojazdu) REFERENCES Pojazdy(IDPojazdu) ON DELETE SET NULL,
    FOREIGN KEY (IDKalkulacji) REFERENCES Kalkulacje(IDKalkulacji) ON DELETE SET NULL,
    FOREIGN KEY (IDAgenta) REFERENCES Agenci(IDPracownika) ON DELETE SET NULL
);

-- Tabela Szkody
CREATE TABLE Szkody (
    IDSzkody INT PRIMARY KEY AUTO_INCREMENT,
    IDPolisy INT,
    IDKlienta INT,
    IDPojazdu INT,
    DataZdarzenia DATE,
    OpisZdarzenia TEXT,
    MiejsceZdarzenia VARCHAR(200),
    PrzyczynaSzkody VARCHAR(100),
    SzacowanaWartosc DECIMAL(10,2),
    WartoscOdszkodowania DECIMAL(10,2) DEFAULT NULL,
    Status VARCHAR(50) DEFAULT 'zgłoszona',
    DataZgloszenia DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (IDPolisy) REFERENCES Polisy(IDPolisy) ON DELETE SET NULL,
    FOREIGN KEY (IDKlienta) REFERENCES Klienci(IDKlienta) ON DELETE SET NULL,
    FOREIGN KEY (IDPojazdu) REFERENCES Pojazdy(IDPojazdu) ON DELETE SET NULL
);

-- Agenci
INSERT INTO Agenci (ImieNazwisko, Stanowisko, TelefonSluzbowy, EmailSluzbowy, Login, Haslo) VALUES
('Karol Adminowski', 'Administrator Główny', '100200300', 'k.adminowski@ubezpieczenia.pro', 'admin', '$2y$10$oGOtVrRZM1dn7Y60GEjwE.w0eCnbsQWJb7ajRnay1Cl.l62u5jrZa'),
('Anna Nowakowska', 'Starszy Agent', '101201301', 'a.nowakowska@ubezpieczenia.pro', 'anowakowska', '$2y$10$t2Yj90T2y4wCvn7vtE5LD.gGqngY0Z8lpwzoivqCvbOawVhdQrAkm'),
('Piotr Zalewski', 'Agent Ubezpieczeniowy', '102202302', 'p.zalewski@ubezpieczenia.pro', 'pzalewski', '$2y$10$ImjoFx2Iat.4/ugQqnSM/enJFW0sY7YRk80iGlelTC89VaCf5Y8vq'),
('Ewa Bąk', 'Młodszy Agent', '103203303', 'e.bak@ubezpieczenia.pro', 'ebak', '$2y$10$cZtOXKdkiluz9SbDWtFBBeV9oQ7mQCFTy5B1Yvg3zpsMv2oINDozu');

-- Klienci (30)
INSERT INTO Klienci (ImieNazwisko, PESEL, DataUrodzenia, Ulica, NumerDomu, KodPocztowy, Miasto, Telefon, Email, DataPrawkoJazdy, DataPierwszejPolisy, LataBezSzkody, Haslo) VALUES
('Piotr Wiśniewski', '90010112345', '1990-01-01', 'Główna', '15', '00-001', 'Warszawa', '600100200', 'piotr@example.com', '2008-06-15', '2010-01-01', 5, '$2y$10$pmZvnW5QuRPqfsjfOlA/WO5gkEwfTo.IIvxdTgKan.tEkRsIDCqGK'),
('Maria Kowalczyk', '85051567890', '1985-05-15', 'Krótka', '7A', '50-001', 'Wrocław', '700200300', 'maria@example.com', '2003-09-20', '2005-03-15', 10, '<HASHED_PASSWORD>'),
('Jan Kowalski', '75010100001', '1975-01-01', 'Długa', '12B', '30-001', 'Kraków','555000111', 'jan.kowalski@example.com', '1995-01-01', '1998-01-01', 12, '<HASHED_PASSWORD>'),
('Anna Zając', '88020200002', '1988-02-02', 'Szeroka', '3', '80-800', 'Gdańsk', '555000222', 'anna.zajac@example.com', '2008-02-02', '2010-02-02', 6, '<HASHED_PASSWORD>'),
('Krzysztof Nowak', '92030300003', '1992-03-03', 'Wąska', '44', '60-600', 'Poznań','555000333', 'krzysztof.nowak@example.com','2012-03-03', '2014-03-03', 3, '<HASHED_PASSWORD>'),
('Alicja Lewandowska', '80040400004', '1980-04-04', 'Leśna', '1', '70-700', 'Szczecin','555000444', 'alicja.lewandowska@example.com', '2000-04-04', '2002-04-04', 15, '<HASHED_PASSWORD>'),
('Tomasz Wójcik', '95050500005', '1995-05-05', 'Polna', '5C', '90-900', 'Łódź', '555000555', 'tomasz.wojcik@example.com', '2015-05-05', '2017-05-05', 2, '<HASHED_PASSWORD>'),
('Magdalena Kaczmarek', '70060600006', '1970-06-06', 'Rzeczna', '6', '20-200', 'Lublin','555000666', 'magdalena.kaczmarek@example.com', '1990-06-06', '1993-06-06', 20, '<HASHED_PASSWORD>'),
('Grzegorz Zima', '99070700007', '1999-07-07', 'Zimowa', '11A', '40-400', 'Katowice','555000777', 'grzegorz.zima@example.com', '2019-07-07', '2021-07-07', 0, '<HASHED_PASSWORD>'),
('Ewelina Lato', '82080800008', '1982-08-08', 'Letnia', '21', '10-100', 'Olsztyn','555000888', 'ewelina.lato@example.com', '2002-08-08', '2004-08-08', 11, '<HASHED_PASSWORD>'),
('Michał Jesień', '77090900009', '1977-09-09', 'Jesienna', '3F', '25-250', 'Kielce','555000999', 'michal.jesien@example.com', '1997-09-09', '2000-09-09', 16, '<HASHED_PASSWORD>'),
('Monika Wiosna', '90101000010', '1990-10-10', 'Wiosenna', '87', '35-350', 'Rzeszów','555001010', 'monika.wiosna@example.com', '2010-10-10', '2012-10-10', 7, '<HASHED_PASSWORD>'),
('Barbara Pawlak', '68111100011', '1968-11-11', 'Słoneczna', '100', '15-150', 'Białystok','555001111', 'barbara.pawlak@example.com', '1988-11-11', '1990-11-11', 25, '<HASHED_PASSWORD>'),
('Kamil Sikora', '97121200012', '1997-12-12', 'Deszczowa', '4', '85-850', 'Bydgoszcz','555001212', 'kamil.sikora@example.com', '2017-12-12', '2019-12-12', 1, '<HASHED_PASSWORD>'),
('Dominik Dąbrowski', '83011300013', '1983-01-13', 'Parkowa', '202', '45-450', 'Opole','555001313', 'dominik.dabrowski@example.com', '2003-01-13', '2005-01-13', 13, '<HASHED_PASSWORD>'),
('Natalia Lis', '91021400014', '1991-02-14', 'Ogrodowa', '55E', '65-650', 'Zielona Góra','555001414', 'natalia.lis@example.com', '2011-02-14', '2013-02-14', 8, '<HASHED_PASSWORD>'),
('Patryk Baran', '73031500015', '1973-03-15', 'Łąkowa', '7G', '75-750', 'Koszalin','555001515', 'patryk.baran@example.com', '1993-03-15', '1996-03-15', 19, '<HASHED_PASSWORD>'),
('Justyna Górska', '96041600016', '1996-04-16', 'Górska', '9', '55-550', 'Jelenia Góra','555001616', 'justyna.gorska@example.com', '2016-04-16', '2018-04-16', 2, '<HASHED_PASSWORD>'),
('Adrian Sokołowski', '81051700017', '1981-05-17', 'Ptasia', '33', '87-870', 'Włocławek','555001717', 'adrian.sokolowski@example.com', '2001-05-17', '2003-05-17', 14, '<HASHED_PASSWORD>'),
('Kinga Chmielewska', '93061800018', '1993-06-18', 'Niebieska', '4A', '62-620', 'Konin','555001818', 'kinga.chmielewska@example.com', '2013-06-18', '2015-06-18', 6, '<HASHED_PASSWORD>'),
('Filip Woźniak', '76071900019', '1976-07-19', 'Czerwona', '101', '33-330', 'Nowy Sącz','555001919', 'filip.wozniak@example.com', '1996-07-19', '1999-07-19', 18, '<HASHED_PASSWORD>'),
('Sandra Krawczyk', '98082000020', '1998-08-20', 'Zielona', '77B', '76-760', 'Słupsk','555002020', 'sandra.krawczyk@example.com', '2018-08-20', '2020-08-20', 1, '<HASHED_PASSWORD>'),
('Oskar Mazur', '84092100021', '1984-09-21', 'Żółta', '2D', '39-390', 'Mielec','555002121', 'oskar.mazur@example.com', '2004-09-21', '2006-09-21', 12, '<HASHED_PASSWORD>'),
('Laura Grabowska', '94102200022', '1994-10-22', 'Fioletowa', '8', '08-080', 'Siedlce','555002222', 'laura.grabowska@example.com', '2014-10-22', '2016-10-10', 5, '<HASHED_PASSWORD>'),
('Bartosz Jabłoński', '79112300023', '1979-11-23', 'Pomarańczowa', '65F', '82-820', 'Elbląg','555002323', 'bartosz.jablonski@example.com', '1999-11-23', '2001-11-23', 17, '<HASHED_PASSWORD>'),
('Weronika Król', '00122400024', '2000-12-24', 'Biała', '1', '99-990', 'Zamość','555002424', 'weronika.krol@example.com', '2020-12-24', '2022-12-24', 0, '<HASHED_PASSWORD>'),
('Kacper Ziółkowski', '87012500025', '1987-01-25', 'Czarna', '333', '38-380', 'Przemyśl','555002525', 'kacper.ziolkowski@example.com', '2007-01-25', '2009-01-25', 11, '<HASHED_PASSWORD>'),
('Karolina Kwiatkowska', '90022600026', '1990-02-26', 'Różana', '7', '05-050', 'Legionowo','555002626', 'karolina.kwiatkowska@example.com', '2010-02-26', '2012-02-26', 9, '<HASHED_PASSWORD>'),
('Mateusz Głowacki', '74032700027', '1974-03-27', 'Tulipanowa', '12', '07-070', 'Ostrołęka','555002727', 'mateusz.glowacki@example.com', '1994-03-27', '1997-03-27', 20, '<HASHED_PASSWORD>'),
('Julia Michalska', '95042800028', '1995-04-28', 'Narcyzowa', '2C', '21-210', 'Świdnik','555002828', 'julia.michalska@example.com', '2015-04-28', '2017-04-28', 4, '<HASHED_PASSWORD>');

-- Pojazdy (Poprawione VINY, dodane nowe)
INSERT INTO Pojazdy (IDKlienta, NrRejestracyjny, Marka, Model, RokProdukcji, PojemnoscSilnika, MocSilnika, RodzajPaliwa, Przebieg, WartoscRynkowa, VIN, DataPierwszejRejestracji) VALUES
(1, 'WW1234X', 'Toyota', 'Corolla', 2019, 1598, 132, 'Benzyna', 55000, 72000.00, 'JT1BRREV0E012345X', '2019-03-10'),
(1, 'WW5678Y', 'Volkswagen', 'Tiguan', 2021, 1968, 150, 'Diesel', 30000, 130000.00, 'WVWZZZAD1MP00002Y', '2021-07-01'),
(2, 'DW9876Z', 'Skoda', 'Superb', 2020, 1998, 190, 'Benzyna', 45000, 115000.00, 'TMBJJ7NPXL000003Z', '2020-09-15'),
(3, 'KR111AB', 'BMW', 'X3', 2018, 1995, 190, 'Diesel', 88000, 145000.00, 'WBAUX31000L1234AB', '2018-05-20'),
(4, 'GD222BC', 'Audi', 'Q5', 2022, 1984, 261, 'Benzyna', 15000, 220000.00, 'WAUZZZFY0MA0000BC', '2022-02-02'),
(5, 'PO333CD', 'Ford', 'Kuga', 2019, 1499, 150, 'Benzyna', 62000, 82000.00, 'WF0SXXGAKEKG123CD', '2019-11-05'),
(6, 'SZ444DE', 'Opel', 'Insignia', 2020, 1598, 165, 'Benzyna', 48000, 95000.00, 'W0LGT8EMXL10000DE', '2020-06-11'),
(7, 'LU555EF', 'Hyundai', 'Tucson', 2021, 1591, 177, 'Benzyna', 22000, 110000.00, 'MALAB51LLMM0000EF', '2021-01-25'),
(8, 'KA666FG', 'Kia', 'Sportage', 2018, 1999, 185, 'Diesel', 75000, 88000.00, 'U5YHS815BJL0000FG', '2018-08-08'),
(9, 'EL777GH', 'Renault', 'Kadjar', 2019, 1332, 140, 'Benzyna', 51000, 79000.00, 'VF1RFE005H00000GH', '2019-04-17'),
(10, 'WW888HI', 'Peugeot', '3008', 2020, 1199, 130, 'Benzyna', 35000, 98000.00, 'VF3MCYHZWL00000HI', '2020-10-02'),
(11, 'KR999IJ', 'Volvo', 'XC60', 2017, 1969, 190, 'Diesel', 105000, 125000.00, 'YV1DZ8840H10000IJ', '2017-03-23'),
(12, 'GD000JK', 'Mazda', 'CX-5', 2021, 2488, 194, 'Benzyna', 18000, 140000.00, 'JMZKF2W70M10000JK', '2021-08-14'),
(13, 'PO111KL', 'Nissan', 'Qashqai', 2019, 1332, 160, 'Benzyna', 59000, 85000.00, 'SJNFAAJ10U10000KL', '2019-01-05'),
(14, 'SZ222LM', 'Fiat', 'Tipo', 2020, 1368, 95, 'Benzyna', 41000, 58000.00, 'ZFA3560000J0000LM', '2020-07-19'),
(15, 'LU333MN', 'Dacia', 'Duster', 2022, 1333, 150, 'Benzyna', 9000, 92000.00, 'UU1HJDAD0L00000MN', '2022-04-01'),
(4, 'GD456EF', 'Mercedes-Benz', 'A-Klasa', 2020, 1332, 163, 'Benzyna', 40000, 115000.00, 'WDD1770841J0000EF', '2020-05-15'); -- Drugi Anny

-- Kalkulacje (jedna kalkulacja na polisę dla uproszczenia)
INSERT INTO Kalkulacje (IDKalkulacji, IDKlienta, IDPojazdu, RodzajUbezpieczenia, SkladkaBazowa, WspolczynnikiRyzyka, ZnizkiZwyzki, SkladkaKoncowa) VALUES
(1, 1, 1, 'OCAC', 2200.00, 1.05, -100.00, 2205.00), -- Poprawiona kalkulacja składki (Przykład: (2200*1.05)-100)
(2, 1, 2, 'AC', 1800.00, 1.10, -50.00, 1930.00),
(3, 2, 3, 'OC', 950.00, 0.95, -50.00, 852.50),
(4, 3, 4, 'AC', 3500.00, 1.15, 150.00, 4175.00),
(5, 4, 5, 'OC', 880.00, 1.00, -10.00, 870.00),
(6, 4, 16, 'OCAC', 2500.00, 1.1, -150.00, 2600.00),
(7, 5, 6, 'OCAC', 1900.00, 1.00, 0.00, 1900.00),
(8, 6, 7, 'OC', 1050.00, 1.05, -80.00, 1022.50);
-- Polisy (zaktualizowane IDKalkulacji i składki)
INSERT INTO Polisy (IDKlienta, IDPojazdu, IDKalkulacji, NrPolisy, TypUbezpieczenia, DataRozpoczecia, DataZakonczenia, SumaUbezpieczenia, SkladkaKoncowa, IDAgenta) VALUES
(1, 1, 1, 'PRO/2024/0001', 'OCAC', DATE_SUB(CURDATE(), INTERVAL 5 MONTH), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), INTERVAL 1 YEAR), 72000.00, 2205.00, 2),
(1, 2, 2, 'PRO/2024/0002', 'AC', DATE_SUB(CURDATE(), INTERVAL 2 MONTH), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 2 MONTH), INTERVAL 1 YEAR), 130000.00, 1930.00, 3),
(2, 3, 3, 'PRO/2024/0003', 'OC', DATE_SUB(CURDATE(), INTERVAL 100 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 100 DAY), INTERVAL 1 YEAR), 1500000.00, 852.50, 4),
(3, 4, 4, 'PRO/2023/0004', 'AC', '2023-10-01', '2024-09-30', 145000.00, 4175.00, 2),
(4, 5, 5, 'PRO/2024/0005', 'OC', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 2000000.00, 870.00, 3),
(4, 16, 6, 'PRO/2024/0006', 'OCAC', DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 15 DAY), INTERVAL 1 YEAR),115000.00, 2600.00, 3),
(5, 6, 7, 'PRO/2023/0007', 'OCAC', '2023-08-15', '2024-08-14', 82000.00, 1900.00, 4),
(6, 7, 8, 'PRO/2024/0008', 'OC', DATE_SUB(CURDATE(), INTERVAL 60 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 60 DAY), INTERVAL 1 YEAR), 1200000.00, 1022.50, 2);

-- Szkody
INSERT INTO Szkody (IDPolisy, IDKlienta, IDPojazdu, DataZdarzenia, OpisZdarzenia, MiejsceZdarzenia, PrzyczynaSzkody, SzacowanaWartosc, WartoscOdszkodowania, Status) VALUES
(1, 1, 1, DATE_SUB(CURDATE(), INTERVAL 3 MONTH), 'Zarysowanie prawych drzwi na parkingu CH.', 'Warszawa, Parking Złote Tarasy', 'Nieznany sprawca', 1800.00, 1650.00, 'zlikwidowana'),
(2, 1, 2, DATE_SUB(CURDATE(), INTERVAL 1 MONTH), 'Pęknięta przednia szyba od kamienia spod kół ciężarówki.', 'Autostrada A2', 'Uderzenie kamienia', 1200.00, NULL, 'w trakcie likwidacji'),
(3, 2, 3, DATE_SUB(CURDATE(), INTERVAL 45 DAY), 'Wgniecenie tylnego zderzaka podczas cofania.', 'Wrocław, ul. Testowa', 'Kolizja przy parkowaniu', 2200.00, 2000.00, 'zlikwidowana'),
(4, 3, 4, DATE_SUB(CURDATE(), INTERVAL 20 DAY), 'Kradzież kołpaków i zarysowanie felgi.', 'Kraków, Pod blokiem', 'Kradzież i wandalizm', 800.00, NULL, 'zgłoszona'),
(5, 4, 5, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Delikatne otarcie na lewym nadkolu.', 'Gdańsk, Manewry na parkingu', 'Otarcie', 750.00, NULL, 'zgłoszona'),
(6, 4, 16, DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'Uszkodzone lusterko boczne.', 'Gdańsk, ul. Długa', 'Akt wandalizmu', 600.00, 550.00, 'zlikwidowana'),
(7, 5, 6, DATE_SUB(CURDATE(), INTERVAL 2 MONTH), 'Kolizja, przerysowany bok.', 'Poznań, Skrzyżowanie', 'Wymuszenie pierwszeństwa', 4500.00, NULL, 'w trakcie likwidacji'),
(8, 6, 7, DATE_SUB(CURDATE(), INTERVAL 80 DAY), 'Gradobicie, liczne wgniecenia karoserii.', 'Szczecin, Ogród', 'Warunki atmosferyczne', 9000.00, 8000.00, 'zlikwidowana');
