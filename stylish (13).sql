-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : dim. 22 juin 2025 à 05:06
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

--
-- Déchargement des données de la table `avis`
--

INSERT INTO `avis` (`id`, `id_produit`, `id_user`, `note`, `commentaire`, `date_creation`, `date_modification`) VALUES
(16, 54, 96, 3, 'moyen', '2025-06-21 21:14:49', '2025-06-21 21:15:52'),
(17, 50, 90, 4, 'vvvvv', '2025-06-21 21:42:42', NULL),
(18, 54, 90, 4, 'hhh', '2025-06-21 21:43:39', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `commande`
--

CREATE TABLE `commande` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `date_commande` datetime NOT NULL DEFAULT current_timestamp(),
  `total` decimal(10,2) NOT NULL,
  `statut` enum('en attente','confirmé','en cours','livré') NOT NULL DEFAULT 'en attente',
  `adresse_livraison` varchar(255) NOT NULL,
  `date_livraison` datetime DEFAULT NULL,
  `confirmation_token` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `commande`
--

INSERT INTO `commande` (`id`, `id_user`, `date_commande`, `total`, `statut`, `adresse_livraison`, `date_livraison`, `confirmation_token`) VALUES
(25, 90, '2025-06-21 17:44:23', 37.00, 'en cours', 'Carthage', NULL, NULL),
(26, 90, '2025-06-21 19:08:29', 87.00, 'confirmé', 'Carthage', NULL, NULL),
(27, 90, '2025-06-21 20:37:33', 207.00, 'confirmé', 'Carthage', NULL, NULL),
(28, 96, '2025-06-22 01:28:27', 19.00, 'confirmé', 'Carthage', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `commande_produit`
--

CREATE TABLE `commande_produit` (
  `id_commande` int(11) NOT NULL,
  `id_produit` int(11) NOT NULL,
  `id_pointure` int(11) NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `quantite` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `commande_produit`
--

INSERT INTO `commande_produit` (`id_commande`, `id_produit`, `id_pointure`, `prix_unitaire`, `quantite`) VALUES
(25, 52, 15, 30.00, 1),
(26, 57, 22, 100.00, 1),
(27, 50, 23, 100.00, 2),
(27, 54, 19, 100.00, 3),
(28, 52, 18, 30.00, 1);

-- --------------------------------------------------------

--
-- Structure de la table `coupon`
--

CREATE TABLE `coupon` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount` int(11) NOT NULL,
  `statut` enum('active','inactive') NOT NULL DEFAULT 'inactive'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `coupon`
--

INSERT INTO `coupon` (`id`, `code`, `discount`, `statut`) VALUES
(1, 'WELCOME10', 10, 'inactive'),
(2, 'ETE2025', 20, 'active'),
(3, 'BLACK60', 60, 'active');

-- --------------------------------------------------------

--
-- Structure de la table `favoris`
--

CREATE TABLE `favoris` (
  `id_user` int(5) NOT NULL,
  `id_produit` int(11) NOT NULL,
  `date_ajout` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `favoris`
--

INSERT INTO `favoris` (`id_user`, `id_produit`, `date_ajout`) VALUES
(90, 52, '2025-06-21 15:20:30'),
(90, 54, '2025-06-21 15:17:54'),
(96, 54, '2025-06-21 15:26:07');

-- --------------------------------------------------------

--
-- Structure de la table `images_produits`
--

CREATE TABLE `images_produits` (
  `id` int(11) NOT NULL,
  `id_produit` int(11) DEFAULT NULL,
  `URL_Image` varchar(255) NOT NULL,
  `Legende` varchar(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `images_produits`
--

INSERT INTO `images_produits` (`id`, `id_produit`, `URL_Image`, `Legende`) VALUES
(114, 54, 'http://localhost/img/6848ca51bce58_Capture d\'écran 2025-06-09 142902.png', NULL),
(120, 50, 'http://localhost/img/6851e83211a72_card-large-item8.jpg', NULL),
(121, 52, 'http://localhost/img/6851e8425903b_card-image6.jpg', NULL),
(122, 53, 'http://localhost/img/6851e856343d3_card-image3.jpg', NULL),
(123, 54, 'http://localhost/img/6851f8a678146_author-item.jpg', NULL),
(124, 52, 'http://localhost/img/6852cd4924b18_author-item.jpg', NULL),
(125, 52, 'http://localhost/img/6852cd49255b0_banner-image3.jpg', NULL),
(126, 52, 'http://localhost/img/6852cd4925b00_card-image1.jpg', NULL),
(127, 57, 'http://localhost/img/6852cf70341ed_author-item.jpg', NULL),
(128, 57, 'http://localhost/img/6852cf7036342_banner-image3.jpg', NULL),
(129, 57, 'http://localhost/img/6852cf703754f_card-image1.jpg', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `panier`
--

CREATE TABLE `panier` (
  `id_user` int(11) NOT NULL,
  `id_produit` int(11) NOT NULL,
  `id_pointure` int(11) NOT NULL,
  `quantite` int(11) NOT NULL,
  `date_ajout` datetime NOT NULL DEFAULT current_timestamp()
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
(54, 19, 6),
(54, 20, 6),
(54, 21, 8),
(50, 21, 8),
(50, 23, 10),
(52, 15, 8),
(52, 16, 4),
(52, 17, 10),
(52, 18, 4),
(57, 20, 13),
(57, 22, 6),
(57, 23, 7),
(53, 5, 6),
(53, 6, 8),
(53, 7, 5),
(53, 8, 9);

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
(50, 'Airforcee', 'Adidas', 'homme', 'running', 'bleu', 'DD', 'en stock', 100, 18, '2025-06-10 20:58:12', '2025-06-19 21:38:13', NULL),
(52, 'Classic', 'Reebok', 'femme', 'casual', 'blanc', 'Chaussures casual classiques pour femme', 'en promotion', 30, 26, '2025-06-11 10:05:00', '2025-06-21 14:38:05', 18),
(53, 'Superstar', 'Adidas', 'enfant', 'casual', 'bleu', 'Chaussures pour enfants style casual', 'en stock', 60, 28, '2025-06-11 10:10:00', '2025-06-21 14:56:04', NULL),
(54, 'Air Max', 'Nike', 'homme', 'running', 'Rouge', 'Chaussures de running avec amorti Air Max', 'en stock', 100, 20, '2025-06-11 10:15:00', '2025-06-21 01:18:29', NULL),
(57, 'Airforce', 'Nike', 'femme', 'running', 'bleu', 'c\'est un chaussure très moderne ', 'en stock', 100, 26, '2025-06-18 15:38:40', '2025-06-21 14:46:07', NULL);

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
(18, 'black friday', 'C\'est Black Friday', '2025-06-12 15:51:00', '2025-08-21 00:47:00', 60);

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
  `statut` enum('nouveau','en cours','résolu') NOT NULL DEFAULT 'nouveau'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `reclamation`
--

INSERT INTO `reclamation` (`id`, `id_user`, `id_produit`, `type`, `description`, `date_creation`, `date_modification`, `statut`) VALUES
(12, 96, NULL, 'service', 'site est très lent', '2025-06-21 15:38:19', '2025-06-21 18:38:08', 'en cours'),
(13, 90, 52, 'produit', 'n\'est pas bon il est très petit  ', '2025-06-21 17:45:28', '2025-06-21 18:39:27', 'en cours');

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
(90, 'Mohamed Youssef', 'Azzouz', 'Homme', '2002-09-06', 22, '26556300', 'Carthage', 'youssefcarma@gmail.com', 'admin123', 'http://localhost/img/user_68576620aa582.jpg'),
(96, 'Ahmed', 'Azzouz', 'homme', '2000-09-10', 24, '26556300', 'Kram ', 'mohamedyoussefazzouz@gmail.com', '6d74b9f759e548d0', 'http://localhost/img/user_6856c062cc1b5.jpg');

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
-- Index pour la table `commande`
--
ALTER TABLE `commande`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`);

--
-- Index pour la table `commande_produit`
--
ALTER TABLE `commande_produit`
  ADD PRIMARY KEY (`id_commande`,`id_produit`,`id_pointure`),
  ADD KEY `id_produit` (`id_produit`),
  ADD KEY `id_pointure` (`id_pointure`);

--
-- Index pour la table `coupon`
--
ALTER TABLE `coupon`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

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
  ADD PRIMARY KEY (`id_user`,`id_produit`,`id_pointure`),
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pour la table `commande`
--
ALTER TABLE `commande`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT pour la table `coupon`
--
ALTER TABLE `coupon`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `images_produits`
--
ALTER TABLE `images_produits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=130;

--
-- AUTO_INCREMENT pour la table `pointures`
--
ALTER TABLE `pointures`
  MODIFY `id` int(2) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT pour la table `produit`
--
ALTER TABLE `produit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT pour la table `promotion`
--
ALTER TABLE `promotion`
  MODIFY `id` int(3) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pour la table `reclamation`
--
ALTER TABLE `reclamation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

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
-- Contraintes pour la table `commande`
--
ALTER TABLE `commande`
  ADD CONSTRAINT `commande_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `commande_produit`
--
ALTER TABLE `commande_produit`
  ADD CONSTRAINT `commande_produit_ibfk_1` FOREIGN KEY (`id_commande`) REFERENCES `commande` (`id`),
  ADD CONSTRAINT `commande_produit_ibfk_2` FOREIGN KEY (`id_produit`) REFERENCES `produit` (`id`),
  ADD CONSTRAINT `commande_produit_ibfk_3` FOREIGN KEY (`id_pointure`) REFERENCES `pointures` (`id`);

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
  ADD CONSTRAINT `panier_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `panier_ibfk_2` FOREIGN KEY (`id_produit`) REFERENCES `produit` (`id`),
  ADD CONSTRAINT `panier_ibfk_3` FOREIGN KEY (`id_pointure`) REFERENCES `pointures` (`id`);

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
