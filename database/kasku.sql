-- Host: 127.0.0.1
-- Waktu pembuatan: 20 Nov 2025 pada 06.35
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
--
-- Database: `kasku`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin`
--

CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `foto_profil` varchar(255) DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `admin`
--

INSERT INTO `admin` (`id_admin`, `username`, `email`, `password`, `nama_lengkap`, `no_telepon`, `foto_profil`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@kasku.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', NULL, NULL, 'aktif', '2025-11-06 06:05:15', '2025-11-06 06:05:15');

-- --------------------------------------------------------

--
-- Struktur dari tabel `anggota`
--

CREATE TABLE `anggota` (
  `id_anggota` int(11) NOT NULL,
  `id_kelas` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `tanggal_bergabung` date NOT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `iuran`
--

CREATE TABLE `iuran` (
  `id_iuran` int(11) NOT NULL,
  `id_kelas` int(11) NOT NULL,
  `nama_iuran` varchar(100) NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `periode` enum('harian','mingguan','bulanan','semesteran','tahunan') NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kas`
--

CREATE TABLE `kas` (
  `id_kas` int(11) NOT NULL,
  `id_kelas` int(11) NOT NULL,
  `saldo` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `kas`
--

INSERT INTO `kas` (`id_kas`, `id_kelas`, `saldo`, `created_at`, `updated_at`) VALUES
(1, 15, 19500.00, '2025-11-20 04:52:27', '2025-11-20 05:24:09');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kelas`
--

CREATE TABLE `kelas` (
  `id_kelas` int(11) NOT NULL,
  `id_admin` int(11) NOT NULL,
  `nama_kelas` varchar(100) NOT NULL,
  `kode_kelas` varchar(10) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `tahun_ajaran` varchar(20) DEFAULT NULL,
  `semester` enum('ganjil','genap') DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `kelas`
--

INSERT INTO `kelas` (`id_kelas`, `id_admin`, `nama_kelas`, `kode_kelas`, `deskripsi`, `tahun_ajaran`, `semester`, `status`, `created_at`, `updated_at`) VALUES
(15, 1, 'kelas homok', 'I6C11J', 'homk', '2025', 'ganjil', 'aktif', '2025-11-20 03:52:23', '2025-11-20 03:52:23');

-- --------------------------------------------------------

--
-- Struktur dari tabel `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id_log` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `id_admin` int(11) DEFAULT NULL,
  `aktivitas` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id_notifikasi` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `id_admin` int(11) DEFAULT NULL,
  `judul` varchar(200) NOT NULL,
  `pesan` text NOT NULL,
  `tipe` enum('info','warning','success','danger') DEFAULT 'info',
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pembayaran_iuran`
--

CREATE TABLE `pembayaran_iuran` (
  `id_pembayaran` int(11) NOT NULL,
  `id_iuran` int(11) NOT NULL,
  `id_anggota` int(11) NOT NULL,
  `id_transaksi` int(11) DEFAULT NULL,
  `jumlah_dibayar` decimal(15,2) NOT NULL,
  `tanggal_bayar` date NOT NULL,
  `bulan_periode` varchar(20) DEFAULT NULL,
  `status` enum('lunas','belum_lunas','terlambat') DEFAULT 'belum_lunas',
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi`
--

CREATE TABLE `transaksi` (
  `id_transaksi` int(11) NOT NULL,
  `id_kas` int(11) NOT NULL,
  `jenis` enum('pemasukan','pengeluaran') NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `tanggal` date NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `bukti_transaksi` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `transaksi`
--

INSERT INTO `transaksi` (`id_transaksi`, `id_kas`, `jenis`, `jumlah`, `tanggal`, `deskripsi`, `kategori`, `bukti_transaksi`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'pemasukan', 9500.00, '2025-11-06', 'homok banget sih', 'pengeluaran', 'eeee', 1, '2025-11-20 04:53:49', '2025-11-20 04:53:49');

--
-- Trigger `transaksi`
--
DELIMITER $$
CREATE TRIGGER `after_transaksi_delete` AFTER DELETE ON `transaksi` FOR EACH ROW BEGIN
    IF OLD.jenis = 'pemasukan' THEN
        UPDATE `kas` SET `saldo` = `saldo` - OLD.jumlah WHERE `id_kas` = OLD.id_kas;
    ELSE
        UPDATE `kas` SET `saldo` = `saldo` + OLD.jumlah WHERE `id_kas` = OLD.id_kas;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_transaksi_insert` AFTER INSERT ON `transaksi` FOR EACH ROW BEGIN
    IF NEW.jenis = 'pemasukan' THEN
        UPDATE `kas` SET `saldo` = `saldo` + NEW.jumlah WHERE `id_kas` = NEW.id_kas;
    ELSE
        UPDATE `kas` SET `saldo` = `saldo` - NEW.jumlah WHERE `id_kas` = NEW.id_kas;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `foto_profil` varchar(255) DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `user`
--

INSERT INTO `user` (`id_user`, `username`, `email`, `password`, `nama_lengkap`, `no_telepon`, `foto_profil`, `status`, `created_at`, `updated_at`) VALUES
(1, 'user1', 'user1@kasku.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User Satu', NULL, NULL, 'aktif', '2025-11-06 06:05:15', '2025-11-06 06:05:15'),
(2, 'user2', 'user2@kasku.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User Dua', NULL, NULL, 'aktif', '2025-11-06 06:05:15', '2025-11-06 06:05:15'),
(3, 'vay', 'vay@gmail.com', '$2y$10$dhoe6hk24O0jle.HQxIY1OUe8.LNqND451AJmjwQJUOeVjbaBQHcu', NULL, NULL, NULL, 'aktif', '2025-11-06 13:30:36', '2025-11-06 13:30:36');

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `v_ringkasan_kelas`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `v_ringkasan_kelas` (
`id_kelas` int(11)
,`nama_kelas` varchar(100)
,`kode_kelas` varchar(10)
,`tahun_ajaran` varchar(20)
,`admin_username` varchar(50)
,`admin_nama` varchar(100)
,`saldo` decimal(15,2)
,`jumlah_anggota` bigint(21)
,`jumlah_transaksi` bigint(21)
,`status` enum('aktif','nonaktif')
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `v_transaksi_detail`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `v_transaksi_detail` (
`id_transaksi` int(11)
,`jenis` enum('pemasukan','pengeluaran')
,`jumlah` decimal(15,2)
,`tanggal` date
,`deskripsi` text
,`kategori` varchar(50)
,`nama_kelas` varchar(100)
,`kode_kelas` varchar(10)
,`saldo_sekarang` decimal(15,2)
,`admin_username` varchar(50)
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Struktur untuk view `v_ringkasan_kelas`
--
DROP TABLE IF EXISTS `v_ringkasan_kelas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_ringkasan_kelas`  AS SELECT `k`.`id_kelas` AS `id_kelas`, `k`.`nama_kelas` AS `nama_kelas`, `k`.`kode_kelas` AS `kode_kelas`, `k`.`tahun_ajaran` AS `tahun_ajaran`, `a`.`username` AS `admin_username`, `a`.`nama_lengkap` AS `admin_nama`, `kas`.`saldo` AS `saldo`, count(distinct `ang`.`id_user`) AS `jumlah_anggota`, count(distinct `t`.`id_transaksi`) AS `jumlah_transaksi`, `k`.`status` AS `status`, `k`.`created_at` AS `created_at` FROM ((((`kelas` `k` left join `admin` `a` on(`k`.`id_admin` = `a`.`id_admin`)) left join `kas` on(`k`.`id_kelas` = `kas`.`id_kelas`)) left join `anggota` `ang` on(`k`.`id_kelas` = `ang`.`id_kelas` and `ang`.`status` = 'aktif')) left join `transaksi` `t` on(`kas`.`id_kas` = `t`.`id_kas`)) GROUP BY `k`.`id_kelas` ;

-- --------------------------------------------------------

--
-- Struktur untuk view `v_transaksi_detail`
--
DROP TABLE IF EXISTS `v_transaksi_detail`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_transaksi_detail`  AS SELECT `t`.`id_transaksi` AS `id_transaksi`, `t`.`jenis` AS `jenis`, `t`.`jumlah` AS `jumlah`, `t`.`tanggal` AS `tanggal`, `t`.`deskripsi` AS `deskripsi`, `t`.`kategori` AS `kategori`, `k`.`nama_kelas` AS `nama_kelas`, `k`.`kode_kelas` AS `kode_kelas`, `kas`.`saldo` AS `saldo_sekarang`, `a`.`username` AS `admin_username`, `t`.`created_at` AS `created_at` FROM (((`transaksi` `t` join `kas` on(`t`.`id_kas` = `kas`.`id_kas`)) join `kelas` `k` on(`kas`.`id_kelas` = `k`.`id_kelas`)) join `admin` `a` on(`k`.`id_admin` = `a`.`id_admin`)) ORDER BY `t`.`created_at` DESC ;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_status` (`status`);

--
-- Indeks untuk tabel `anggota`
--
ALTER TABLE `anggota`
  ADD PRIMARY KEY (`id_anggota`),
  ADD UNIQUE KEY `unique_member` (`id_kelas`,`id_user`),
  ADD KEY `id_kelas` (`id_kelas`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_anggota_kelas_user` (`id_kelas`,`id_user`);

--
-- Indeks untuk tabel `iuran`
--
ALTER TABLE `iuran`
  ADD PRIMARY KEY (`id_iuran`),
  ADD KEY `id_kelas` (`id_kelas`),
  ADD KEY `idx_status` (`status`);

--
-- Indeks untuk tabel `kas`
--
ALTER TABLE `kas`
  ADD PRIMARY KEY (`id_kas`),
  ADD UNIQUE KEY `id_kelas` (`id_kelas`);

--
-- Indeks untuk tabel `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id_kelas`),
  ADD UNIQUE KEY `kode_kelas` (`kode_kelas`),
  ADD KEY `id_admin` (`id_admin`),
  ADD KEY `idx_status` (`status`);

--
-- Indeks untuk tabel `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `idx_user` (`id_user`),
  ADD KEY `idx_admin` (`id_admin`);

--
-- Indeks untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id_notifikasi`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_admin` (`id_admin`),
  ADD KEY `idx_is_read` (`is_read`);

--
-- Indeks untuk tabel `pembayaran_iuran`
--
ALTER TABLE `pembayaran_iuran`
  ADD PRIMARY KEY (`id_pembayaran`),
  ADD KEY `id_iuran` (`id_iuran`),
  ADD KEY `id_anggota` (`id_anggota`),
  ADD KEY `id_transaksi` (`id_transaksi`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_pembayaran_status` (`status`,`tanggal_bayar`);

--
-- Indeks untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `id_kas` (`id_kas`),
  ADD KEY `idx_jenis` (`jenis`),
  ADD KEY `idx_tanggal` (`tanggal`),
  ADD KEY `idx_kategori` (`kategori`),
  ADD KEY `idx_transaksi_created` (`created_at`);

--
-- Indeks untuk tabel `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `anggota`
--
ALTER TABLE `anggota`
  MODIFY `id_anggota` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `iuran`
--
ALTER TABLE `iuran`
  MODIFY `id_iuran` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `kas`
--
ALTER TABLE `kas`
  MODIFY `id_kas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id_kelas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id_notifikasi` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pembayaran_iuran`
--
ALTER TABLE `pembayaran_iuran`
  MODIFY `id_pembayaran` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id_transaksi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `anggota`
--
ALTER TABLE `anggota`
  ADD CONSTRAINT `fk_anggota_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_anggota_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `iuran`
--
ALTER TABLE `iuran`
  ADD CONSTRAINT `fk_iuran_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `kas`
--
ALTER TABLE `kas`
  ADD CONSTRAINT `fk_kas_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `kelas`
--
ALTER TABLE `kelas`
  ADD CONSTRAINT `fk_kelas_admin` FOREIGN KEY (`id_admin`) REFERENCES `admin` (`id_admin`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD CONSTRAINT `fk_log_admin` FOREIGN KEY (`id_admin`) REFERENCES `admin` (`id_admin`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD CONSTRAINT `fk_notif_admin` FOREIGN KEY (`id_admin`) REFERENCES `admin` (`id_admin`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pembayaran_iuran`
--
ALTER TABLE `pembayaran_iuran`
  ADD CONSTRAINT `fk_pembayaran_anggota` FOREIGN KEY (`id_anggota`) REFERENCES `anggota` (`id_anggota`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pembayaran_iuran` FOREIGN KEY (`id_iuran`) REFERENCES `iuran` (`id_iuran`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pembayaran_transaksi` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id_transaksi`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `fk_transaksi_kas` FOREIGN KEY (`id_kas`) REFERENCES `kas` (`id_kas`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
