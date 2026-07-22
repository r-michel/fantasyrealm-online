SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


DROP TABLE IF EXISTS `character`;
CREATE TABLE `character` (
  `id` int NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `skin_color` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `eye_color` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `eye_shape` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nose_shape` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mouth_shape` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shared` tinyint NOT NULL,
  `authorized` tinyint NOT NULL,
  `image` longblob,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `owner_id` int NOT NULL,
  `hair_color` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `public_id` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `comment`;
CREATE TABLE `comment` (
  `id` int NOT NULL,
  `rate` int NOT NULL,
  `comment` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `published` tinyint NOT NULL,
  `owner_id` int NOT NULL,
  `on_character_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `doctrine_migration_versions`;
CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `equipment`;
CREATE TABLE `equipment` (
  `id` int NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` tinyint NOT NULL,
  `image` longblob,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `category_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `equipment_category`;
CREATE TABLE `equipment_category` (
  `id` int NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `equipment_character`;
CREATE TABLE `equipment_character` (
  `equipment_id` int NOT NULL,
  `character_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `reset_password_request`;
CREATE TABLE `reset_password_request` (
  `id` int NOT NULL,
  `selector` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hashed_token` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `requested_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `user_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int NOT NULL,
  `username` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `roles` json NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_verified` tinyint NOT NULL,
  `suspended` tinyint NOT NULL,
  `must_update_password` tinyint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `character`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_937AB0345E237E06` (`name`),
  ADD UNIQUE KEY `UNIQ_937AB034B5B48B91` (`public_id`),
  ADD KEY `IDX_937AB0347E3C61F9` (`owner_id`);

ALTER TABLE `comment`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_COMMENT_OWNER_CHARACTER` (`owner_id`,`on_character_id`),
  ADD KEY `IDX_9474526C7E3C61F9` (`owner_id`),
  ADD KEY `IDX_9474526CC23AC56E` (`on_character_id`);

ALTER TABLE `doctrine_migration_versions`
  ADD PRIMARY KEY (`version`);

ALTER TABLE `equipment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_D338D58312469DE2` (`category_id`);

ALTER TABLE `equipment_category`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_368F9DE777153098` (`code`);

ALTER TABLE `equipment_character`
  ADD PRIMARY KEY (`equipment_id`,`character_id`),
  ADD KEY `IDX_4147F64D517FE9FE` (`equipment_id`),
  ADD KEY `IDX_4147F64D1136BE75` (`character_id`);

ALTER TABLE `reset_password_request`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_7CE748AA76ED395` (`user_id`);

ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_8D93D649E7927C74` (`email`),
  ADD UNIQUE KEY `UNIQ_IDENTIFIER_USERNAME` (`username`);


ALTER TABLE `character`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `comment`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `equipment`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `equipment_category`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `reset_password_request`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `user`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;


ALTER TABLE `character`
  ADD CONSTRAINT `FK_937AB0347E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`);

ALTER TABLE `comment`
  ADD CONSTRAINT `FK_9474526C7E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_9474526CC23AC56E` FOREIGN KEY (`on_character_id`) REFERENCES `character` (`id`);

ALTER TABLE `equipment`
  ADD CONSTRAINT `FK_D338D58312469DE2` FOREIGN KEY (`category_id`) REFERENCES `equipment_category` (`id`);

ALTER TABLE `equipment_character`
  ADD CONSTRAINT `FK_4147F64D1136BE75` FOREIGN KEY (`character_id`) REFERENCES `character` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_4147F64D517FE9FE` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE;

ALTER TABLE `reset_password_request`
  ADD CONSTRAINT `FK_7CE748AA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
