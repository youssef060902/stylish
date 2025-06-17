// user-edit.js
document.addEventListener('DOMContentLoaded', function () {
    window.openEditUserModal = function(user) {
        let imageTag = `
          <div class="d-flex flex-column align-items-center mb-3" id="edit-image-wrapper">
            <div id="edit-image-preview" style="position:relative;">
              <img src="${user.image && user.image.startsWith('http') ? user.image : 'https://via.placeholder.com/120x120?text=User'}"
                alt="Photo de profil" id="edit-avatar-img"
                style="width:120px;height:120px;border-radius:50%;object-fit:cover;box-shadow:0 4px 24px rgba(44,62,80,0.18);border:4px solid #fff;">
              <span id="edit-image-remove-x" style="position:absolute;top:-10px;right:-10px;background:#dc3545;color:#fff;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-weight:bold;font-size:20px;z-index:2;">&times;</span>
            </div>
            <input type="file" name="image" id="edit-image-input" accept="image/*" style="display:none;">
            <button type="button" id="edit-image-add-btn" class="btn btn-outline-primary mt-2" style="display:none;">Ajouter une image</button>
            <input type="hidden" name="delete_image" id="edit-delete-image" value="0">
          </div>
        `;
        let html = `
            <form id="editUserForm" enctype="multipart/form-data">
                <input type="hidden" name="id" value="${user.id}">
                ${imageTag}
                <div class="row g-2">
                    <div class="col-md-6 mb-2">
                        <label>Prénom</label>
                        <input type="text" name="prenom" class="form-control" value="${user.prenom || ''}" required>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label>Nom</label>
                        <input type="text" name="nom" class="form-control" value="${user.nom || ''}" required>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label>Genre</label>
                        <select name="genre" class="form-select" required>
                            <option value="Homme" ${user.genre === 'Homme' ? 'selected' : ''}>Homme</option>
                            <option value="Femme" ${user.genre === 'Femme' ? 'selected' : ''}>Femme</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label>Date de naissance</label>
                        <input type="date" name="date_naissance" class="form-control" value="${user.date_naissance || ''}" required>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="${user.email || ''}" required>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label>Téléphone</label>
                        <input type="tel" name="phone" class="form-control" value="${user.phone || ''}" required>
                    </div>
                    <div class="col-md-12 mb-2">
                        <label>Adresse</label>
                        <input type="text" name="adresse" class="form-control" value="${user.adresse || ''}" required>
                    </div>
                </div>
            </form>
        `;

        Swal.fire({
            title: 'Modifier l\'utilisateur',
            html: html,
            showCancelButton: true,
            confirmButtonText: 'Enregistrer',
            cancelButtonText: 'Annuler',
            focusConfirm: false,
            didOpen: () => {
                setTimeout(() => {
                  const imgInput = document.getElementById('edit-image-input');
                  const imgPreview = document.getElementById('edit-avatar-img');
                  const xBtn = document.getElementById('edit-image-remove-x');
                  const addBtn = document.getElementById('edit-image-add-btn');
                  // Quand on clique sur l'image, ouvrir le file input
                  imgPreview.onclick = () => imgInput.click();
                  // Quand on choisit une image, afficher la preview
                  imgInput.onchange = function() {
                    if (this.files && this.files[0]) {
                      const reader = new FileReader();
                      reader.onload = e => {
                        imgPreview.src = e.target.result;
                        addBtn.style.display = 'none';
                        xBtn.style.display = 'flex';
                        document.getElementById('edit-delete-image').value = '0';
                      };
                      reader.readAsDataURL(this.files[0]);
                    }
                  };
                  // Quand on clique sur X, supprimer la preview et afficher le bouton ajouter
                  xBtn.onclick = function() {
                    imgPreview.src = 'https://via.placeholder.com/120x120?text=User';
                    imgInput.value = '';
                    xBtn.style.display = 'none';
                    addBtn.style.display = 'inline-block';
                    document.getElementById('edit-delete-image').value = '1';
                  };
                  // Quand on clique sur "Ajouter une image", ouvrir le file input
                  addBtn.onclick = () => imgInput.click();
                  // Si pas d'image, afficher le bouton ajouter
                  if (!imgPreview.src || imgPreview.src.includes('placeholder')) {
                    xBtn.style.display = 'none';
                    addBtn.style.display = 'inline-block';
                  }
                }, 100);
            },
            preConfirm: () => {
                const form = document.getElementById('editUserForm');
                // Validation JS
                const prenom = form.prenom.value.trim();
                const nom = form.nom.value.trim();
                const genre = form.genre.value;
                const date_naissance = form.date_naissance.value;
                const email = form.email.value.trim();
                const phone = form.phone.value.trim();
                const adresse = form.adresse.value.trim();

                if (!prenom || !nom || !genre || !date_naissance || !email || !phone || !adresse) {
                    Swal.showValidationMessage('Tous les champs sont obligatoires');
                    return false;
                }
                if (!/^[\w-.]+@[\w-]+\.[a-z]{2,}$/i.test(email)) {
                    Swal.showValidationMessage('Email invalide');
                    return false;
                }
                if (!/^\d{8,15}$/.test(phone.replace(/\D/g, ''))) {
                    Swal.showValidationMessage('Téléphone invalide');
                    return false;
                }
                // Date de naissance : 18 ans minimum
                const birth = new Date(date_naissance);
                const now = new Date();
                let age = now.getFullYear() - birth.getFullYear();
                if (now.getMonth() < birth.getMonth() || (now.getMonth() === birth.getMonth() && now.getDate() < birth.getDate())) {
                    age--;
                }
                if (age < 18) {
                    Swal.showValidationMessage('L\'utilisateur doit avoir au moins 18 ans');
                    return false;
                }
                return form;
            }
        }).then(result => {
            if (result.isConfirmed) {
                const form = result.value;
                const formData = new FormData(form);

                fetch('update_user.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Succès', data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Erreur', data.message, 'error');
                    }
                })
                .catch(() => {
                    Swal.fire('Erreur', 'Erreur lors de la communication avec le serveur.', 'error');
                });
            }
        });
    };

    document.querySelectorAll('.btn-edit-user').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const user = JSON.parse(this.closest('tr').getAttribute('data-user'));
            openEditUserModal(user);
        });
    });
}); 