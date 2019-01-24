-- phpMyAdmin SQL Dump
-- version 4.6.6deb4
-- https://www.phpmyadmin.net/
--
-- Client :  localhost
-- Généré le :  Jeu 24 Janvier 2019 à 09:56
-- Version du serveur :  10.1.37-MariaDB-0+deb9u1
-- Version de PHP :  5.6.39-1+0~20181212060557.8+stretch~1.gbp4260ff

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données :  `authentication_geosamples`
--
CREATE DATABASE IF NOT EXISTS `authentication_geosamples` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `authentication_geosamples`;

-- --------------------------------------------------------

--
-- Structure de la table `lost_password`
--

CREATE TABLE `lost_password` (
  `mail` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `datetime` datetime NOT NULL,
  `created_at` date NOT NULL,
  `updated_at` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `mail_validation`
--

CREATE TABLE `mail_validation` (
  `mail` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `datetime` datetime NOT NULL,
  `created_at` date NOT NULL,
  `updated_at` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `Projects`
--

CREATE TABLE `Projects` (
  `id` int(10) NOT NULL,
  `name` varchar(25) NOT NULL,
  `updated_at` date NOT NULL,
  `created_at` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `Projects_access_right`
--

CREATE TABLE `Projects_access_right` (
  `id` int(10) NOT NULL,
  `id_project` int(10) NOT NULL,
  `id_user` int(10) NOT NULL,
  `updated_at` date NOT NULL,
  `created_at` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `Projects_request`
--

CREATE TABLE `Projects_request` (
  `id` int(11) NOT NULL,
  `id_project` int(12) NOT NULL,
  `id_user` int(11) NOT NULL,
  `created_at` date NOT NULL,
  `updated_at` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id_user` int(10) NOT NULL,
  `name` varchar(40) NOT NULL,
  `firstname` varchar(40) NOT NULL,
  `mail` varchar(255) NOT NULL,
  `mdp` varchar(256) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `mail_validation` tinyint(1) NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL,
  `created_at` date NOT NULL,
  `updated_at` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Contenu de la table `users`
--

INSERT INTO `users` (`id_user`, `name`, `firstname`, `mail`, `mdp`, `status`, `mail_validation`, `type`, `created_at`, `updated_at`) VALUES
(1, 'Default', 'Admin', 'admin@geosample.fr', '$2y$10$btJ26/lTLfigFowkSQ.tWeCec3vBoXz7oE3ar.ZQuQ72p39p6b0RG', 1, 1, 1, '2019-01-09', '2019-01-24');

--
-- Index pour les tables exportées
--

--
-- Index pour la table `lost_password`
--
ALTER TABLE `lost_password`
  ADD PRIMARY KEY (`mail`);

--
-- Index pour la table `mail_validation`
--
ALTER TABLE `mail_validation`
  ADD PRIMARY KEY (`mail`);

--
-- Index pour la table `Projects`
--
ALTER TABLE `Projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Index pour la table `Projects_access_right`
--
ALTER TABLE `Projects_access_right`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `Projects_request`
--
ALTER TABLE `Projects_request`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`mail`),
  ADD UNIQUE KEY `id_user` (`id_user`);

--
-- AUTO_INCREMENT pour les tables exportées
--

--
-- AUTO_INCREMENT pour la table `Projects`
--
ALTER TABLE `Projects`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `Projects_access_right`
--
ALTER TABLE `Projects_access_right`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `Projects_request`
--
ALTER TABLE `Projects_request`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
DELIMITER $$
--
-- Événements
--
CREATE DEFINER=`root`@`localhost` EVENT `remove_mail_token` ON SCHEDULE EVERY 30 MINUTE STARTS '2017-11-21 16:03:14' ON COMPLETION NOT PRESERVE ENABLE DO DELETE FROM mail_validation WHERE `datetime` < (NOW() - INTERVAL 30 MINUTE)$$

CREATE DEFINER=`root`@`localhost` EVENT `remove_invalid_user` ON SCHEDULE EVERY 1 HOUR STARTS '2017-11-21 16:03:14' ON COMPLETION NOT PRESERVE ENABLE DO DELETE FROM users WHERE `mail_validation`=0$$

CREATE DEFINER=`root`@`localhost` EVENT `remove_password_token` ON SCHEDULE EVERY 30 MINUTE STARTS '2017-11-21 16:03:14' ON COMPLETION NOT PRESERVE ENABLE DO DELETE FROM lost_password WHERE `datetime` < (NOW() - INTERVAL 30 MINUTE)$$

DELIMITER ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
