/*
 Navicat Premium Dump SQL

 Source Server         : Local
 Source Server Type    : MySQL
 Source Server Version : 80200 (8.2.0)
 Source Host           : localhost:3306
 Source Schema         : if0_38392208_kehadiran

 Target Server Type    : MySQL
 Target Server Version : 80200 (8.2.0)
 File Encoding         : 65001

 Date: 19/03/2025 08:35:19
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for tbl_absensi
-- ----------------------------
DROP TABLE IF EXISTS `tbl_absensi`;
CREATE TABLE `tbl_absensi`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `tanggal` date NOT NULL,
  `waktu_masuk` datetime NOT NULL,
  `waktu_keluar` datetime NULL DEFAULT NULL,
  `latitude` decimal(10, 8) NOT NULL,
  `longitude` decimal(11, 8) NOT NULL,
  `foto_path` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `created_at` datetime NULL DEFAULT CURRENT_TIMESTAMP,
  `shift` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `tanggal_shift` date NOT NULL,
  `latitude_keluar` decimal(10, 8) NULL DEFAULT NULL,
  `longitude_keluar` decimal(11, 8) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `idx_tanggal`(`tanggal`) USING BTREE,
  INDEX `idx_shift`(`shift`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE,
  INDEX `idx_waktu_masuk`(`waktu_masuk`) USING BTREE,
  INDEX `idx_tanggal_shift`(`tanggal`, `shift`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 2 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of tbl_absensi
-- ----------------------------

-- ----------------------------
-- Table structure for tbm_jam_shift
-- ----------------------------
DROP TABLE IF EXISTS `tbm_jam_shift`;
CREATE TABLE `tbm_jam_shift`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `shift` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of tbm_jam_shift
-- ----------------------------

-- ----------------------------
-- Table structure for tbm_lokasi
-- ----------------------------
DROP TABLE IF EXISTS `tbm_lokasi`;
CREATE TABLE `tbm_lokasi`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_lokasi` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `latitude` decimal(10, 8) NOT NULL,
  `longitude` decimal(11, 8) NOT NULL,
  `radius` int NOT NULL,
  `created_at` datetime NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 2 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of tbm_lokasi
-- ----------------------------

-- ----------------------------
-- Table structure for tbm_users
-- ----------------------------
DROP TABLE IF EXISTS `tbm_users`;
CREATE TABLE `tbm_users`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `email` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `password` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `nomor_telepon` varchar(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `role` enum('admin','user') CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT 'user',
  `shift_id` int NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `email`(`email` ASC) USING BTREE,
  INDEX `shift_id`(`shift_id` ASC) USING BTREE,
  CONSTRAINT `tbm_users_ibfk_1` FOREIGN KEY (`shift_id`) REFERENCES `tbm_jam_shift` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `tbm_users_ibfk_2` FOREIGN KEY (`shift_id`) REFERENCES `tbm_jam_shift` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of tbm_users
-- ----------------------------
INSERT INTO `tbm_users` VALUES (1, 'Super User', 'super@user.com', '$2y$10$hYAvgxylPL5U/VcTkri5lu0601Sh1tqU8IRI1uc8OmFtlWiXe.1Di', NULL, 'admin', NULL, '2025-02-26 06:42:26');

SET FOREIGN_KEY_CHECKS = 1;
