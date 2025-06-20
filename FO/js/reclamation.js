$(document).ready(function() {
    // Cache le select des produits par défaut
    $('#produit_commande_div').hide();
    
    // Gestion du changement de type de réclamation
    $('#type').on('change', function() {
        if ($(this).val() === 'produit') {
            $('#produit_commande_div').slideDown();
            $('#id_produit').prop('required', true);
        } else {
            $('#produit_commande_div').slideUp();
            $('#id_produit').prop('required', false);
        }
    });

    // Animation du formulaire
    $('.add-reclam-btn').hover(
        function() { $(this).addClass('pulse'); },
        function() { $(this).removeClass('pulse'); }
    );

    // Effet de transition sur les status badges
    $('.status-badge').each(function() {
        $(this).css('transition', 'all 0.3s ease');
    });
}); 