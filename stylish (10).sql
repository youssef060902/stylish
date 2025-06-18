-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mar. 17 juin 2025 à 19:08
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `stylish`
--

-- --------------------------------------------------------

--
-- Structure de la table `admin`
--

CREATE TABLE `admin` (
  `id` int(2) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `admin`
--

INSERT INTO `admin` (`id`, `email`, `password`) VALUES
(1, 'mohamedyoussefazzouz@gmail.com', 'admin123');

-- --------------------------------------------------------

--
-- Structure de la table `avis`
--

CREATE TABLE `avis` (
  `id` int(11) NOT NULL,
  `id_produit` int(11) NOT NULL,
  `id_user` int(5) NOT NULL,
  `note` int(1) NOT NULL CHECK (`note` between 1 and 5),
  `commentaire` text DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
  `date_modification` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `favoris`
--

CREATE TABLE `favoris` (
  `id_user` int(5) NOT NULL,
  `id_produit` int(11) NOT NULL,
  `date_ajout` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `images_produits`
--

CREATE TABLE `images_produits` (
  `id` int(11) NOT NULL,
  `id_produit` int(11) DEFAULT NULL,
  `URL_Image` varchar(255) NOT NULL,
  `Legende` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `images_produits`
--

INSERT INTO `images_produits` (`id`, `id_produit`, `URL_Image`, `Legende`) VALUES
(108, 50, 'http://localhost/img/68488e54623fc_Capture d\'écran 2025-06-07 222039.png', NULL),
(112, 55, 'http://localhost/img/6848b049d77d7_Capture d\'écran 2025-06-07 222039.png', NULL),
(113, 55, 'http://localhost/img/6848b049d8033_Capture d\'écran 2025-06-07 222547.png', NULL),
(114, 54, 'http://localhost/img/6848ca51bce58_Capture d\'écran 2025-06-09 142902.png', NULL),
(115, 53, 'http://localhost/img/6849f322e5386_Capture d\'écran 2025-06-09 142902.png', NULL),
(116, 52, 'http://localhost/img/684a186fbcaf0_Capture d\'écran 2025-06-09 142902.png', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `panier`
--

CREATE TABLE `panier` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_produit` int(11) NOT NULL,
  `quantite` int(11) DEFAULT 1,
  `id_pointure` int(11) DEFAULT NULL,
  `date_ajout` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `pointures`
--

CREATE TABLE `pointures` (
  `id` int(2) NOT NULL,
  `pointure` int(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `pointures`
--

INSERT INTO `pointures` (`id`, `pointure`) VALUES
(1, 22),
(2, 23),
(3, 24),
(4, 25),
(5, 26),
(6, 27),
(7, 28),
(8, 29),
(9, 30),
(10, 31),
(11, 32),
(12, 33),
(13, 34),
(14, 35),
(15, 36),
(16, 37),
(17, 38),
(18, 39),
(19, 40),
(20, 41),
(21, 42),
(22, 43),
(23, 44);

-- --------------------------------------------------------

--
-- Structure de la table `pointure_produit`
--

CREATE TABLE `pointure_produit` (
  `id_produit` int(3) NOT NULL,
  `id_pointure` int(2) NOT NULL,
  `stock` int(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `pointure_produit`
--

INSERT INTO `pointure_produit` (`id_produit`, `id_pointure`, `stock`) VALUES
(50, 23, 4),
(52, 15, 5),
(52, 16, 10),
(52, 17, 10),
(52, 18, 5),
(53, 5, 10),
(53, 6, 10),
(53, 7, 10),
(53, 8, 10),
(55, 15, 10),
(55, 16, 15),
(55, 17, 10),
(54, 19, 5),
(54, 20, 10),
(54, 21, 10);

-- --------------------------------------------------------

--
-- Structure de la table `produit`
--

CREATE TABLE `produit` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `marque` varchar(26) NOT NULL,
  `catégorie` enum('homme','femme','enfant') NOT NULL,
  `type` enum('running','casual') NOT NULL,
  `couleur` varchar(33) NOT NULL,
  `description` varchar(211) NOT NULL,
  `statut` enum('en stock','en promotion','rupture de stock') NOT NULL,
  `prix` float NOT NULL,
  `quantité` int(19) NOT NULL,
  `date_ajout` datetime NOT NULL,
  `date_modification` datetime DEFAULT NULL,
  `id_promotion` int(3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `produit`
--

INSERT INTO `produit` (`id`, `nom`, `marque`, `catégorie`, `type`, `couleur`, `description`, `statut`, `prix`, `quantité`, `date_ajout`, `date_modification`, `id_promotion`) VALUES
(50, 'Airforce', 'adidas', 'homme', 'running', 'bleu', 'DD', 'en stock', 100, 4, '2025-06-10 20:58:12', '2025-06-10 22:31:20', NULL),
(52, 'Classic', 'Reebok', 'femme', 'casual', 'blanc', 'Chaussures casual classiques pour femme', 'en stock', 75, 30, '2025-06-11 10:05:00', '2025-06-13 15:39:44', NULL),
(53, 'Superstar', 'adidas', 'enfant', 'casual', 'bleu', 'Chaussures pour enfants style casual', 'en stock', 60, 40, '2025-06-11 10:10:00', '2025-06-13 15:48:26', NULL),
(54, 'Air Max', 'Nike', 'homme', 'running', 'Rouge', 'Chaussures de running avec amorti Air Max', 'en promotion', 40, 25, '2025-06-11 10:15:00', '2025-06-14 18:50:34', 18),
(55, 'Chuck Taylor', 'Converse', 'femme', 'casual', 'noir', 'Baskets classiques pour femme', 'en stock', 65, 35, '2025-06-11 10:20:00', '2025-06-14 18:47:20', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `promotion`
--

CREATE TABLE `promotion` (
  `id` int(3) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `description` varchar(50) DEFAULT NULL,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime NOT NULL,
  `discount` int(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `promotion`
--

INSERT INTO `promotion` (`id`, `nom`, `description`, `date_debut`, `date_fin`, `discount`) VALUES
(13, 'Été Soldes', 'Promotion spéciale été', '2025-08-01 00:00:00', '2025-08-14 23:59:00', 30),
(14, 'Back to School', 'Préparation rentrée scolaire', '2025-08-15 00:00:00', '2025-09-15 23:59:59', 25),
(15, 'Noël', 'Promotion de Noël', '2025-12-01 00:00:00', '2025-12-31 23:59:59', 40),
(16, 'Nouvel An', 'Promotion spéciale Nouvel An', '2025-12-26 00:00:00', '2026-01-15 23:59:59', 20),
(17, 'Black Friday', 'Super promotions Black Friday', '2025-11-25 00:00:00', '2025-11-30 23:59:59', 50),
(18, 'black friday', 'C\'est Black Friday', '2025-06-12 15:51:00', '2025-06-21 00:47:00', 60);

-- --------------------------------------------------------

--
-- Structure de la table `reclamation`
--

CREATE TABLE `reclamation` (
  `id` int(11) NOT NULL,
  `id_user` int(5) NOT NULL,
  `id_produit` int(11) DEFAULT NULL,
  `type` enum('produit','livraison','service','paiement','autre') NOT NULL,
  `description` text NOT NULL,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
  `date_modification` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `statut` enum('nouveau','en cours','résolu','fermé') NOT NULL DEFAULT 'nouveau'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

CREATE TABLE `user` (
  `id` int(5) NOT NULL,
  `prenom` varchar(20) NOT NULL,
  `nom` varchar(26) NOT NULL,
  `genre` varchar(12) NOT NULL,
  `date_naissance` date NOT NULL,
  `age` int(3) NOT NULL,
  `phone` varchar(19) NOT NULL,
  `adresse` varchar(44) NOT NULL,
  `email` varchar(33) NOT NULL,
  `password` varchar(33) NOT NULL,
  `image` varchar(211) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `user`
--

INSERT INTO `user` (`id`, `prenom`, `nom`, `genre`, `date_naissance`, `age`, `phone`, `adresse`, `email`, `password`, `image`) VALUES
(89, 'Mohamed Youssef', 'Azzouz', 'homme', '2000-02-02', 25, '25556300', 'Carthage', 'mohamedyoussefazzouz@gmail.com', 'youssef', 'http://localhost/img/user_684f0be930d8f.jpg'),
(90, 'Med Youssef', 'Azzouz', 'homme', '2000-10-10', 24, '26556300', 'Carthage ', 'youssefcarma@gmail.com', 'youssef', 'http://localhost/img/user_6851739b1e691.jpg');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `avis`
--
ALTER TABLE `avis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_avis_produit` (`id_produit`),
  ADD KEY `fk_avis_user` (`id_user`);

--
-- Index pour la table `favoris`
--
ALTER TABLE `favoris`
  ADD PRIMARY KEY (`id_user`,`id_produit`),
  ADD KEY `fk_favoris_produit` (`id_produit`);

--
-- Index pour la table `images_produits`
--
ALTER TABLE `images_produits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `images_produits_ibfk_1` (`id_produit`);

--
-- Index pour la table `panier`
--
ALTER TABLE `panier`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_produit` (`id_produit`),
  ADD KEY `id_pointure` (`id_pointure`);

--
-- Index pour la table `pointures`
--
ALTER TABLE `pointures`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `pointure_produit`
--
ALTER TABLE `pointure_produit`
  ADD KEY `fk_produit` (`id_produit`),
  ADD KEY `fk_pointire` (`id_pointure`);

--
-- Index pour la table `produit`
--
ALTER TABLE `produit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_promotion` (`id_promotion`);

--
-- Index pour la table `promotion`
--
ALTER TABLE `promotion`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `reclamation`
--
ALTER TABLE `reclamation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reclamation_user` (`id_user`),
  ADD KEY `fk_reclamation_produit` (`id_produit`);

--
-- Index pour la table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `indexx` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(2) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `avis`
--
ALTER TABLE `avis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `images_produits`
--
ALTER TABLE `images_produits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- AUTO_INCREMENT pour la table `panier`
--
ALTER TABLE `panier`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `pointures`
--
ALTER TABLE `pointures`
  MODIFY `id` int(2) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT pour la table `produit`
--
ALTER TABLE `produit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT pour la table `promotion`
--
ALTER TABLE `promotion`
  MODIFY `id` int(3) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pour la table `reclamation`
--
ALTER TABLE `reclamation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `avis`
--
ALTER TABLE `avis`
  ADD CONSTRAINT `fk_avis_produit` FOREIGN KEY (`id_produit`) REFERENCES `produit` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_avis_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `favoris`
--
ALTER TABLE `favoris`
  ADD CONSTRAINT `fk_favoris_produit` FOREIGN KEY (`id_produit`) REFERENCES `produit` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_favoris_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `images_produits`
--
ALTER TABLE `images_produits`
  ADD CONSTRAINT `images_produits_ibfk_1` FOREIGN KEY (`id_produit`) REFERENCES `produit` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `panier`
--
ALTER TABLE `panier`
  ADD CONSTRAINT `panier_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `panier_ibfk_2` FOREIGN KEY (`id_produit`) REFERENCES `produit` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `panier_ibfk_3` FOREIGN KEY (`id_pointure`) REFERENCES `pointures` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `pointure_produit`
--
ALTER TABLE `pointure_produit`
  ADD CONSTRAINT `fk_pointire` FOREIGN KEY (`id_pointure`) REFERENCES `pointures` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_produit` FOREIGN KEY (`id_produit`) REFERENCES `produit` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `produit`
--
ALTER TABLE `produit`
  ADD CONSTRAINT `fk_promotion` FOREIGN KEY (`id_promotion`) REFERENCES `promotion` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `reclamation`
--
ALTER TABLE `reclamation`
  ADD CONSTRAINT `fk_reclamation_produit` FOREIGN KEY (`id_produit`) REFERENCES `produit` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reclamation_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

DELIMITER $$
--
-- Évènements
--
CREATE DEFINER=`root`@`localhost` EVENT `auto_check_expired_promotions` ON SCHEDULE EVERY 1 MINUTE STARTS '2025-06-10 02:13:20' ON COMPLETION PRESERVE ENABLE DO BEGIN
    UPDATE produit p
    JOIN promotion pr ON p.id_promotion = pr.id
    SET p.prix = p.prix / (1 - pr.discount / 100),
        p.id_promotion = NULL,
        p.statut = 'en stock',
        p.date_modification = NOW()
    WHERE pr.date_fin < NOW()
    AND p.statut = 'en promotion';
END$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
SET GLOBAL event_scheduler = ON;
SHOW EVENTS FROM stylish;
SHOW VARIABLES LIKE 'event_scheduler';