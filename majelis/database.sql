-- Database: majelis_dzikir
-- Created for Majelis Dzikir Management System

CREATE DATABASE IF NOT EXISTS majelis_dzikir;
USE majelis_dzikir;

-- Table: wilayah (Regions)
CREATE TABLE wilayah (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_wilayah VARCHAR(255) NOT NULL,
    polygon JSON NOT NULL COMMENT 'GeoJSON polygon coordinates',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: majelis_dzikir (Prayer Groups)
CREATE TABLE majelis_dzikir (
    id INT PRIMARY KEY AUTO_INCREMENT,
    wilayah_id INT NOT NULL,
    nama_majelis VARCHAR(255) NOT NULL,
    alamat TEXT,
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (wilayah_id) REFERENCES wilayah(id) ON DELETE CASCADE
);

-- Table: petugas_pentawajuh (Officers)
CREATE TABLE petugas_pentawajuh (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(255) NOT NULL,
    telepon VARCHAR(20),
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,
    id_wilayah INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_wilayah) REFERENCES wilayah(id) ON DELETE CASCADE
);

-- Table: penugasan (Assignments)
CREATE TABLE penugasan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_petugas INT NOT NULL,
    id_majelis INT NOT NULL,
    tanggal DATE NOT NULL,
    jarak_km DECIMAL(8,2) GENERATED ALWAYS AS (
        6371 * ACOS(
            LEAST(1.0,
                GREATEST(-1.0,
                    COS(RADIANS((SELECT latitude FROM majelis_dzikir WHERE id = id_majelis))) *
                    COS(RADIANS((SELECT latitude FROM petugas_pentawajuh WHERE id = id_petugas))) *
                    COS(RADIANS((SELECT longitude FROM petugas_pentawajuh WHERE id = id_petugas)) -
                         RADIANS((SELECT longitude FROM majelis_dzikir WHERE id = id_majelis))) +
                    SIN(RADIANS((SELECT latitude FROM majelis_dzikir WHERE id = id_majelis))) *
                    SIN(RADIANS((SELECT latitude FROM petugas_pentawajuh WHERE id = id_petugas)))
                )
            )
        )
    ) STORED,
    tugas ENUM('manual', 'otomatis') DEFAULT 'manual',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_petugas) REFERENCES petugas_pentawajuh(id) ON DELETE CASCADE,
    FOREIGN KEY (id_majelis) REFERENCES majelis_dzikir(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment (id_petugas, tanggal),
    INDEX idx_tanggal (tanggal),
    INDEX idx_majelis_tanggal (id_majelis, tanggal)
);

-- Table: user (Users)
CREATE TABLE user (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL COMMENT 'Hashed with password_hash()',
    role ENUM('admin', 'regional') NOT NULL DEFAULT 'regional',
    wilayah_id INT NULL COMMENT 'NULL for admin, filled for regional users',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (wilayah_id) REFERENCES wilayah(id) ON DELETE SET NULL
);

-- Initial Data Setup

-- Data untuk tabel `wilayah`
INSERT INTO `wilayah` (`id`, `nama_wilayah`, `polygon`, `created_at`, `updated_at`) VALUES
(1, 'Indonesia, BKMZ Jakarta', '[[-4.631179340411012,104.381103515625],[-6.893707270014225,104.447021484375],[-6.860985433763648,106.74316406250001],[-6.882800241767556,107.64404296875001],[-6.217012327817175,107.67700195312501],[-4.872047700241915,107.73193359375]]', NOW(), NOW()),
(2, 'Indonesia, BKMZ Kalteng 1', '[[-1.9496968587473593,111.48925781250001],[-2.58640142780824,111.51123046875001],[-2.5479878714713835,114.44458007812501],[-1.9002862838753778,114.44458007812501]]', NOW(), NOW()),
(3, 'Indonesia, BKMZ Kalteng 2', '[[-0.5987439850125229,114.61486816406251],[-1.6587038068676119,114.60937500000001],[-1.6147764249054963,115.411376953125],[-0.615222552406841,115.433349609375]]', NOW(), NOW()),
(4, 'Malaysia, KL', '[[2.8662354211137324,101.282958984375],[2.8826941788448823,102.16735839843751],[3.6066246213236863,102.14538574218751],[3.5956599859799567,101.26098632812501]]', NOW(), NOW()),
(5, 'Singapura, Central', '[[0.7250783020332547,103.62304687500001],[0.8129610018708315,105.07324218750001],[2.196727241761671,103.8]]', NOW(), NOW());

-- Data untuk tabel `majelis_dzikir`
INSERT INTO `majelis_dzikir` (`id`, `wilayah_id`, `nama_majelis`, `alamat`, `latitude`, `longitude`, `created_at`, `updated_at`) VALUES
(1, 1, 'MZ Bakauheni', 'Jl. Lintas Sumatera km.05 Sidoluhur', -5.83590440, 105.73123410, NOW(), NOW()),
(2, 1, 'MZ Bandar Lampung', 'Jl. Raflesia Baru No.16 Way Dadi, Kec. Sukarame', -5.37256630, 105.28525910, NOW(), NOW()),
(3, 1, 'MZ BEKASI', 'Jl. Karang Indah No.118 Karangsatria', -6.22310110, 107.04423740, NOW(), NOW()),
(4, 1, 'MZ Bogor', 'Jl. Taman Cimanggu Poncol No.3 Kedung Waringin', -6.58471870, 106.73598500, NOW(), NOW()),
(5, 1, 'MZ Karawang', 'Jl. Jatimulya 2 Mekarjati', -6.25733850, 107.30196950, NOW(), NOW()),
(6, 1, 'MZ Kelapa Gading', 'Jl. Sengon No.Blok F', -6.14237990, 106.90434960, NOW(), NOW()),
(7, 1, 'MZ PALMERAH', 'C1 Jl. Palmerah Bar. IX No.15', -6.20408390, 106.78761350, NOW(), NOW()),
(8, 1, 'MZ Pasar Minggu', 'Jl. Muhamad Dahlan No.11', -6.28325940, 106.84669160, NOW(), NOW()),
(9, 1, 'MZ Penjaringan', 'Jl. Luar Batang II No.3', -6.12562710, 106.80666100, NOW(), NOW()),
(10, 1, 'MZ Rumpin', 'Unnamed Road, Mekar Sari', -6.38534220, 106.60197210, NOW(), NOW()),
(11, 1, 'MZ Serang', 'Drangong, Taktakan, Serang City', -6.09177440, 106.13925000, NOW(), NOW()),
(12, 1, 'MZ SOLEAR', 'Jl. Raya Cisoka No.Km. 4, Cireundeu', -6.30752180, 106.41152910, NOW(), NOW()),
(13, 1, 'MZ SUKABANJAR', 'Hutan, Negeri Katon, Pesawaran Regency, Lampung', -5.34846370, 105.18042690, NOW(), NOW()),
(14, 1, 'MZFK Depok', 'Jl. Anggrek, Cinangka, Kec. Sawangan, Kota Depok', -6.38404190, 106.75903520, NOW(), NOW()),
(15, 1, 'MZ SADIQUL KHALIQ', 'Jl. Japos Raya no. 38 Pondok Jati Utara', -6.25420180, 106.71963740, NOW(), NOW()),
(16, 2, 'MZ Sabaru', 'JL. SANANG', -2.28396700, 113.96356800, NOW(), NOW()),
(17, 2, 'MZ Sampit', 'Sampit', -2.53743300, 112.95851400, NOW(), NOW()),
(18, 2, 'MZ Medang Sari', 'DESA  Medang Sari', -2.50041900, 111.68017500, NOW(), NOW()),
(19, 3, 'MZ Baru Raya', 'DS Baru Raya', -1.31485000, 115.25855400, NOW(), NOW()),
(20, 3, 'MZ Bukit sawit', 'DS Bukit sawit', -1.13637200, 114.95709800, NOW(), NOW()),
(21, 3, 'MZ Sikui', 'DS Sikui', -1.07700300, 115.08566900, NOW(), NOW()),
(22, 3, 'MZ Muara Teweh', 'Muara Teweh', -0.94629330, 114.90096100, NOW(), NOW()),
(23, 3, 'MZ Nurit', 'DS Nurit', -1.35620700, 115.23550000, NOW(), NOW());

-- Data untuk tabel `petugas_pentawajuh`
INSERT INTO `petugas_pentawajuh` (`id`, `nama`, `telepon`, `latitude`, `longitude`, `id_wilayah`, `created_at`, `updated_at`) VALUES
(1, 'Abdul Sofa SH, MH', '08130010001', -6.28325940, 106.84669160, 1, NOW(), NOW()),
(2, 'Bagindo Farizal', '08130020002', -6.23816670, 106.67644440, 1, NOW(), NOW()),
(3, 'Budi Rachmat K', '08130030003', -6.26663400, 106.66550700, 1, NOW(), NOW()),
(4, 'Defri Hamdi', '08130040004', -6.33626100, 106.73292400, 1, NOW(), NOW()),
(5, 'Drs. Subagyo, M.Si', '08130050005', -6.25641670, 106.68475000, 1, NOW(), NOW()),
(6, 'H. Boy Hendry A', '08130020006', -6.42330300, 106.76419700, 1, NOW(), NOW()),
(7, 'H. Irian Dharma S.', '08130020007', -6.21983330, 106.98858330, 1, NOW(), NOW()),
(8, 'Hikmawan P', '08130020009', -6.30753240, 106.41121300, 1, NOW(), NOW()),
(9, 'Ibnu Anggoro', '08130020010', -5.74836110, 105.68352700, 1, NOW(), NOW()),
(10, 'Ir. Moch. Rajif', '08130020011', -6.31427780, 106.68375000, 1, NOW(), NOW()),
(11, 'Janatin', '08130020012', -4.86476230, 104.57247920, 1, NOW(), NOW()),
(12, 'Mundir AR', '08130020013', -6.25234040, 106.72126570, 1, NOW(), NOW()),
(13, 'Oedy Erlangga', '08130020014', -6.32666670, 106.74611300, 1, NOW(), NOW()),
(14, 'Pilyono', '08130020015', -6.34751460, 107.34848790, 1, NOW(), NOW()),
(15, 'Puguh Wahjudi', '08130020016', -6.17740200, 106.99545290, 1, NOW(), NOW()),
(16, 'Ustd. Gugun', '08130020018', -6.39797220, 106.75911110, 1, NOW(), NOW()),
(20, 'H. Syamsuriza, M. Ag', '085200000011', -2.28626591, 113.91830921, 2, NOW(), NOW()),
(22, 'Bg Suyono', '0811223344', -2.54028000, 112.89161000, 2, NOW(), NOW()),
(23, 'Bg Nurhasim', '08112200000', -2.50028500, 111.68038800, 2, NOW(), NOW()),
(24, 'Bg Suryadi', '0811122500', -1.07700300, 114.95788800, 3, NOW(), NOW()),
(25, 'Bg Asdad', '08', -1.12637200, 114.85709800, 3, NOW(), NOW()),
(26, 'Bg Burhan', '08', -1.09700300, 115.07566900, 3, NOW(), NOW());

-- Data untuk tabel `user`
INSERT INTO `user` (`id`, `username`, `password`, `role`, `created_at`, `updated_at`) VALUES
(1, 'administrator', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NOW(), NOW());