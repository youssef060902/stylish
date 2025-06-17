<?php

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php include 'header.php'; ?>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Custom CSS -->
  <style>
    /* Styles généraux */
    :root {
      --primary-color: #2c3e50;
      --secondary-color: #e74c3c;
      --text-color: #2c3e50;
      --light-bg: #f8f9fa;
      --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      --navbar-height: 80px;
    }

    body {
      font-family: 'Poppins', sans-serif;
      padding-top: var(--navbar-height);
    }

    

    /* Styles pour la section des produits en promotion (simplifié) */
    .product-store .product-card {
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: none; /* Pas d'ombre par défaut */
      transition: border-color 0.2s ease-in-out; /* Seule la bordure change */
    }
    .product-store .product-card:hover {
      transform: none; /* Supprime le soulèvement */
      border-color: var(--secondary-color); /* Bordure colorée au survol */
      box-shadow: none; /* Toujours pas d'ombre au survol */
    }
    .product-store .card-img {
      position: relative;
      padding-top: 100%; /* Ratio 1:1 pour les images */
      overflow: hidden;
    }
    .product-store .product-image {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .product-store .cart-concern {
      background: none; /* Supprime le fond de l'overlay */
      position: absolute;
      bottom: 0;
      padding: 10px;
      width: 100%;
      opacity: 0;
      transition: opacity 0.3s ease-out;
      pointer-events: none;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .product-store .product-card:hover .cart-concern {
      opacity: 1;
      pointer-events: all;
    }
    .product-store .cart-button {
        display: flex;
        gap: 10px;
        justify-content: center;
        width: 100%;
    }
    .product-store .cart-button .btn {
      background-color: transparent; /* Fond transparent par défaut */
      color: var(--primary-color); /* Texte couleur primaire */
      border: 1px solid var(--primary-color); /* Bordure primaire */
      border-radius: 5px;
      padding: 8px 12px;
      font-size: 0.9em;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 5px;
      transition: background-color 0.2s, color 0.2s, border-color 0.2s;
      /* backdrop-filter est retiré pour la simplicité */
    }
    .product-store .cart-button .btn:hover {
      background-color: var(--primary-color); /* Fond primaire au survol */
      color: white; /* Texte blanc au survol */
      border-color: var(--primary-color); /* Bordure primaire au survol */
    }
    .product-store .cart-button svg {
      fill: currentColor;
      width: 18px;
      height: 18px;
    }
    .product-store .discount-badge {
      background-color: #ff4444;
      color: white;
      padding: 5px 10px;
      border-radius: 4px;
      font-weight: bold;
      font-size: 14px;
      position: absolute;
      top: 10px;
      right: 10px;
      z-index: 5;
    }
    .product-store .card-detail {
      padding: 15px;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .product-store .card-title {
      font-size: 1.1em;
      margin-bottom: 5px;
    }
    .product-store .card-title a {
      text-decoration: none;
      color: var(--text-color);
    }
    .product-store .card-title a:hover {
      color: var(--secondary-color);
    }
    .product-store .price-container {
      display: flex;
      align-items: baseline;
      justify-content: flex-end;
      gap: 8px;
      margin-top: auto;
    }
    .product-store .card-price {
      color: var(--secondary-color);
      font-size: 1.2em;
      font-weight: bold;
    }
    .product-store .original-price {
      font-size: 0.9em;
      color: #888;
      text-decoration: line-through;
    }
    .product-store .row {
      display: flex;
      flex-wrap: nowrap;
      overflow-x: auto;
      margin: 0 -10px;
      padding: 10px 0;
    }
    .product-store .col {
      flex: 0 0 auto;
      width: 250px;
      padding: 0 10px;
    }


    /* Ajustement du contenu principal */
    main {
      position: relative;
      z-index: 1;
    }
  </style>
  <link rel="stylesheet" href="css/vendor.css">
  <link rel="stylesheet" type="text/css" href="style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,900;1,900&family=Source+Sans+Pro:wght@400;600;700;900&display=swap"
    rel="stylesheet">
  <style>
    
  </style>
  <link rel="stylesheet" href="css/all.min.css">
  <style>
    .product-store .row {
      display: flex;
      flex-wrap: nowrap;
      overflow-x: auto;
      margin: 0 -10px;
      padding: 10px 0;
    }
    .product-store .col {
      flex: 0 0 auto;
      width: 250px;
      padding: 0 10px;
    }
    .product-store .product-card {
      height: 100%;
      display: flex;
      flex-direction: column;
    }
    .product-store .card-img {
      position: relative;
      padding-top: 100%;
      overflow: hidden;
    }
    .product-store .product-image {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .product-store .discount-badge {
        background-color: #ff4444;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-weight: bold;
        font-size: 14px;
    }
    .product-store .price-container {
        display: flex;
        align-items: baseline; /* Aligne les prix sur la même ligne de base */
        justify-content: flex-end;
        gap: 8px; /* Ajoute un espace entre les prix */
    }
    .product-store .card-price {
        color: #ff4444; /* Prix promotionné en rouge */
        font-size: 1.2em; /* Rend le prix promotionné plus grand */
        font-weight: bold; /* Le rend gras */
    }
    .product-store .original-price {
        font-size: 0.9em;
        color: #888; /* Couleur gris clair pour le prix barré */
        text-decoration: line-through; /* Barre le prix */
    }
  </style>
  <style>
    /* Styles pour la modal de détails du produit */
    .product-details-modal .modal-content {
      border: none;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .product-details-modal .modal-header {
      border-bottom: none;
      padding: 1.5rem;
      background-color: #fff;
    }

    .product-details-modal .modal-title {
      font-weight: 600;
      color: #2c3e50;
      font-size: 1.5rem;
    }

    .product-details-modal .modal-body {
      padding: 0;
    }

    .product-details-modal .carousel {
      border-radius: 0;
      overflow: hidden;
    }

    .product-details-modal .carousel-item img {
      height: 500px;
      object-fit: contain;
      background-color: #f8f9fa;
    }

    .product-details-modal .carousel-control-prev,
    .product-details-modal .carousel-control-next {
      width: 40px;
      height: 40px;
      background-color: rgba(255, 255, 255, 0.9);
      border-radius: 50%;
      top: 50%;
      transform: translateY(-50%);
      margin: 0 20px;
    }

    .product-details-modal .carousel-control-prev-icon,
    .product-details-modal .carousel-control-next-icon {
      width: 20px;
      height: 20px;
    }

    .product-details-modal .product-info {
      padding: 2.5rem;
      background: #fff;
    }

    .product-details-modal .product-title {
      font-size: 2rem;
      font-weight: 700;
      color: #2c3e50;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .product-details-modal .promotion-badge {
      background-color: #e74c3c;
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-size: 0.9rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .product-details-modal .promotion-badge svg {
      width: 0.8em; /* Ajustez la taille selon vos besoins */
      height: 0.8em;
      vertical-align: middle;
    }

    /* .product-details-modal .promotion-badge i { */
    /*   font-size: 0.8rem; */
    /* } */

    .product-details-modal .price-tag {
      display: flex;
      align-items: baseline;
      gap: 15px;
      margin-bottom: 2rem;
    }

    .product-details-modal .price-tag span {
      font-size: 2rem;
      font-weight: 700;
      color: #e74c3c;
    }

    .product-details-modal .original-price-tag span {
      font-size: 1.2rem;
      color: #95a5a6;
      text-decoration: line-through;
    }

    .product-details-modal .product-meta {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .product-details-modal .meta-item {
      background-color: #f8f9fa;
      padding: 1rem;
      border-radius: 12px;
      transition: all 0.3s ease;
    }

    .product-details-modal .meta-item:hover {
      background-color: #e9ecef;
      transform: translateY(-2px);
    }

    .product-details-modal .meta-label {
      font-size: 0.9rem;
      color: #7f8c8d;
      margin-bottom: 0.5rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .product-details-modal .meta-value {
      font-weight: 600;
      color: #2c3e50;
      font-size: 1.1rem;
    }

    .product-details-modal .product-description {
      background-color: #f8f9fa;
      padding: 1.5rem;
      border-radius: 12px;
      margin-bottom: 2rem;
    }

    .product-details-modal .product-description h5 {
      color: #2c3e50;
      font-weight: 600;
      margin-bottom: 1rem;
    }

    .product-details-modal .product-description p {
      color: #34495e;
      line-height: 1.6;
      margin: 0;
    }

    .product-details-modal .sizes-section h5 {
      color: #2c3e50;
      font-weight: 600;
      margin-bottom: 1rem;
    }

    .product-details-modal .sizes-container {
      display: flex;
      flex-wrap: wrap;
      gap: 0.8rem;
    }

    .product-details-modal .size-badge {
      background-color: #f8f9fa;
      color: #2c3e50;
      padding: 0.8rem 1.5rem;
      border-radius: 30px;
      font-weight: 600;
      font-size: 1.1rem;
      transition: all 0.3s ease;
      cursor: pointer;
      border: 2px solid transparent;
    }

    .product-details-modal .size-badge:hover {
      background-color: #e9ecef;
      transform: translateY(-2px);
    }

    .product-details-modal .status-section {
      margin-top: 2rem;
    }

    .product-details-modal .status-badge {
      display: inline-block;
      padding: 0.8rem 1.5rem;
      border-radius: 30px;
      font-weight: 600;
      font-size: 1rem;
    }

    .product-details-modal .btn-primary {
      background-color: #e74c3c;
      border-color: #e74c3c;
      padding: 1rem 2rem;
      font-size: 1.1rem;
      font-weight: 600;
      border-radius: 30px;
      transition: all 0.3s ease;
    }

    .product-details-modal .btn-primary:hover {
      background-color: #c0392b;
      border-color: #c0392b;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
    }

    .product-details-modal .btn-favorite {
      background-color: #ffffff; /* Passé de #f8f9fa à blanc pur pour un aspect plus net */
      border: 2px solid #e74c3c;
      color: #e74c3c;
      padding: 1rem 1.5rem; /* Ajusté pour être plus compact */
      font-size: 1.1rem;
      font-weight: 600;
      border-radius: 30px;
      transition: all 0.3s ease;
    }

    .product-details-modal .btn-favorite:hover {
      background-color: #e74c3c;
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(231, 76, 60, 0.2);
    }

    .product-details-modal .btn-favorite svg,
    .product-details-modal .btn-primary svg {
      width: 1.1em; /* Correspond à la taille précédente de l'icône Font Awesome */
      height: 1.1em;
      vertical-align: middle;
    }

    /* .product-details-modal .btn-favorite i { */
    /*   margin-right: 0.5rem; */
    /*   font-size: 1.1rem; */
    /* } */

    .product-details-modal .btn-close {
      position: absolute;
      right: 1.5rem;
      top: 1.5rem;
      background-color: #f8f9fa;
      padding: 0.8rem;
      border-radius: 50%;
      opacity: 1;
      transition: all 0.3s ease;
    }

    .product-details-modal .btn-close:hover {
      background-color: #e9ecef;
      transform: rotate(90deg);
    }
    /* Font Awesome explicit font-family declaration */
    /* .fa-solid, */
    /* .fa-regular, */
    /* .fa-brands { */
    /*   font-family: "Font Awesome 6 Free" !important; */
    /*   font-weight: 900 !important; */
    /*   font-display: block; */
    /* } */
    /* .fa-regular { */
    /*   font-weight: 400 !important; */
    /* } */
  </style>
  <link rel="stylesheet" href="css/all.min.css">
</head>

<body>
  <svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
    <symbol id="heart" viewBox="0 0 24 24">
      <path d="M12 21.35l-1.84-1.66C4.01 15.36 2 13.06 2 10.11 2 6.7 4.7 4 8.11 4c1.98 0 3.91.96 5.12 2.5l.77.93.77-.93C15.9 4.96 17.82 4 19.89 4 23.3 4 26 6.7 26 10.11c0 2.95-2.01 5.25-8.16 9.58L12 21.35z"/>
    </symbol>
    <symbol id="heart-outline" viewBox="0 0 24 24">
      <path d="M16.5 3C14.77 3 13.1 3.81 12 5.09 10.9 3.81 9.23 3 7.5 3 4.42 3 2 5.42 2 8.5c0 3.78 3.4 6.86 8.55 11.54L12 21.35l1.45-1.31C18.6 15.36 22 12.28 22 8.5 22 5.42 19.58 3 16.5 3zm-4.4 15.71l-.1-.09-.1-.09c-4.9-4.47-7.9-7.14-7.9-10.03 0-2.31 1.76-4.17 3.93-4.17 1.81 0 3.42 1.05 4.17 2.68.75-1.63 2.36-2.68 4.17-2.68 2.17 0 3.93 1.86 3.93 4.17 0 2.89-3 5.56-7.9 10.03l-.1.09-.1.09-.1.09-.1.09z"/>
    </symbol>
    <symbol id="tag" viewBox="0 0 24 24">
      <path d="M20 12l-1.41-1.41L12 17.17l-6.59-6.58L4 12l8 8 8-8zM12 4l-8 8 8 8 8-8-8-8z"/>
    </symbol>
  </svg>

  <!-- Section À propos -->
  <section id="about" class="about-section-final">
    <div class="container-fluid h-100">
      <div class="row h-100 align-items-center no-gutters">
        <div class="col-lg-6 d-flex justify-content-center align-items-center about-image-panel-final">
          <img src="images/logoo.png" alt="Stylish Logo" class="img-fluid stylish-about-logo">
        </div>
        <div class="col-lg-6 about-content-panel-final">
          <div class="about-content-wrapper-final">
            <h1>About us.</h1>
            <p class="bold-paragraph">
              Chez Stylish, nous sommes votre destination premium pour des chaussures d'exception. Nous sommes reconnus pour notre sélection raffinée qui allie élégance, confort et qualité, offrant des moments de style inoubliables.
            </p>
            <p>
              L'excellence se manifeste dans chaque détail, assurant des pas qui font la différence. Nous vous offrons une expérience shopping unique et fluide, du choix de vos chaussures jusqu'à leur livraison. Rejoignez la communauté Stylish et découvrez pourquoi nous sommes la référence en ligne.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <style>
    /* Import Playfair Display font */
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap');

    /* Police principale pour tout le site */
    * {
      font-family: "Times New Roman", Times, serif !important;
    }

    /* About Us Section Final Design */
    .about-section-final {
      min-height: 700px;
      background-color: #f5f5f5;
      display: flex;
      align-items: center;
    }

    .about-section-final .container-fluid {
      height: 100%;
    }

    /* Styles améliorés pour le logo */
    .navbar-brand img {
      height: 55px; /* Augmentation de la taille du logo dans la navbar */
      transition: all 0.3s ease;
      filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
    }

    .navbar-brand:hover img {
      transform: scale(1.05);
      filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.15));
    }

    .about-section-final .stylish-about-logo {
      max-width: 350px; /* Légèrement plus grand */
      border-radius: 15px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
      padding: 0; /* Suppression du padding */
      background-color: transparent; /* Suppression du fond gris */
      transition: all 0.3s ease;
    }

    .about-section-final .stylish-about-logo:hover {
      transform: translateY(-5px);
      box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
    }

    /* Animation subtile pour le logo */
    @keyframes logoFloat {
      0% { transform: translateY(0); }
      50% { transform: translateY(-5px); }
      100% { transform: translateY(0); }
    }

    .about-section-final .stylish-about-logo {
      animation: logoFloat 3s ease-in-out infinite;
    }

    .about-image-panel-final {
      background-color: #e0e0e0; /* Darker grey for the image panel background */
      min-height: 400px;
      position: relative;
    }

    .about-content-panel-final {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 80px;
      background-color: #f5f5f5; /* Match main background */
    }

    .about-content-wrapper-final {
      max-width: 600px;
      text-align: left;
    }

    .about-content-wrapper-final h1 {
      font-family: 'Playfair Display', serif;
      font-size: 5rem; /* Larger font size for the title */
      font-weight: 400;
      color: #333;
      margin-bottom: 40px;
      line-height: 1;
    }

    .about-content-wrapper-final p {
      font-family: 'Poppins', sans-serif; /* Keep Poppins for body text */
      font-size: 1rem;
      line-height: 1.7;
      color: #555;
      margin-bottom: 25px;
      font-weight: 300;
    }

    .about-content-wrapper-final p.bold-paragraph {
      font-weight: 600;
      color: #444;
    }

    @media (max-width: 991px) {
      .about-section-final .row {
        flex-direction: column;
      }

      .about-image-panel-final {
        min-height: 250px;
        padding: 20px;
      }

      .stylish-about-logo {
        max-width: 180px;
      }

      .about-content-panel-final {
        padding: 40px 20px;
        text-align: center;
      }

      .about-content-wrapper-final h1 {
        font-size: 3.5rem;
        margin-bottom: 25px;
      }

      .about-content-wrapper-final p {
        font-size: 0.95rem;
      }
    }
  </style>

  <!-- Reste du contenu -->
  <section id="intro" class="position-relative mt-4">
    <div class="container-lg">
      <div class="swiper main-swiper">
        <div class="swiper-wrapper">
          <div class="swiper-slide">
            <div class="card d-flex flex-row align-items-end border-0 large jarallax-keep-img">
              <img src="images/card-image1.jpg" alt="shoes" class="img-fluid jarallax-img">
              <div class="cart-concern p-3 m-3 p-lg-5 m-lg-5">
                <h2 class="card-title display-3 light">Stylish shoes for Women</h2>
                <a href="index.html"
                  class="text-uppercase light mt-3 d-inline-block text-hover fw-bold light-border">Shop Now</a>
              </div>
            </div>
          </div>
          <div class="swiper-slide">
            <div class="row g-4">
              <div class="col-lg-12 mb-4">
                <div class="card d-flex flex-row align-items-end border-0 jarallax-keep-img">
                  <img src="images/card-image2.jpg" alt="shoes" class="img-fluid jarallax-img">
                  <div class="cart-concern p-3 m-3 p-lg-5 m-lg-5">
                    <h2 class="card-title style-2 display-4 light">Sports Wear</h2>
                    <a href="index.html"
                      class="text-uppercase light mt-3 d-inline-block text-hover fw-bold light-border">Shop Now</a>
                  </div>
                </div>
              </div>
              <div class="col-lg-12">
                <div class="card d-flex flex-row align-items-end border-0 jarallax-keep-img">
                  <img src="images/card-image3.jpg" alt="shoes" class="img-fluid jarallax-img">
                  <div class="cart-concern p-3 m-3 p-lg-5 m-lg-5">
                    <h2 class="card-title style-2 display-4 light">Fashion Shoes</h2>
                    <a href="index.html"
                      class="text-uppercase light mt-3 d-inline-block text-hover fw-bold light-border">Shop Now</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="swiper-slide">
            <div class="row g-4">
              <div class="col-lg-12 mb-4">
                <div class="card d-flex flex-row align-items-end border-0 jarallax-keep-img">
                  <img src="images/card-image5.jpg" alt="shoes" class="img-fluid jarallax-img">
                  <div class="cart-concern p-3 m-3 p-lg-5 m-lg-5">
                    <h2 class="card-title style-2 display-4 light">Men Shoes</h2>
                    <a href="index.html"
                      class="text-uppercase light mt-3 d-inline-block text-hover fw-bold light-border">Shop Now</a>
                  </div>
                </div>
              </div>
              <div class="col-lg-12">
                <div class="card d-flex flex-row align-items-end border-0 jarallax-keep-img">
                  <img src="images/card-image6.jpg" alt="shoes" class="img-fluid jarallax-img">
                  <div class="cart-concern p-3 m-3 p-lg-5 m-lg-5">
                    <h2 class="card-title style-2 display-4 light">Women Shoes</h2>
                    <a href="index.html"
                      class="text-uppercase light mt-3 d-inline-block text-hover fw-bold light-border">Shop Now</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="swiper-pagination"></div>
    </div>
  </section>
  
  <section id="featured-products" class="product-store">
    <div class="container-md">
      <div class="display-header d-flex align-items-center justify-content-between">
        <h2 class="section-title text-uppercase">Products on promotion</h2>
        <div class="btn-right">
          <a href="promotions.php" class="d-inline-block text-uppercase text-hover fw-bold">View all</a>
        </div>
      </div>
      <div class="product-content padding-small">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5">
          <?php
          // Connexion à la base de données
          $host = 'localhost';
          $dbname = 'stylish';
          $username = 'root';
          $password = '';

          try {
              $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
              $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
              $pdo->exec("SET NAMES utf8");

              // Récupération des produits en promotion
              $stmt = $pdo->query("SELECT p.*, pr.discount, pr.nom as promotion_nom, 
                                  (SELECT URL_Image FROM images_produits WHERE id_produit = p.id LIMIT 1) as image_url
                                  FROM produit p 
                                  LEFT JOIN promotion pr ON p.id_promotion = pr.id 
                                  WHERE p.statut = 'en promotion' 
                                  ORDER BY p.id DESC 
                                  LIMIT 5");
              $promo_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

              foreach ($promo_products as $product) {
                  // Si $product['prix'] est le prix promotionné
                  // Calculer le prix original à partir du prix promotionné et de la réduction
                  $original_price = $product['prix'] / (1 - $product['discount'] / 100);
                  ?>
                  <div class="col mb-4"> 
                    <div class="product-card position-relative" onclick="displayProductModal(<?php echo $product['id']; ?>)">
                      <div class="card-img">
                        <?php if ($product['image_url']): ?>
                          <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['nom']); ?>" class="product-image img-fluid">
                        <?php else: ?>
                          <img src="images/no-image.jpg" alt="No image" class="product-image img-fluid">
                        <?php endif; ?>
                        
                        <?php if ($product['discount'] > 0): ?>
                          <div class="discount-badge position-absolute top-0 end-0 m-2">
                            -<?php echo $product['discount']; ?>%
                          </div>
                        <?php endif; ?>
                      </div>
                      <div class="card-detail d-flex justify-content-between align-items-center mt-3">
                        <h3 class="card-title fs-6 fw-normal m-0">
                          <a href="product-details.php?id=<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['nom']); ?></a>
                        </h3>
                        <div class="price-container">
                          <span class="card-price fw-bold"><?php echo number_format($product['prix'], 2); ?> DT</span>
                          <?php if ($product['discount'] > 0): ?>
                            <span class="original-price text-decoration-line-through text-muted ms-2"><?php echo number_format($original_price, 2); ?> DT</span>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  </div>
                  <?php
              }
          } catch(PDOException $e) {
              echo "Erreur : " . $e->getMessage();
          }
          ?>
        </div>
      </div>
    </div>
  </section>
  
  <section id="collection-products" class="py-2 my-2 py-md-5 my-md-5">
    <div class="container-md">
      <div class="row">
        <div class="col-lg-6 col-md-6 mb-4">
          <div class="collection-card card border-0 d-flex flex-row align-items-end jarallax-keep-img">
            <img src="images/collection-item1.jpg" alt="product-item" class="border-rounded-10 img-fluid jarallax-img">
            <div class="card-detail p-3 m-3 p-lg-5 m-lg-5">
              <h3 class="card-title display-3">
                <a href="#">Minimal Collection</a>
              </h3>
              <a href="index.html" class="text-uppercase mt-3 d-inline-block text-hover fw-bold">Shop Now</a>
            </div>
          </div>
        </div>
        <div class="col-lg-6 col-md-6">
          <div class="collection-card card border-0 d-flex flex-row jarallax-keep-img">
            <img src="images/collection-item2.jpg" alt="product-item" class="border-rounded-10 img-fluid jarallax-img">
            <div class="card-detail p-3 m-3 p-lg-5 m-lg-5">
              <h3 class="card-title display-3">
                <a href="#">Sneakers Collection</a>
              </h3>
              <a href="index.html" class="text-uppercase mt-3 d-inline-block text-hover fw-bold">Shop Now</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <section id="latest-products" class="product-store py-2 my-2 py-md-5 my-md-5 pt-0">
    <div class="container-md">
      <div class="display-header d-flex align-items-center justify-content-between">
        <h2 class="section-title text-uppercase">Latest Products</h2>
        <div class="btn-right">
          <a href="nouveautes.php" class="d-inline-block text-uppercase text-hover fw-bold">View all</a>
        </div>
      </div>
      <div class="product-content padding-small">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5">
          <?php
          // Connexion à la base de données (utiliser les mêmes identifiants que la section promotion)
          $host = 'localhost';
          $dbname = 'stylish';
          $username = 'root';
          $password = '';

          try {
              $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
              $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
              $pdo->exec("SET NAMES utf8");

              // Récupération des 5 derniers produits ajoutés
              $stmt = $pdo->query("SELECT p.*, pr.discount, pr.nom as promotion_nom,
                                  (SELECT URL_Image FROM images_produits WHERE id_produit = p.id LIMIT 1) as image_url
                                  FROM produit p
                                  LEFT JOIN promotion pr ON p.id_promotion = pr.id
                                  ORDER BY p.date_ajout DESC
                                  LIMIT 5");
              $latest_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

              foreach ($latest_products as $product) {
                  $original_price = $product['prix'];
                  if ($product['discount'] > 0) {
                      $original_price = $product['prix'] / (1 - $product['discount'] / 100);
                  }
                  ?>
                  <div class="col mb-4"> 
                    <div class="product-card position-relative" onclick="displayProductModal(<?php echo $product['id']; ?>)">
                      <div class="card-img">
                        <?php if ($product['image_url']): ?>
                          <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['nom']); ?>" class="product-image img-fluid">
                        <?php else: ?>
                          <img src="images/no-image.jpg" alt="No image" class="product-image img-fluid">
                        <?php endif; ?>
                        
                        <?php if ($product['discount'] > 0): ?>
                          <div class="discount-badge position-absolute top-0 end-0 m-2">
                            -<?php echo $product['discount']; ?>%
                          </div>
                        <?php endif; ?>
                      </div>
                      <div class="card-detail d-flex justify-content-between align-items-center mt-3">
                        <h3 class="card-title fs-6 fw-normal m-0">
                          <a href="product-details.php?id=<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['nom']); ?></a>
                        </h3>
                        <div class="price-container">
                          <span class="card-price fw-bold"><?php echo number_format($product['prix'], 2); ?> DT</span>
                          <?php if ($product['discount'] > 0): ?>
                            <span class="original-price text-decoration-line-through text-muted ms-2"><?php echo number_format($original_price, 2); ?> DT</span>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  </div>
                  <?php
              }
          } catch(PDOException $e) {
              echo "Erreur : " . $e->getMessage();
          }
          ?>
        </div>
      </div>
      </section>

  <?php include 'footer.php'; ?>
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/jquery-1.11.0.min.js"></script>
  <script src="js/plugins.js"></script>
  <script src="js/script.js"></script>
  <!-- SweetAlert2 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
  <!-- SweetAlert2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

<script>
  let currentProductId = null;
  let selectedSize = null;

  function getCurrentProductId() {
      return currentProductId;
  }

  function displayProductModal(id) {
      currentProductId = id;
      selectedSize = null;

      fetch(`get_product_details.php?id=${id}`)
          .then(response => response.json())
          .then(data => {
              if (data.success) {
                  const product = data.data;

                  // Mise à jour des détails du produit
                  document.getElementById('details_nom').textContent = product.nom;
                  document.getElementById('details_marque').textContent = product.marque;
                  document.getElementById('details_categorie').textContent = product.catégorie;
                  document.getElementById('details_type').textContent = product.type;
                  document.getElementById('details_couleur').textContent = product.couleur;
                  document.getElementById('details_description').textContent = product.description;

                  // Gestion des prix
                  const prixPromo = parseFloat(product.prix);
                  const discount = parseFloat(product.discount);
                  
                  document.getElementById('details_prix_promo').textContent = `${prixPromo.toFixed(2)} DT`;

                  if (discount > 0) {
                      const prixOriginal = prixPromo / (1 - discount / 100);
                      document.getElementById('details_promotion_badge').style.display = 'inline-flex';
                      document.getElementById('details_prix_original').textContent = `${prixOriginal.toFixed(2)} DT`;
                      document.getElementById('details_prix_original').style.display = 'inline'; // S'assurer qu'il est visible
                  } else {
                      document.getElementById('details_promotion_badge').style.display = 'none';
                      document.getElementById('details_prix_original').textContent = ''; // Vider le contenu
                      document.getElementById('details_prix_original').style.display = 'none'; // Masquer l'élément
                  }

                  // Gestion des images
                  const carouselInner = document.getElementById('productDetailsCarouselInner');
                  carouselInner.innerHTML = '';

                  if (product.images && product.images.length > 0) {
                      product.images.forEach((image, index) => {
                          const carouselItem = document.createElement('div');
                          carouselItem.className = `carousel-item ${index === 0 ? 'active' : ''}`;
                          carouselItem.innerHTML = `
                              <img src="${image}" class="d-block w-100" alt="${product.nom}">
                          `;
                          carouselInner.appendChild(carouselItem);
                      });
                  } else {
                      carouselInner.innerHTML = `
                          <div class="carousel-item active">
                              <div class="d-flex align-items-center justify-content-center bg-light" style="height: 400px;">
                                  <i class="fas fa-image fa-5x text-muted"></i>
        </div>
      </div>
                      `;
                  }

                  // Gestion des pointures
                  const sizesContainer = document.getElementById('details_pointures');
                  sizesContainer.innerHTML = '';

                  if (product.pointures && product.pointures.length > 0) {
                      product.pointures.forEach(pointure => {
                          const sizeBadge = document.createElement('div');
                          sizeBadge.className = 'size-badge';
                          sizeBadge.innerHTML = `<span>${pointure}</span>`;
                          sizeBadge.onclick = () => selectSize(sizeBadge, pointure);
                          sizesContainer.appendChild(sizeBadge);
                      });
                  } else {
                      sizesContainer.innerHTML = '<span class="text-muted">Aucune pointure disponible</span>';
                  }

                  // Vérifier si le produit est dans les favoris
                  checkFavoriteStatus(id);

                  // Afficher la modal
                  const productDetailsModal = new bootstrap.Modal(document.getElementById('productDetailsModal'));
                  productDetailsModal.show();
              } else {
                  console.error('Erreur lors du chargement des détails du produit:', data.message);
              }
          })
          .catch(error => {
              console.error('Erreur:', error);
          });
  }

  function selectSize(sizeBadge, pointure) {
      // Retirer la classe selected de tous les badges
      document.querySelectorAll('.size-badge').forEach(badge => {
          badge.classList.remove('selected');
      });

      // Ajouter la classe selected au badge cliqué
      sizeBadge.classList.add('selected');
      selectedSize = pointure;
  }

  function addToCart(productId) {
      if (!selectedSize) {
          alert('Veuillez sélectionner une pointure');
          return;
      }

      // Ajouter au panier
      fetch('add_to_cart.php', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json',
          },
          body: JSON.stringify({
              productId: productId,
              size: selectedSize,
              quantity: 1
          })
      })
      .then(response => response.json())
      .then(data => {
          if (data.success) {
              // Mettre à jour le compteur du panier
              updateCartCount();
              // Afficher un message de succès
              alert('Produit ajouté au panier avec succès');
          } else {
              alert(data.message || 'Erreur lors de l\'ajout au panier');
          }
      })
      .catch(error => {
          console.error('Erreur:', error);
          alert('Erreur lors de l\'ajout au panier');
      });
  }

  function toggleFavorite(productId) {
      fetch('toggle_favorite.php', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json',
          },
          body: JSON.stringify({
              productId: productId
          })
      })
      .then(response => response.json())
      .then(data => {
          if (data.success) {
              const favoriteSvgUse = document.querySelector('.btn-outline-danger svg use');
              if (favoriteSvgUse) {
                  if (data.isFavorite) {
                      favoriteSvgUse.setAttribute('xlink:href', '#heart');
                  } else {
                      favoriteSvgUse.setAttribute('xlink:href', '#heart-outline');
                  }
              }
          }
      })
      .catch(error => {
          console.error('Erreur:', error);
      });
  }

  function checkFavoriteStatus(productId) {
      fetch(`check_favorite.php?id=${productId}`)
          .then(response => response.json())
          .then(data => {
              if (data.success) {
                  const favoriteSvgUse = document.querySelector('.btn-outline-danger svg use');
                  if (favoriteSvgUse) {
                      if (data.isFavorite) {
                          favoriteSvgUse.setAttribute('xlink:href', '#heart');
                      } else {
                          favoriteSvgUse.setAttribute('xlink:href', '#heart-outline');
                      }
                  }
              }
          })
          .catch(error => {
              console.error('Erreur:', error);
          });
  }

  function updateCartCount() {
      fetch('get_cart_count.php')
          .then(response => response.json())
          .then(data => {
              if (data.success) {
                  const cartCount = document.getElementById('cartCount');
                  if (cartCount) {
                      cartCount.textContent = data.count;
                  }
              }
          })
          .catch(error => {
              console.error('Erreur:', error);
          });
  }
</script>

<!-- Modal des détails du produit -->
<div class="modal fade product-details-modal" id="productDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">
                    <div class="col-md-6">
                        <div id="productDetailsCarousel" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner" id="productDetailsCarouselInner">
                                <!-- Les images seront insérées ici par JavaScript -->
                            </div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#productDetailsCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon"></span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#productDetailsCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon"></span>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="product-info p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h1 class="product-title mb-0" id="details_nom"></h1>
                                <div id="details_promotion_badge" class="promotion-badge" style="display: none;">
                                    <svg class="icon" style="width: 1em; height: 1em; fill: currentColor;"><use xlink:href="#tag"></use></svg>
                                    <span>Promotion</span>
                                </div>
                            </div>
                            
                            <div class="price-section mb-4">
                                <div class="price-tag d-flex align-items-baseline gap-2">
                                    <span id="details_prix_promo" class="text-danger fw-bold fs-3"></span>
                                    <span id="details_prix_original" class="text-muted text-decoration-line-through"></span>
                                </div>
                            </div>
                            
                            <div class="product-meta mb-4">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="meta-item p-3 rounded-3 bg-light">
                                            <div class="meta-label text-muted small">Marque</div>
                                            <div class="meta-value fw-semibold" id="details_marque"></div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="meta-item p-3 rounded-3 bg-light">
                                            <div class="meta-label text-muted small">Catégorie</div>
                                            <div class="meta-value fw-semibold" id="details_categorie"></div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="meta-item p-3 rounded-3 bg-light">
                                            <div class="meta-label text-muted small">Type</div>
                                            <div class="meta-value fw-semibold" id="details_type"></div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="meta-item p-3 rounded-3 bg-light">
                                            <div class="meta-label text-muted small">Couleur</div>
                                            <div class="meta-value fw-semibold" id="details_couleur"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="product-description bg-light p-4 rounded-3 mb-4">
                                <h5 class="fw-semibold mb-3">Description</h5>
                                <p id="details_description" class="mb-0"></p>
                            </div>

                            <div class="sizes-section mb-4">
                                <h5 class="fw-semibold mb-3">Pointures disponibles</h5>
                                <div class="sizes-container d-flex flex-wrap gap-2" id="details_pointures">
                                    <!-- Les pointures seront insérées ici par JavaScript -->
                                </div>
                            </div>

                            <div class="action-buttons d-flex gap-3">
                                <button class="btn btn-primary flex-grow-1 d-flex align-items-center justify-content-center gap-2" onclick="addToCart(getCurrentProductId())">
                                    <svg class="icon" style="width: 1.1em; height: 1.1em; fill: currentColor;"><use xlink:href="#shopping-cart"></use></svg>
                                    <span>Ajouter au panier</span>
                                </button>
                                <button class="btn btn-outline-danger btn-favorite d-flex align-items-center justify-content-center gap-2" onclick="toggleFavorite(getCurrentProductId())">
                                    <svg class="icon" style="width: 1.1em; height: 1.1em; fill: currentColor;"><use xlink:href="#heart-outline"></use></svg>
                                    <span>Ajouter au favoris</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Initialiser tous les dropdowns Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });
});
</script>
</body>
</html>
