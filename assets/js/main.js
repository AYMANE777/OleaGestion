// form fournisseur
$(document).on("submit", "#form-fournisseur", function (e) {
  e.preventDefault();

  const form = this;
  const fd = new FormData(form);

  const nom    = fd.get("nom_fournisseur")?.trim();
  const prenom = fd.get("prenom_fournisseur")?.trim();
  const tel    = fd.get("num_fournisseur")?.trim();
  const addr   = fd.get("adress_fournisseur")?.trim();

  if (!nom || !prenom || !tel || !addr) {
    Swal.fire({
      icon: "warning",
      title: "Attention",
      text: "Tous les champs sont obligatoires !",
    });
    return;
  }

  if (!/^\d{10}$/.test(tel)) {
    Swal.fire({
      icon: "warning",
      title: "Attention",
      text: "Le numéro doit contenir exactement 10 chiffres !",
    });
    return;
  }

  Swal.showLoading();

  $.ajax({
    url: form.action,
    method: "POST",
    dataType: "json",
    data: fd,
    processData: false,
    contentType: false,
    success: function (response) {
      Swal.close();
      Swal.fire({
        icon: response.status === "success" ? "success" : "error",
        title: response.status === "success" ? "Ajouté" : "Erreur",
        text: response.message || "",
      }).then(() => {
        if (response.status === "success") {
          form.reset();
          form.classList.remove("was-validated");
          location.reload(); 
        }
      });
    },
    error: function (xhr) {
      Swal.close();
      Swal.fire({
        icon: "error",
        title: "Erreur réseau",
        text: xhr.responseText || "Impossible d'envoyer la requête.",
      });
    },
  });
});


// form machine
$(document).on("submit", "#form-machine", function (e) {
  e.preventDefault();

  const form = this;
  const fd   = new FormData(form);

  const ref   = (fd.get("ref_machine") || "").trim();
  const nom   = (fd.get("nom_machine") || "").trim();
  const descM = (fd.get("description_machine") || "").trim();
  const four  = parseInt(fd.get("fournisseur_machine") || "0", 10);
  const stat  = (fd.get("statut_machine") || "").trim();
  if (!ref || !nom || !descM || !Number.isInteger(four) || four <= 0 || !stat) {
    Swal.fire({ icon:"warning", title:"Attention", text:"Tous les champs sont obligatoires." });
    return;
  }

  const input = form.querySelector('#image_machine');
  if (!input || !input.files || input.files.length === 0) {
    Swal.fire({ icon:"warning", title:"Image requise", text:"Ajoutez au moins une image." });
    return;
  }
  if (input.files.length > 5) {
    Swal.fire({ icon:"warning", title:"Trop d'images", text:"Vous pouvez sélectionner jusqu’à 5 images." });
    return;
  }
  for (let i = 0; i < input.files.length; i++) {
    const f = input.files[i];
    if (f.size > 5 * 1024 * 1024) {
      Swal.fire({ icon:"warning", title:"Image trop lourde", text:`${f.name} dépasse 5 Mo.` });
      return;
    }
    if (!/\.(jpg|jpeg|png|webp)$/i.test(f.name)) {
      Swal.fire({ icon:"warning", title:"Format non valide", text:`${f.name} n'est pas JPG/JPEG/PNG.` });
      return;
    }
  }

  const $btn = $(form).find('button[type="submit"]');
  const oldHtml = $btn.html();
  $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-2"></span>Envoi...');
  Swal.showLoading();

  $.ajax({
    url: form.action,
    method: "POST",
    dataType: "json",
    data: fd,
    processData: false,
    contentType: false,
    headers: { "X-Requested-With": "XMLHttpRequest" },
    success: function (response) {
      Swal.close();
      Swal.fire({
        icon: response.status === "success" ? "success" : "error",
        title: response.status === "success" ? "Ajoutée" : "Erreur",
        text: response.message || "",
      }).then(() => {
        if (response.status === "success") {
          form.reset();
          form.classList.remove("was-validated");
          location.reload();
        }
      });
    },
    error: function (xhr) {
      Swal.close();
      let msg = "Impossible d'envoyer la requête.";
      try {
        const j = JSON.parse(xhr.responseText);
        if (j && j.message) msg = j.message;
      } catch (_) {
        if (xhr.responseText) msg = xhr.responseText;
      }
      Swal.fire({ icon: "error", title: "Erreur réseau", text: "Veuillez vérifier attentivement l'exactitude des données avant validation." });
    },
    complete: function () {
      $btn.prop("disabled", false).html(oldHtml);
    },
  });
});


// form piece
$(document).on("submit", "#form-piece", function (e) {
  e.preventDefault();
  const form = this;
  const fd   = new FormData(form);

  // 🔒 si jamais action="" dans le HTML, on force l’URL attendue
  const urlAction = form.action && form.action.trim()
    ? form.action
    : "/gestionMeknassa/includes/ajax/addPiece.php";

  // ... tes validations inchangées ...

  const $btn = $(form).find('button[type="submit"]');
  $btn.prop("disabled", true);
  Swal.showLoading();

  $.ajax({
    url: urlAction,               // <= utilise la garde
    method: "POST",
    dataType: "json",
    data: fd,
    processData: false,
    contentType: false,
    headers: { "X-Requested-With": "XMLHttpRequest" },

    success: function (response) {
      Swal.close();
      Swal.fire({
        icon: response.status === "success" ? "success" : "error",
        title: response.status === "success" ? "Ajoutée" : "Erreur",
        text: response.message || "",
      }).then(() => {
        if (response.status === "success") {
          form.reset();
          form.classList.remove("was-validated");
          location.reload();
        }
      });
    },

    // ⬇Version améliorée : affiche le vrai détail de l’erreur
    error: function (xhr, status, error) {
      Swal.close();

      let serverMsg = "";
      try {
        const j = JSON.parse(xhr.responseText);
        if (j && j.message) serverMsg = j.message;
      } catch (_) {
        serverMsg = xhr.responseText || "";
      }

      const details = [
        `HTTP: ${xhr.status} ${xhr.statusText || ""}`.trim(),
        error ? `Erreur: ${error}` : "",
        serverMsg ? `Serveur: ${serverMsg}` : ""
      ].filter(Boolean).join("\n");

      console.error("[FORM-PIECE][AJAX ERROR]", { status, error, xhr, details });

      Swal.fire({
        icon: "error",
        title: "Erreur réseau",
        html: `<pre style="text-align:left;white-space:pre-wrap">${details}</pre>`
      });
    },

    complete: function () {
      $btn.prop("disabled", false);
    },
  });
});


// Update machine
$(document).on('submit.editMachine', '#editMachineForm', function (e) {
  e.preventDefault();

  const form = this;
  const fd   = new FormData(form);

  const id   = parseInt(fd.get('id_machine') || '0', 10);
  const ref  = (fd.get('ref_machine') || '').trim();
  const nom  = (fd.get('nom_machine') || '').trim();
  const desc = (fd.get('description_machine') || '').trim();
  const four = parseInt(fd.get('fournisseur_machine') || '0', 10);
  const file = form.querySelector('#e-img-file')?.files?.[0];

  if (id <= 0 || !ref || !nom || !desc || !Number.isInteger(four) || four <= 0) {
    Swal.fire({ icon:'warning', title:'Attention', text:"Tous les champs sont obligatoires !." });
    return false;
  }
  if (file) {
    if (file.size > 5 * 1024 * 1024) {
      Swal.fire({ icon:'warning', title:'Image trop lourde', text:'La taille maximale est de 5 Mo.' });
      return false;
    }
    if (!/\.(jpg|jpeg|png|webp)$/i.test(file.name)) {
      Swal.fire({ icon:'warning', title:'Format non valide', text:'Formats autorisés : JPG, JPEG, PNG, WEBP.' });
      return false;
    }
  }

  const $btn = $(form).find('button[type="submit"]');
  const oldHtml = $btn.html();
  $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement…');
  Swal.showLoading();

  $.ajax({
    url: form.action,
    method: 'POST',
    data: fd,
    dataType: 'json',
    processData: false,
    contentType: false,
    cache: false,
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    success: function (res) {
      Swal.close();
      Swal.fire({
        icon: res.status === 'success' ? 'success' : 'error',
        title: res.status === 'success' ? 'Modifiée' : 'Erreur',
        text: res.message || ''
      }).then(() => {
        if (res.status === 'success') {
          const modalEl = document.getElementById('editMachineModal');
          if (modalEl) (bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl)).hide();
          form.reset();
          form.classList.remove('was-validated');
          location.reload();
        }
      });
    },
    error: function (xhr) {
      Swal.close();
      let msg = "Impossible d'envoyer la requête.";
      try { const j = JSON.parse(xhr.responseText); if (j && j.message) msg = j.message; }
      catch (_) { if (xhr.responseText) msg = xhr.responseText; }
      Swal.fire({ icon:'error', title:'Erreur réseau', text: "Veuillez vérifier attentivement l'exactitude des données avant validation." });
    },
    complete: function () {
      $btn.prop('disabled', false).html(oldHtml);
    }
  });

  return false;
});

// update statut machine
$(document).on('submit', '#statusMachineForm', function (e) {
  e.preventDefault();

  const form = this;
  const fd   = new FormData(form);

  const id   = parseInt(fd.get('id_machine') || '0', 10);
  const stat = String(fd.get('statut') || '').trim();
  let   desc = String(fd.get('description') || '').trim();

  desc = desc.replace(/<[^>]*>/g, '');
  fd.set('description', desc);
  if (id <= 0 || !['en_marche','en_panne','en_maintenance'].includes(stat) || !desc) {
    Swal.fire({ icon:'warning', title:'Attention', text:'Tous les champs sont obligatoires.' });
    return;
  }
  if (desc.length > 500) {
    Swal.fire({ icon:'warning', title:'Trop long', text:'La description ne doit pas dépasser 500 caractères.' });
    return;
  }

  const $btn = $(form).find('button[type="submit"]');
  const oldHtml = $btn.html();
  $btn.prop('disabled', true).html(
    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Enregistrement…'
  );
  Swal.showLoading();

  $.ajax({
    url: form.action,
    method: 'POST',
    data: fd,
    dataType: 'json',
    processData: false,
    contentType: false,
    cache: false,
    headers: { 'X-Requested-With': 'XMLHttpRequest' },

    success: function (res) {
      Swal.close();
      if (res && res.status === 'success') {
        const newStat = (res.payload && res.payload.statut) || stat;
        const machineId = (res.payload && res.payload.id_machine) || id;

        const $rowBtn = $(`button.btn-status-machine[data-id="${machineId}"]`);
        const $tr     = $rowBtn.closest('tr');
        const $badge  = $tr.find('td:eq(2) .badge');

        $badge
          .removeClass('text-bg-success text-bg-danger text-bg-warning text-bg-secondary')
          .addClass(statutBadgeClass(newStat))
          .text(newStat);
        $rowBtn.data('statut', newStat);

        Swal.fire({ icon:'success', title:'OK', text: res.message || 'Statut enregistré.' })
          .then(() => {
            const el = document.getElementById('statusMachineModal');
            if (el) (bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el)).hide();
            form.reset();
         form.classList.remove('was-validated');
          location.reload();
          });
      } else {
        Swal.fire({ icon:'error', title:'Erreur', text:(res && res.message) || "Échec de l'enregistrement." });
      }
    },

    error: function (xhr) {
      Swal.close();
      let msg = "Impossible d'enregistrer.";
      try { const j = JSON.parse(xhr.responseText); if (j && j.message) msg = j.message; }
      catch (_) { if (xhr.responseText) msg = xhr.responseText; }
      Swal.fire({ icon:'error', title:'Erreur réseau', text: "Veuillez vérifier attentivement l'exactitude des données avant validation." });
    },

    complete: function () {
      $btn.prop('disabled', false).html(oldHtml);
    }
  });
});

// Update pièce 
$(document).on('submit.editPiece', '#editPieceForm', function (e) {
  e.preventDefault();

  const form = this;
  const fd   = new FormData(form);

  const id   = parseInt(fd.get('id_piece') || '0', 10);
  const ref  = (fd.get('ref_piece') || '').trim();
  const nom  = (fd.get('nom_piece') || '').trim();
  const desc = (fd.get('description_piece') || '').trim();
  const type = (fd.get('type_piece') || '').trim();
  const four = parseInt(fd.get('fournisseur_piece') || '0', 10);
  const file = fd.get('image_piece');

  if (id <= 0 || !ref || !nom || !type || !Number.isInteger(four) || four <= 0 || !desc) {
    Swal.fire({
      icon:'warning',
      title:'Attention',
      text:"Tous les champs sont obligatoires !."
    });
    return false;
  }

  if (file && file.size && file.size > 5 * 1024 * 1024) {
    Swal.fire({ icon:'warning', title:'Image trop lourde', text:'La taille maximale est de 5 Mo.' });
    return false;
  }
  if (file && file.name && !/\.(jpg|jpeg|png|webp)$/i.test(file.name)) {
    Swal.fire({ icon:'warning', title:'Format non valide', text:'Formats autorisés : JPG, JPEG, PNG, WEBP.' });
    return false;
  }

  const $btn = $(form).find('button[type="submit"]');
  const oldHtml = $btn.html();
  $btn.prop('disabled', true).html(
    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Enregistrement…'
  );
  Swal.showLoading();

  $.ajax({
    url: form.action, 
    method: 'POST',
    data: fd,
    dataType: 'json',
    processData: false,
    contentType: false,
    cache: false,
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    success: function (res) {
      Swal.close();
      Swal.fire({
        icon: res.status === 'success' ? 'success' : 'error',
        title: res.status === 'success' ? 'Modifié' : 'Erreur',
        text: res.message || ''
      }).then(() => {
        if (res.status === 'success') {
          const modalEl = document.getElementById('editPieceModal');
          if (modalEl) {
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.hide();
          }
          form.reset();
          form.classList.remove('was-validated');
          location.reload();
        }
      });
    },
    error: function (xhr) {
      Swal.close();
      let msg = "Impossible d'envoyer la requête.";
      try {
        const j = JSON.parse(xhr.responseText);
        if (j && j.message) msg = j.message;
      } catch (_) {
        if (xhr.responseText) msg = xhr.responseText;
      }
      Swal.fire({ icon:'error', title:'Erreur réseau', text: "Veuillez vérifier attentivement l'exactitude des données avant validation." });
    },
    complete: function () {
      $btn.prop('disabled', false).html(oldHtml);
    }
  });

  return false;
});

// Update stock 
$(document).on('submit.stockPiece', '#stockPieceForm', function (e) {
  e.preventDefault();

  const form = this;
  const fd   = new FormData(form);

  const id   = parseInt(fd.get('id_piece') || '0', 10);
  const type = (fd.get('mouvement_type') || '').toString();  
  const qte  = parseInt(fd.get('quantite') || '0', 10);

  let desc = (fd.get('description') || '').toString().trim();
  desc = desc.replace(/<[^>]*>/g, '');
  const MAX_DESC = 500; 
  if (desc.length > MAX_DESC) {
    Swal.fire({
      icon: 'warning',
      title: 'Description trop longue',
      text: `La description ne doit pas dépasser ${MAX_DESC} caractères.`
    });
    return false;
  }

  fd.set('description', desc);

  if (id <= 0 || !['entree','sortie'].includes(type) || !(qte > 0)) {
    Swal.fire({
      icon:'warning',
      title:'Attention',
      text:'Vérifiez le type de mouvement et la quantité (> 0).'
    });
    return false;
  }

  const $btn = $(form).find('button[type="submit"]');
  const oldHtml = $btn.html();
  $btn.prop('disabled', true).html(
    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Enregistrement…'
  );
  Swal.showLoading();

  $.ajax({
    url: form.action,                      
    method: 'POST',
    data: fd,
    dataType: 'json',
    processData: false,
    contentType: false,
    cache: false,
    headers: { 'X-Requested-With': 'XMLHttpRequest' },

    success: function (res) {
      Swal.close();
      if (res && res.status === 'success') {
        const $badge   = $('#stock-piece-qte');
        const current  = parseInt($badge.text() || '0', 10);
        const newStock = Number.isInteger(res.stock)
          ? res.stock
          : (type === 'entree' ? current + qte : current - qte);

        $badge.text(newStock);
        const $row = $(`button.btn-stock-piece[data-id="${id}"]`).closest('tr');
        $row.find('td').eq(2).text(newStock);
        $row.find('button.btn-view-piece, button.btn-edt-piece, button.btn-stock-piece')
            .each(function(){ $(this).data('qte', newStock); });

        Swal.fire({ icon:'success', title:'success', text: res.message || 'L’opération enregistré.' })
          .then(() => {
            const el = document.getElementById('stockPieceModal');
            if (el) {
              const modal = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
              modal.hide();
            }
            form.reset();
            form.classList.remove('was-validated');
            location.reload();
          });
      } else {
        Swal.fire({
          icon:'error',
          title:'Erreur',
          text:(res && res.message) || "Échec de l'enregistrement."
        });
      }
    },

    error: function (xhr) {
      Swal.close();
      let msg = "Impossible d'enregistrer.";
      try {
        const j = JSON.parse(xhr.responseText);
        if (j && j.message) msg = j.message;
      } catch (_) {
        if (xhr.responseText) msg = xhr.responseText;
      }
      Swal.fire({ icon:'error', title:'Erreur réseau', text: "Veuillez vérifier attentivement l'exactitude des données avant validation." });
    },

    complete: function () {
      $btn.prop('disabled', false).html(oldHtml);
    }
  });

  return false;
});

// EN MARCHE 
$(document).on('submit.marchePiece', '#marchePieceForm', function (e) {
  e.preventDefault();

  const form = this;
  const fd = new FormData(form);

  const id = parseInt(fd.get('id_piece') || '0', 10);
  const type = (fd.get('mouvement_type') || '').toString(); 
  const qte = parseInt(fd.get('quantite') || '0', 10);
  let desc = (fd.get('description') || '').toString().trim();
  desc = desc.replace(/<[^>]*>/g, '');

  if (id <= 0 || !['entree', 'sortie'].includes(type) || !(qte > 0) || !desc) {
    Swal.fire({ 
      icon: 'warning', 
      title: 'Attention', 
      text: 'Tous les champs sont obligatoires !' 
    });
    return false;
  }
  const $btn = $(form).find('button[type="submit"]');
  const oldHtml = $btn.html();
  $btn.prop('disabled', true).html(
    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Enregistrement…'
  );
  Swal.showLoading();

  $.ajax({
    url: form.action,
    method: 'POST',
    data: fd,
    dataType: 'json',
    processData: false,
    contentType: false,
    cache: false,
    headers: { 'X-Requested-With': 'XMLHttpRequest' },

    success: function (res) {
      Swal.close();
      if (res && res.status === 'success') {
        const p = res.payload || {};
        $('#st2-total').text(parseInt(p.q_total || 0, 10));
        $('#st2-marche').text(parseInt(p.q_marche || 0, 10));
        $('#st2-panne').text(parseInt(p.q_panne || 0, 10));
        $('#st2-maint').text(parseInt(p.q_maint || 0, 10));
        const $btnStat = $(`button.btn-status-piece[data-id="${id}"]`);
        if ($btnStat.length) {
          $btnStat
            .data('qstock', parseInt(p.q_total || 0, 10))
            .data('marche', parseInt(p.q_marche || 0, 10))
            .data('panne', parseInt(p.q_panne || 0, 10))
            .data('maint', parseInt(p.q_maint || 0, 10));
        }

        Swal.fire({ 
          icon: 'success', 
          title: 'success', 
          text: res.message || 'l’opération enregistré avec succès.' 
        }).then(() => {
          const el = document.getElementById('marchePieceModal');
          if (el) (bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el)).hide();
          form.reset();
          form.classList.remove('was-validated');
          location.reload();
          
        });
      } else {
        Swal.fire({ 
          icon: 'error', 
          title: 'Erreur', 
          text: (res && res.message) || "Échec de l'enregistrement." 
        });
      }
    },

    error: function (xhr) {
      Swal.close();
      let msg = "Impossible d'enregistrer cette opération.";
      try { 
        const j = JSON.parse(xhr.responseText); 
        if (j && j.message) msg = j.message; 
      } catch (_) { 
        if (xhr.responseText) msg = xhr.responseText; 
      }
      Swal.fire({ 
        icon: 'error', 
        title: 'Erreur réseau', 
        text: "Veuillez vérifier attentivement l'exactitude des données avant validation." 
      });
    },

    complete: function () {
      $btn.prop('disabled', false).html(oldHtml);
    }
  });

  return false;
});



// EN PANNE 
$(document).on('submit.pannePiece', '#pannePieceForm', function (e) {
  e.preventDefault();

  const form = this;
  const fd   = new FormData(form);

  const id   = parseInt(fd.get('id_piece') || '0', 10);
  const type = (fd.get('mouvement_type') || '').toString();
  const qte  = parseInt(fd.get('quantite') || '0', 10);

  let desc = (fd.get('description') || '').toString().trim();  
  desc = desc.replace(/<[^>]*>/g, '');

  if (id <= 0 || !['entree','sortie'].includes(type) || !(qte > 0) || !desc) {
    Swal.fire({ icon:'warning', title:'Attention', text:'Tous les champs sont obligatoires !' });
    return false;
  }

  const $btn = $(form).find('button[type="submit"]');
  const oldHtml = $btn.html();
  $btn.prop('disabled', true).html(
    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Enregistrement…'
  );
  Swal.showLoading();

  $.ajax({
    url: form.action,
    method: 'POST',
    data: fd,
    dataType: 'json',
    processData: false,
    contentType: false,
    cache: false,
    headers: { 'X-Requested-With': 'XMLHttpRequest' },

    success: function (res) {
      Swal.close();
      if (res && res.status === 'success') {
        const p = res.payload || {};
        $('#st2-total').text(parseInt(p.q_total || 0,10));
        $('#st2-marche').text(parseInt(p.q_marche || 0,10));
        $('#st2-panne').text(parseInt(p.q_panne || 0,10));
        $('#st2-maint').text(parseInt(p.q_maint || 0,10));

        const $btnStat = $(`button.btn-status-piece[data-id="${id}"]`);
        if ($btnStat.length){
          $btnStat
            .data('qstock', parseInt(p.q_total || 0,10))
            .data('marche', parseInt(p.q_marche || 0,10))
            .data('panne' , parseInt(p.q_panne  || 0,10))
            .data('maint' , parseInt(p.q_maint  || 0,10));
        }

        Swal.fire({ icon:'success', title:'success', text: res.message || 'l’opération enregistré.' })
          .then(() => {
            const el = document.getElementById('pannePieceModal');
            if (el) (bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el)).hide();
            form.reset();
          form.classList.remove('was-validated');
          location.reload();
          });
      } else {
        Swal.fire({ icon:'error', title:'Erreur', text:(res && res.message) || "Échec de l’enregistrement." });
      }
    },

    error: function (xhr) {
      Swal.close();
      let msg = "Impossible d'enregistrer.";
      try { const j = JSON.parse(xhr.responseText); if (j && j.message) msg = j.message; }
      catch(_) { if (xhr.responseText) msg = xhr.responseText; }
      Swal.fire({ icon:'error', title:'Erreur réseau', text: "Veuillez vérifier attentivement l'exactitude des données avant validation." });
    },

    complete: function () {
      $btn.prop('disabled', false).html(oldHtml);
    }
  });

  return false;
});



// EN MAINTENANCE 
$(document).on('submit.maintPiece', '#maintPieceForm', function (e) {
  e.preventDefault();

  const form = this;
  const fd   = new FormData(form);

  const id   = parseInt(fd.get('id_piece') || '0', 10);
  const type = (fd.get('mouvement_type') || '').toString();
  const qte  = parseInt(fd.get('quantite') || '0', 10);

  let desc = (fd.get('description') || '').toString().trim();   
  desc = desc.replace(/<[^>]*>/g, '');
  if (id <= 0 || !['entree','sortie'].includes(type) || !(qte > 0) ||  !desc) {
    Swal.fire({ icon:'warning', title:'Attention', text:'Tous les champs sont obligatoires !' });
    return false;
  }
  const $btn = $(form).find('button[type="submit"]');
  const oldHtml = $btn.html();
  $btn.prop('disabled', true).html(
    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Enregistrement…'
  );
  Swal.showLoading();

  $.ajax({
    url: form.action,
    method: 'POST',
    data: fd,
    dataType: 'json',
    processData: false,
    contentType: false,
    cache: false,
    headers: { 'X-Requested-With': 'XMLHttpRequest' },

    success: function (res) {
      Swal.close();
      if (res && res.status === 'success') {
        const p = res.payload || {};
        $('#st2-total').text(parseInt(p.q_total || 0,10));
        $('#st2-marche').text(parseInt(p.q_marche || 0,10));
        $('#st2-panne').text(parseInt(p.q_panne || 0,10));
        $('#st2-maint').text(parseInt(p.q_maint || 0,10));

        const $btnStat = $(`button.btn-status-piece[data-id="${id}"]`);
        if ($btnStat.length){
          $btnStat
            .data('qstock', parseInt(p.q_total || 0,10))
            .data('marche', parseInt(p.q_marche || 0,10))
            .data('panne' , parseInt(p.q_panne  || 0,10))
            .data('maint' , parseInt(p.q_maint  || 0,10));
        }

        Swal.fire({ icon:'success', title:'success', text: res.message || 'Mouvement enregistré.' })
          .then(() => {
            const el = document.getElementById('maintPieceModal');
            if (el) (bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el)).hide();
            form.reset();
            form.classList.remove('was-validated');
            location.reload();
          });
      } else {
        Swal.fire({ icon:'error', title:'Erreur', text:(res && res.message) || "Échec de l’enregistrement." });
      }
    },

    error: function (xhr) {
      Swal.close();
      let msg = "Impossible d'enregistrer.";
      try { const j = JSON.parse(xhr.responseText); if (j && j.message) msg = j.message; }
      catch(_) { if (xhr.responseText) msg = xhr.responseText; }
      Swal.fire({ icon:'error', title:'Erreur réseau', text: "Veuillez vérifier attentivement l'exactitude des données avant validation." });
    },

    complete: function () {
      $btn.prop('disabled', false).html(oldHtml);
    }
  });

  return false;
});


// update fournisseur
$(document).on('submit', '#editFournisseurForm', function(e) {
  e.preventDefault();

  const form = this;
  const fd   = new FormData(form);

  const id     = parseInt(fd.get('id_fournisseur') || '0', 10);
  const prenom = (fd.get('prenom_fournisseur') || '').trim();
  const nom    = (fd.get('nom_fournisseur') || '').trim();
  const tele   = String(fd.get('tele_fournisseur') || '').replace(/\D+/g, '');
  const addr   = (fd.get('adress_fournisseur') || '').trim();

  if (id <= 0 || !prenom || !nom || !tele || !addr) {
    Swal.fire({ icon:'warning', title:'Attention', text:'Tous les champs sont obligatoires !' });
    return;
  }
  if (tele.length !== 10) {
    Swal.fire({ icon:'warning', title:'Téléphone invalide', text:'Le numéro doit contenir exactement 10 chiffres.' });
    return;
  }
  fd.set('tele_fournisseur', tele);

  const $btn = $(form).find('button[type="submit"]');
  const old  = $btn.html();
  $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement…');
  Swal.showLoading();

  $.ajax({
    url: form.action,
    method: 'POST',
    data: fd,
    dataType: 'json',
    processData: false,
    contentType: false,
    cache: false,
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    success: function(res) {
      Swal.close();
      Swal.fire({
        icon: res.status === 'success' ? 'success' : 'error',
        title: res.status === 'success' ? 'Modifié' : 'Erreur',
        text: res.message || ''
      }).then(() => {
        if (res.status === 'success') {
          const el = document.getElementById('editFournisseurModal');
          if (el) (bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el)).hide();
          form.reset();
          form.classList.remove('was-validated');
          location.reload();
        }
      });
    },
    error: function(xhr) {
      Swal.close();
      let msg = "Impossible d'envoyer la requête.";
      try { const j = JSON.parse(xhr.responseText); if (j && j.message) msg = j.message; }
      catch(_) { if (xhr.responseText) msg = xhr.responseText; }
      Swal.fire({ icon:'error', title:'Erreur réseau', text: "Veuillez vérifier attentivement l'exactitude des données avant validation." });
    },
    complete: function() {
      $btn.prop('disabled', false).html(old);
    }
  });
});


// form ouvrier 
$(document).on("submit", "#form-ouvrier", function (e) {
  e.preventDefault();

  const form = this;
  const fd = new FormData(form);

  const prenom   = fd.get("prenom")?.trim();
  const nom      = fd.get("nom")?.trim();
  const cin      = fd.get("cin")?.trim();
  const tel      = fd.get("tel")?.trim();
  const fonction = fd.get("fonction")?.trim();
  const adresse  = fd.get("adresse")?.trim();

  const img_cin_file = (document.getElementById('img_cin') || {}).files?.[0];
  const photo_file = (document.getElementById('photo') || {}).files?.[0];

  if (!prenom || !nom || !cin || !tel || !fonction || !adresse || !photo_file || !img_cin_file ) {
    Swal.fire({ icon: "warning", title: "Attention", text: "Tous les champs texte sont obligatoires !" });
    return;
  }


  if (!/^\d{10}$/.test(tel)) {
    Swal.fire({ icon: "warning", title: "Attention", text: "Le numéro doit contenir exactement 10 chiffres !" });
    return;
  }


  const maxSize = 5 * 1024 * 1024; 
  const allowed = ['image/jpeg', 'image/png'];

  if (!allowed.includes(img_cin_file.type) || !allowed.includes(photo_file.type)) {
    Swal.fire({ icon: "warning", title: "Attention", text: "Les fichiers doivent être JPG ou PNG." });
    return;
  }

  if (img_cin_file.size > maxSize || photo_file.size > maxSize) {
    Swal.fire({ icon: "warning", title: "Attention", text: "Chaque fichier doit être inférieur à 5MB." });
    return;
  }

  Swal.showLoading();

  $.ajax({
    url: form.action, 
    method: "POST",
    dataType: "json",
    data: fd,
    processData: false,
    contentType: false,
    headers: { "X-Requested-With": "XMLHttpRequest" },
    success: function (response) {
      Swal.close();
      Swal.fire({
        icon: response.status === "success" ? "success" : "error",
        title: response.status === "success" ? "Ajouté" : "Erreur",
        text: response.message || "",
      }).then(() => {
        if (response.status === "success") {
          form.reset();
          form.classList.remove("was-validated");
          location.reload();
        }
      });
    },
    error: function (xhr) {
      Swal.close();
      let msg = "Impossible d'envoyer la requête.";
      try {
        const j = JSON.parse(xhr.responseText);
        if (j && j.message) msg = j.message;
      } catch (_) {
        if (xhr.responseText) msg = xhr.responseText;
      }
      Swal.fire({ icon: "error", title: "Erreur", text: msg });
    }
  });
});

// form avance 
$(document).on("submit", "#form-avance", function (e) {
  e.preventDefault();

  const form = this;
  const fd = new FormData(form);

  const ouvrier = parseInt(fd.get("ouvrier_id") || "0", 10);
  const montant = (fd.get("avance") || "").toString().trim();
  const motif   = (fd.get("motif") || "").toString().trim();

  if (!ouvrier || !montant) {
    Swal.fire({ icon: "warning", title: "Attention", text: "Champs obligatoires manquants !" });
    return;
  }

  const montantNorm = parseFloat(montant.replace(",", "."));
  if (!(Number.isFinite(montantNorm) && montantNorm > 0)) {
    Swal.fire({ icon: "warning", title: "Montant invalide", text: "Le montant doit être un nombre supérieur à 0." });
    return;
  }

  Swal.showLoading();

  $.ajax({
    url: form.action,
    method: "POST",
    dataType: "json",
    data: fd,
    processData: false,
    contentType: false,
    headers: { "X-Requested-With": "XMLHttpRequest" },
    success: function (response) {
      Swal.close();

      if (response && response.status === "success") {
        Swal.fire({
          icon: "success",
          title: "Enregistré",
          text: response.message || "Avance ajoutée.",
          confirmButtonText: "Imprimer le reçu",
          showCancelButton: true,
          cancelButtonText: "Fermer"
        }).then((resSwal) => {
          if (resSwal.isConfirmed) {
            const id = response.id || '';
            if (id) {
     const url = '/gestionMeknassa/avances/print_avance.php?id=' + encodeURIComponent(id);
              window.open(url, '_blank');
            } else {
              window.open('', '_blank');
            }
          }
          form.reset();
          form.classList.remove('was-validated');
          location.reload();
        });
      } else {
        Swal.fire({ icon: "error", title: "Erreur", text: (response && response.message) || "Erreur inconnue." });
      }
    },
    error: function (xhr) {
      Swal.close();
      let msg = "Impossible d'envoyer la requête.";
      try {
        const j = JSON.parse(xhr.responseText);
        if (j && j.message) msg = j.message;
      } catch (_) {
        if (xhr.responseText) msg = xhr.responseText;
      }
      Swal.fire({ icon: "error", title: "Erreur", text: "Veuillez vérifier attentivement l'exactitude des données avant validation." });
    }
  });
});


// Update ouvrie
$(document).on('submit', '#editOuvrierForm', function(e){
    e.preventDefault();
    const form = this;
    const fd = new FormData(form);

    const tele = String(fd.get('tele') || '').replace(/\D+/g,'');
    fd.set('tele', tele);

    const required = ['id_ouvrier','prenom','nom','cin','fonction','tele','adress'];
    for(const f of required){
        if(!fd.get(f) || fd.get(f).trim() === ''){
            Swal.fire({icon:'warning', title:'Attention', text:'Tous les champs sont obligatoires !'});
            return;
        }
    }
    if(tele.length !== 10){
        Swal.fire({icon:'warning', title:'Téléphone invalide', text:'Le numéro doit contenir exactement 10 chiffres.'});
        return;
    }

    const $btn = $(form).find('button[type="submit"]');
    const old = $btn.html();
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement…');
    Swal.showLoading();

    $.ajax({
        url: form.action,
        type: 'POST',
        data: fd,
        dataType: 'json',
        processData: false,
        contentType: false,
        cache: false,
        success: function(res){
            Swal.close();
            Swal.fire({
                icon: res.status==='success' ? 'success':'error',
                title: res.status==='success' ? 'Modifié':'Erreur',
                text: res.message || ''
            }).then(()=>{
                if(res.status==='success'){
                    const modal = document.getElementById('editOuvrierModal');
                    if(modal) (bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal)).hide();
                    form.reset();
                    form.classList.remove('was-validated');
                    location.reload();
                }
            });
        },
        error: function(xhr){
            Swal.close();
            let msg = "Impossible d'envoyer la requête.";
            try { const j = JSON.parse(xhr.responseText); if(j && j.message) msg = j.message; } catch{}
            Swal.fire({icon:'error', title:'Erreur réseau', text: msg });
        },
        complete: function(){ $btn.prop('disabled', false).html(old); }
    });
});

// reglement
$(document).on('submit', '#form-reglement', function (e) {
  e.preventDefault();

  const form = this;
  const $form = $(form);
  const fd = new FormData(form);

  const reference = (fd.get('reference_facture') || '').toString().trim();
  const fournisseur = parseInt(fd.get('id_fournisseur') || 0, 10);
  const is_paye = fd.get('is_paye');
  const maxPieces = 50;

  if (!reference || !fournisseur || !is_paye) {
    Swal.fire({
      icon: 'warning',
      title: 'Champs manquants',
      text: 'Veuillez remplir tous les champs obligatoires (référence, fournisseur, situation).'
    });
    return;
  }


  if (is_paye === 'Payé') {
    const payeBlocks = document.querySelectorAll('#paye-container .payment-block');
    if (payeBlocks.length === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Aucun paiement ajouté',
        text: 'Cliquez sur "+ Ajouter un paiement" avant d’enregistrer.'
      });
      return;
    }
  }

  if (is_paye === 'Avance') {
    const avanceBlocks = document.querySelectorAll('#avance-container .payment-block');
    if (avanceBlocks.length === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Aucune avance ajoutée',
        text: 'Cliquez sur "+ Ajouter une avance" avant d’enregistrer.'
      });
      return;
    }
  }

  let piecesCount = 0;
  for (let pair of fd.entries()) {
    const key = pair[0];
    if (key.startsWith('pieces[') && key.endsWith('][nom]')) piecesCount++;
  }

  if (piecesCount === 0 || piecesCount > maxPieces) {
    Swal.fire({
      icon: 'warning',
      title: 'Erreur dans les pièces',
      text: `Vous devez ajouter entre 1 et ${maxPieces} pièce(s).`
    });
    return;
  }

  const $btn = $form.find('button[type="submit"]');
  const oldHtml = $btn.html();
  $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement…');

  Swal.fire({
    title: 'Envoi en cours...',
    allowOutsideClick: false,
    didOpen: () => { Swal.showLoading(); }
  });

  $.ajax({
    url: $form.attr('action'),
    type: 'POST',
    data: fd,
    dataType: 'json',
    processData: false,
    contentType: false,
    cache: false,
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    success: function (res) {
      Swal.close();

      if (res && res.status === 'success') {
        Swal.fire({
          icon: 'success',
          title: 'Règlement enregistré',
          text: res.message || 'Les données ont été sauvegardées avec succès.',
          confirmButtonText: 'Imprimer le reçu',
          showCancelButton: true,
          cancelButtonText: 'Fermer'
        }).then((r) => {
          if (r.isConfirmed && res.id_reglement) {
            const url = '/gestionMeknassa/inventaire/print_reglement.php?id=' + encodeURIComponent(res.id_reglement);
            window.open(url, '_blank');
          }
          form.reset();
          location.reload();
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Erreur serveur',
          text: (res && res.message) || 'Erreur inconnue lors de l’enregistrement.'
        });
      }
    },
    error: function (xhr) {
      Swal.close();
      let msg = "Impossible d’envoyer la requête. Veuillez vérifier votre connexion.";
      try {
        const j = JSON.parse(xhr.responseText);
        if (j && j.message) msg = j.message;
      } catch (_) { }
      Swal.fire({ icon: 'error', title: 'Erreur réseau', text: msg });
    },
    complete: function () {
      $btn.prop('disabled', false).html(oldHtml);
    }
  });
});




$(document).on("submit", "#addClientForm", function (e) {
  e.preventDefault();

  const form = this;
  const fd = new FormData(form);
  const nom = fd.get("nom")?.trim();
  const prenom = fd.get("prenom")?.trim();
  const num_telephone = fd.get("num_telephone")?.trim();
  const ville = fd.get("ville")?.trim();
  const address_client = fd.get("address_client")?.trim();

  if (!nom || !prenom || !num_telephone || !ville || !address_client) {
    Swal.fire({
      icon: "warning",
      text: "Tout les champs sont obligatoire!",
      title: "Attention",
    });
    return;
  }
  Swal.showLoading();

  $.ajax({
    url: "../includes/ajax/Huile/Client-Huile/addClient.php",
    method: "POST",
    dataType: "JSON",
    data: fd,
    processData: false, // <= required for FormData
    contentType: false, // <= required for FormData
    success: function (response) {
      Swal.close();
      Swal.fire({
        icon: response.status === "success" ? "success" : "error",
        title: response.status === "success" ? "Ajouté" : "Erreur",
        text: response.message || "",
      }).then(() => {
        if (response.status === "success") location.reload();
      });
    },
    error: function (xhr) {
      Swal.close();
      Swal.fire({
        icon: "error",
        title: "Erreur réseau",
        text: xhr.responseText || "Impossible d'envoyer la requête.",
      });
    },
  });
});



//==================================== ADD Client Oubliable ==============================================


$(document).on("submit", "#addClientOublier", function (e) {
  e.preventDefault();

  const form = this;
  const fd = new FormData(form);

  // Récupération et nettoyage des champs
  const nom = fd.get("nom_client_forget")?.trim();
  const prenom = fd.get("prenom_client_forget")?.trim();
  const cin = fd.get("cin_bon_client_forget")?.trim();
  const refBon = fd.get("ref_bon_forg")?.trim();
  const qte = fd.get("qte_bon_client_forget")?.trim();
  const provider = fd.get("provider_client_forget")?.trim();
  const tel = fd.get("num_tel_client_forget")?.trim();

  // Validation basique
  if (!nom || !prenom || !cin || !refBon || !qte || !provider || !tel) {
    Swal.fire({
      icon: "warning",
      title: "Attention",
      text: "Tous les champs obligatoires doivent être remplis !",
    });
    return;
  }

  Swal.fire({
    title: "Ajout en cours...",
    text: "Veuillez patienter.",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: "../includes/ajax/Oubliable/addClientOublier.php",
    method: "POST",
    dataType: "JSON",
    data: fd,
    processData: false, // Obligatoire pour FormData
    contentType: false, // Obligatoire pour FormData

    success: function (response) {
      Swal.close();

      Swal.fire({
        icon: response.status === "success" ? "success" : "error",
        title: response.status === "success" ? "Ajouté" : "Erreur",
        text: response.message || "",
      }).then(() => {
        if (response.status === "success") {
          form.reset();
          location.reload();
        }
      });
    },

    error: function (xhr) {
      Swal.close();
      Swal.fire({
        icon: "error",
        title: "Erreur réseau",
        text: xhr.responseText || "Une erreur s'est produite lors de l'envoi du formulaire.",
      });
    },
  });
});







//==================================== End Client Oubliable ==============================================





//==================================== ADD BIDON ==============================================


$(document).on("submit", "#addBidonForm", function (e) {
  e.preventDefault();

  const form = this;
  const fd = new FormData(form);
  const nom_bidon = fd.get("nom_bidon")?.trim();
  const type_bidon = fd.get("type_bidon")?.trim();
  const prix_bidon = fd.get("prix_bidon")?.trim();
  const quantite_bidon = fd.get("qte_bidon")?.trim();
 

  if (!nom_bidon || !type_bidon || !prix_bidon || !quantite_bidon ) {
    Swal.fire({
      icon: "warning",
      text: "Tout les champs sont obligatoire!",
      title: "Attention",
    });
    return;
  }
  Swal.showLoading();

  $.ajax({
    url: "../includes/ajax/Huile/Bidon-Huile/addBidon.php",
    method: "POST",
    dataType: "JSON",
    data: fd,
    processData: false, // <= required for FormData
    contentType: false, // <= required for FormData
    success: function (response) {
      Swal.close();
      Swal.fire({
        icon: response.status === "success" ? "success" : "error",
        title: response.status === "success" ? "Ajouté" : "Erreur",
        text: response.message || "",
      }).then(() => {
        if (response.status === "success") location.reload();
      });
    },
    error: function (xhr) {
      Swal.close();
      Swal.fire({
        icon: "error",
        title: "Erreur réseau",
        text: xhr.responseText || "Impossible d'envoyer la requête.",
      });
    },
  });
});


// ==================================== BEGIN UPDATE BON HUILE CLIENT ===================================================

// Update Bon d’Huile
$(document).on("submit", "#formUpdateBonHuile", function (e) {
  e.preventDefault();

  const form = this;
  const fd = new FormData(form);

  const id_bon = fd.get("id_bon")?.trim();
  const quantite = fd.get("quantite")?.trim();
  const prix_unitaire = fd.get("prix_unitaire")?.trim();
  const mode = fd.get("mode_paiement_huile")?.trim();
  const note = fd.get("note")?.trim();

  // --- Validation
  if (!id_bon || !quantite || !prix_unitaire) {
    Swal.fire({
      icon: "warning",
      title: "Champs manquants",
      text: "Veuillez remplir tous les champs obligatoires.",
    });
    return;
  }

  // ---- Ajouter les lignes de bidon si présentes dans la table
  $("#tableBidonTypes tbody tr").each(function () {
    const type = $(this).find(".sel-type").val()?.trim();
    const count = $(this).find(".inp-count").val()?.trim();
    if (type && count && parseFloat(count) > 0) {
      fd.append("detail_type[]", type);
      fd.append("detail_count[]", count);
    }
  });

  Swal.fire({
    title: "Mise à jour en cours...",
    text: "Veuillez patienter...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: "../includes/ajax/Huile/Bidon-Huile/updateBon_Huile.php",
    method: "POST",
    dataType: "JSON",
    data: fd,
    processData: false,
    contentType: false,
    success: function (response) {
      Swal.close();

      if (response.ok) {
        Swal.fire({
          icon: "success",
          title: "Bon mis à jour",
          text: response.message || "Le bon et le stock ont été mis à jour avec succès.",
        }).then(() => {
          $("#bhModalUpdate").modal("hide"); // ferme le modal
          if (typeof load === "function") load(); // recharge la liste si dispo
        });
      } else {
        Swal.fire({
          icon: "error",
          title: "Erreur",
          text: response.error || "Une erreur est survenue lors de la mise à jour.",
        });
      }
    },
    error: function (xhr) {
      Swal.close();
      Swal.fire({
        icon: "error",
        title: "Erreur serveur",
        text: xhr.responseText || "Impossible d’envoyer la requête au serveur.",
      });
    },
  });
});

//======================= Update Ajax Statut Fitor Taratonage ============================


// Update Taratonage
$(document).on("submit", "#editTaratonageForm", function (e) {
  e.preventDefault();

  const form = this;
  const fd = new FormData(form);

  const id_taratonage = fd.get("id_taratonage")?.trim();
  const poid_brut = parseFloat(fd.get("poid_brut")) || 0;
  const poid_taratone = parseFloat(fd.get("poid_taratone")) || 0;
  const note = fd.get("note")?.trim();

  const poid_net = poid_brut - poid_taratone;

  // --- Validation
  if (!id_taratonage || !poid_brut || !poid_taratone) {
    Swal.fire({
      icon: "warning",
      title: "Champs manquants",
      text: "Veuillez remplir tous les champs obligatoires.",
    });
    return;
  }

  // --- Update automatic net weight in field
  $("#e_poid_net").val(poid_net.toFixed(2));

  // --- Send AJAX
   $.ajax({
    url: "../includes/ajax/Fitor/Taratonage/update_taratonage.php",
    method: "POST",
    dataType: "JSON",
    data: fd,
    processData: false,
    contentType: false,
    success: function (response) {
      Swal.close();

      if (response.ok) {
        Swal.fire({
          icon: "success",
          title: "Taratonage mis à jour",
          text: response.message || "Taratonage mis à jour avec succès !",
        }).then(() => {
          $("#bhModalUpdate").modal("hide"); // ferme le modal
          if (typeof load === "function") load(); // recharge la liste si dispo
        });
      } else {
        Swal.fire({
          icon: "error",
          title: "Erreur",
          text: response.error || "Une erreur est survenue lors de la mise à jour.",
        });
      }
    },
    error: function (xhr) {
      Swal.close();
      Swal.fire({
        icon: "error",
        title: "Erreur serveur",
        text: xhr.responseText || "Impossible d’envoyer la requête au serveur.",
      });
    },
  });
});


// Update Taratonage transporteur
$(document).on("submit", "#editTaratonageFormTransporteur", function (e) {
  e.preventDefault();

  const form = this;
  const fd = new FormData(form);

  const id_taratonage = fd.get("id_taratonage_trans")?.trim();
  const poid_brut = parseFloat(fd.get("poid_brut_trans")) || 0;
  const poid_taratone = parseFloat(fd.get("poid_taratone_trans")) || 0;
  const note = fd.get("note_trans")?.trim();

  const poid_net = poid_brut - poid_taratone;

  // --- Validation
  if (!id_taratonage || !poid_brut || !poid_taratone) {
    Swal.fire({
      icon: "warning",
      title: "Champs manquants",
      text: "Veuillez remplir tous les champs obligatoires.",
    });
    return;
  }

  // --- Update automatic net weight in field
  $("#e_poid_net").val(poid_net.toFixed(2));

  // --- Send AJAX
   $.ajax({
    url: "../includes/ajax/Transporteur/Taratonage/update_taratonage.php",
    method: "POST",
    dataType: "JSON",
    data: fd,
    processData: false,
    contentType: false,
    success: function (response) {
      Swal.close();

      if (response.ok) {
        Swal.fire({
          icon: "success",
          title: "Taratonage mis à jour",
          text: response.message || "Taratonage mis à jour avec succès !",
        }).then(() => {
          $("#bhModalUpdate").modal("hide"); // ferme le modal
          if (typeof load === "function") load(); // recharge la liste si dispo
        });
      } else {
        Swal.fire({
          icon: "error",
          title: "Erreur",
          text: response.error || "Une erreur est survenue lors de la mise à jour.",
        });
      }
    },
    error: function (xhr) {
      Swal.close();
      Swal.fire({
        icon: "error",
        title: "Erreur serveur",
        text: xhr.responseText || "Impossible d’envoyer la requête au serveur.",
      });
    },
  });
});


//======================= End Of Update Ajax Statut Fitor Taratonage ============================


$(document).ready(function () {
  // Load details when clicking "Détails"
  $(document).on("click", "[data-action='details']", function () {
    const idBon = $(this).data("id");
    $("#bh_modal_id").text(idBon);
    const tbody = $("#bh_modal_tbody");
    tbody.html(`<tr><td colspan="5" class="text-center text-muted">Chargement...</td></tr>`);

    fetch(`../includes/ajax/Huile/Bidon-Huile/getBon_HuileClient.php?id_bon=${idBon}`)
      .then((r) => r.json())
      .then((data) => {
        if (!data.ok) throw new Error(data.error || "Erreur inconnue");
        tbody.empty();
        data.details.forEach((d) => {
          tbody.append(`
            <tr data-id="${d.id_detail}">
              <td>${d.nom_bidon}</td>
              <td>${d.type_bidon}</td>
              <td class="text-end">
                <input type="number" class="form-control form-control-sm text-end qte-bidon" value="${d.quantite_bidon}" min="0">
              </td>
              <td class="text-end">${parseFloat(d.prix_bidon).toFixed(2)} DH</td>
              <td class="text-end">
                <button class="btn btn-sm btn-primary btn-save-qte">💾</button>
              </td>
            </tr>
          `);
        });
      })
      .catch((err) => {
        tbody.html(`<tr><td colspan="5" class="text-center text-danger">${err.message}</td></tr>`);
      });

    $("#bhModalDetails").modal("show");
  });

  // Save quantity update
  $(document).on("click", ".btn-save-qte", function () {
    const tr = $(this).closest("tr");
    const id_detail = tr.data("id");
    const quantite = parseFloat(tr.find(".qte-bidon").val() || 0);

    if (quantite <= 0) {
      Swal.fire({ icon: "warning", title: "Quantité invalide", text: "Veuillez saisir une quantité positive." });
      return;
    }

    Swal.fire({
      title: "Mise à jour...",
      text: "Veuillez patienter...",
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });

    $.ajax({
      url: "../includes/ajax/Huile/Bidon-Huile/updateBidon_Qte.php",
      method: "POST",
      dataType: "JSON",
      data: { id_detail, quantite_bidon: quantite },
      success: function (res) {
        Swal.close();
        if (res.ok) {
          Swal.fire({ icon: "success", title: "Modifié", text: res.message });
        } else {
          Swal.fire({ icon: "error", title: "Erreur", text: res.error });
        }
      },
      error: function (xhr) {
        Swal.close();
        Swal.fire({ icon: "error", title: "Erreur serveur", text: xhr.responseText || "Erreur réseau." });
      },
    });
  });
});


// ==================================== END UPDATE BON HUILE CLIENT ===================================================
// ==================================== ADD BON HUILE CLIENT ===================================================


$(document).on("submit", "#addBon_HuileClient", function (e) {
  e.preventDefault();

  const form = this;
  const fd = new FormData(form);

  // --- Read fields with the correct names (match your HTML)
  const client        = (fd.get("List_client_name") || "").trim();
  const quantiteStr   = (fd.get("quantite_en_litre") || "").trim();   
  const prixUnitStr   = (fd.get("prix_unitaire") || "").trim();
  const prixNetStr    = (fd.get("prix_net") || "").trim();
  const provider      = (fd.get("provider_of_price") || "").trim();
  const mode_paiment_huile      = (fd.get("mode_paiement_huile") || "").trim();
  const note          = (fd.get("note_bon_huile") || "").trim();

  // --- Bidon detail arrays come from <select name="detail_type[]"> & <input name="detail_count[]">
  const types  = fd.getAll("detail_type[]").map(v => (v || "").trim());
  const counts = fd.getAll("detail_count[]").map(v => Number(v || 0));

  // --- Build compact lines (type with count > 0)
  const lines = [];
  for (let i = 0; i < Math.max(types.length, counts.length); i++) {
    const t = (types[i] || "").toUpperCase().trim();
    const c = Number(counts[i] || 0);
    if (t && c > 0) lines.push({ type: t, count: c });
  }

  // --- Helper to parse litres from "5L", "10 L", etc.
  const typeToLiters = (t) => {
    const m = String(t).toUpperCase().match(/(\d+(?:\.\d+)?)\s*L/);
    return m ? parseFloat(m[1]) : 0;
  };

  const litresFromLines = lines.reduce((sum, r) => sum + typeToLiters(r.type) * r.count, 0);

  // --- Parse numerics
  const qteLitres = quantiteStr ? Number(quantiteStr) : 0;
  const prixUnit  = prixUnitStr ? Number(prixUnitStr) : 0;
  const prixNet   = prixNetStr ? Number(prixNetStr) : 0;

  // --- Basic requireds common to all modes
  if (!client || !provider) {
    Swal.fire({ icon: "warning", title: "Attention", text: "Client et provider sont obligatoires." });
    return;
  }

  // --- Decide the mode and validate accordingly
  const hasOil    = qteLitres > 0;         // user filled global litres
  const hasBidons = lines.length > 0;      // user added at least one bidon line

  if (!hasOil && !hasBidons) {
    Swal.fire({ icon: "warning", title: "Attention", text: "Saisissez une quantité (litres) ou ajoutez au moins un bidon." });
    return;
  }

  // Huile seule (global litres only)
  if (hasOil && !hasBidons) {
    if (prixUnit <= 0) {
      Swal.fire({ icon: "warning", title: "Attention", text: "Prix unitaire requis pour la vente d’huile seule." });
      return;
    }
    if (prixNet <= 0) {
      Swal.fire({ icon: "warning", title: "Attention", text: "Le prix net est vide. Vérifiez le calcul automatique." });
      return;
    }
  }

  // Bidons seuls (no global litres)
  if (!hasOil && hasBidons) {
    // For bidon-only, prix unitaire n’est pas indispensable (le backend calcule à partir des prix bidon)
    // Nothing extra required here.
  }

  // Les deux (global litres + bidons)
  if (hasOil && hasBidons) {
    // Petit avertissement si les litres diffèrent de façon notable
    const diff = Math.abs(qteLitres - litresFromLines);
    if (diff > 0.01) {
      // Ask user to confirm before submitting
      Swal.fire({
        icon: "question",
        title: "Litres différents",
        html: `La quantité globale (${qteLitres.toFixed(2)} L) est différente du total des bidons (${litresFromLines.toFixed(2)} L).<br>Continuer quand même ?`,
        showCancelButton: true,
        confirmButtonText: "Oui, continuer",
        cancelButtonText: "Annuler",
      }).then((res) => {
        if (res.isConfirmed) submitAjax();
      });
      return; // wait for confirmation
    }
  }

  // --- Submit via AJAX to the exact form action (avoids URL mismatch)
  submitAjax();

  function submitAjax() {
    Swal.showLoading();

    $.ajax({
      url: form.action || "../includes/ajax/Huile/Bidon-Huile/addBon_HuileClient.php", // fallback
      method: "POST",
      dataType: "json",
      data: fd,
      processData: false,
      contentType: false,
      success: function (response) {
        Swal.close();
        Swal.fire({
          icon: response.status === "success" ? "success" : "error",
          title: response.status === "success" ? "Ajouté" : "Erreur",
          text: response.message || "",
          allowOutsideClick: false
        }).then(() => {
          if (response.status === "success") location.reload();
        });
      },
      error: function (xhr) {
        Swal.close();
        // Try to parse JSON error if available
        let msg = "Impossible d'envoyer la requête.";
        try {
          const p = JSON.parse(xhr.responseText);
          msg = p.message || msg;
        } catch (_) {
          msg = xhr.responseText || msg;
        }
        Swal.fire({
          icon: "error",
          title: "Erreur",
          text: msg,
        });
      },
    });
  }
});

// ==================================== FIN ADD HUILE CLIENT ==================================================


//=================================== ADD BON BIDON CLIENT ====================================================



$(document).on("submit", "#addBon_BidonClient", function (e) {
  e.preventDefault();

  const form = this;
  const fd = new FormData(form);

  // -- Fields from the form
  const client = String(fd.get("List_client_name") || "").trim();
  const note   = String(fd.get("note_bon_huile") || "").trim();

  // -- Arrays from the table
  const types  = fd.getAll("detail_type[]").map(v => String(v || "").trim().toUpperCase());
  const counts = fd.getAll("detail_count[]").map(v => Number(v || 0));

  // -- Merge same types + keep only positive counts
  const merged = new Map();
  for (let i = 0; i < Math.max(types.length, counts.length); i++) {
    const t = types[i] || "";
    const c = counts[i] || 0;
    if (t && c > 0) merged.set(t, (merged.get(t) || 0) + c);
  }

  // -- Minimal validations
  if (!client) {
    Swal.fire({ icon: "warning", title: "Attention", text: "Sélectionnez un client." });
    return;
  }
  if (merged.size === 0) {
    Swal.fire({ icon: "warning", title: "Attention", text: "Ajoutez au moins une ligne de bidon avec une quantité > 0." });
    return;
  }

  // -- Helper: parse "5L", "10 L", "20l" -> 5, 10, 20
  const typeToLiters = (t) => {
    const m = String(t).toUpperCase().match(/(\d+(?:\.\d+)?)\s*L/);
    return m ? parseFloat(m[1]) : 0;
  };

  // -- Compute small UX summary (litres & montant if visible in table)
  let totalLitres = 0;
  for (const [t, c] of merged.entries()) totalLitres += typeToLiters(t) * c;

  // If your table shows per-row prices, we can read the footer sum for info only
  const footerMontantEl = document.getElementById("sumMontant");
  const totalMontantTxt = footerMontantEl ? footerMontantEl.textContent.trim() : "";

  // -- Rebuild FormData with merged lines (clean payload)
  // Clear existing detail arrays first
  ["detail_type[]", "detail_count[]"].forEach(k => fd.delete(k));
  for (const [t, c] of merged.entries()) {
    fd.append("detail_type[]", t);
    fd.append("detail_count[]", String(c));
  }
  // (Optional) keep note as-is; backend may ignore/store it later
  fd.set("note_bon_huile", note);

  // -- Confirm submit (nice UX)
  Swal.fire({
    icon: "question",
    title: "Confirmer l’enregistrement",
    html: `
      <div class="text-start">
        <div><b>Client:</b> ${client}</div>
        <div><b>Total litres estimés:</b> ${totalLitres.toFixed(2)} L</div>
        ${totalMontantTxt ? `<div><b>Montant estimé:</b> ${totalMontantTxt} DH</div>` : ""}
        ${note ? `<div><b>Note:</b> ${$('<div/>').text(note).html()}</div>` : ""}
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: "Enregistrer",
    cancelButtonText: "Annuler"
  }).then((res) => {
    if (!res.isConfirmed) return;

    // Disable submit to avoid double click
    const $btn = $(form).find('[type="submit"], #btnSubmitBon').prop('disabled', true);
    Swal.showLoading();

    $.ajax({
      url: form.action || "../includes/ajax/Huile/Bidon-Huile/addBon_BidonClient.php",
      method: "POST",
      dataType: "json",
      data: fd,
      processData: false,
      contentType: false,
      success: function (response) {
        Swal.close();
        Swal.fire({
          icon: response.status === "success" ? "success" : "error",
          title: response.status === "success" ? "Ajouté" : "Erreur",
          text: response.message || "",
          allowOutsideClick: false
        }).then(() => {
          if (response.status === "success") {
            form.reset?.();
            // Optionally, clear table body manually if you added rows dynamically
            $("#tableBidonTypes tbody").empty();
            // Reload so stock & list update
            location.reload();
          }
        });
      },
      error: function (xhr) {
        Swal.close();
        let msg = "Impossible d'envoyer la requête.";
        try {
          const p = JSON.parse(xhr.responseText || "{}");
          msg = p.message || msg;
        } catch (_) {
          msg = xhr.responseText || msg;
        }
        Swal.fire({ icon: "error", title: "Erreur", text: msg });
      },
      complete: function () {
        $btn.prop('disabled', false);
      }
    });
  });
});














//=================================== END BON BIDON CLIENT ====================================================

// Ajout du Patron
$(document).on("submit", "#addPatronFitour", function (e) {
  e.preventDefault();

  const form = this;
  const fd = new FormData(form);
  const nom = fd.get("nom_patron")?.trim();
  const prenom = fd.get("prenom_patron")?.trim();
  const telephone = fd.get("num_tel_patron")?.trim();

  if (!nom || !prenom || !telephone) {
    Swal.fire({
      icon: "warning",
      text: "Tout les champs sont obligatoire!",
      title: "Attention",
    });
    return;
  }
  Swal.showLoading();

  $.ajax({
    url: "../includes/ajax/Fitor/addPatronFitour.php",
    method: "POST",
    dataType: "JSON",
    data: fd,
    processData: false, // <= required for FormData
    contentType: false, // <= required for FormData
    success: function (response) {
      Swal.close();
      Swal.fire({
        icon: response.status === "success" ? "success" : "error",
        title: response.status === "success" ? "Ajouté" : "Erreur",
        text: response.message || "",
      }).then(() => {
        if (response.status === "success") location.reload();
      });
    },
    error: function (xhr) {
      Swal.close();
      Swal.fire({
        icon: "error",
        title: "Erreur réseau",
        text: xhr.responseText || "Impossible d'envoyer la requête.",
      });
    },
  });
});

// Ajout Chauffeur de Patron
$(document).on("submit", "#addChauffeurFitor", function (e) {
  e.preventDefault();

  const form = this;
  const fd = new FormData(form);
  const nom = fd.get("nom_Fchauffeur")?.trim();
  const prenom = fd.get("prenom_Fchauffeur")?.trim();
  const telephone = fd.get("num_Fchauffeur")?.trim();
  const patron = fd.get("List_patron_name")?.trim();

  if (!nom || !prenom || !telephone || !patron) {
    Swal.fire({
      icon: "warning",
      text: "Tout les champs sont obligatoire!",
      title: "Attention",
    });
    return;
  }
  Swal.showLoading();

  $.ajax({
    url: "../includes/ajax/Fitor/addChauffeurFitor.php",
    method: "POST",
    dataType: "JSON",
    data: fd,
    processData: false, // <= required for FormData
    contentType: false, // <= required for FormData
    success: function (response) {
      Swal.close();
      Swal.fire({
        icon: response.status === "success" ? "success" : "error",
        title: response.status === "success" ? "Ajouté" : "Erreur",
        text: response.message || "",
      }).then(() => {
        if (response.status === "success") location.reload();
      });
    },
    error: function (xhr) {
      Swal.close();
      Swal.fire({
        icon: "error",
        title: "Erreur réseau",
        text: xhr.responseText || "Impossible d'envoyer la requête.",
      });
    },
  });
});

//add Vehicule Fitour
$(document).on("submit", "#addVehiculeFitor", function (e) {
  e.preventDefault();

  const form = this;
  const fd = new FormData(form);
  const Fchauffeur = fd.get("List_F_chauffeur")?.trim();
  const type_vehicule = fd.get("List_type_vehicule")?.trim();
  const nom_vehicule = fd.get("nom_vehicule")?.trim();
  const matricule = fd.get("matricule_vehicule")?.trim();
  const type_assurance = fd.get("List_type_assurance")?.trim();
  const date_exp_assurance = fd.get("date_expiration")?.trim();
  const couleur_vehicule = fd.get("List_couleur_vehicule")?.trim();

  if (
    !Fchauffeur ||
    !type_vehicule ||
    !nom_vehicule ||
    !matricule ||
    !couleur_vehicule
  ) {
    Swal.fire({
      icon: "warning",
      text: "Tout les champs sont obligatoire!",
      title: "Attention",
    });
    return;
  }
  Swal.showLoading();

  $.ajax({
    url: "../includes/ajax/Fitor/addVehiculeFitor.php",
    method: "POST",
    dataType: "JSON",
    data: fd,
    processData: false, // <= required for FormData
    contentType: false, // <= required for FormData
    success: function (response) {
      Swal.close();
      Swal.fire({
        icon: response.status === "success" ? "success" : "error",
        title: response.status === "success" ? "Ajouté" : "Erreur",
        text: response.message || "",
      }).then(() => {
        if (response.status === "success") location.reload();
      });
    },
    error: function (xhr) {
      Swal.close();
      Swal.fire({
        icon: "error",
        title: "Erreur réseau",
        text: xhr.responseText || "Impossible d'envoyer la requête.",
      });
    },
  });
});

// ADD TARATONAGE FITOR

$(document).on("submit", "#addTaratonageFitour", function (e) {
  e.preventDefault();

  const form = this;
  const fd = new FormData(form);
  const Fchauffeur_Vehicule = fd.get("F_chauffeur_Vehicule")?.trim();
  const poid_Taratone = fd.get("poid_taratone")?.trim();
  const poid_brut = fd.get("poid_brut")?.trim();
  const poid_net = fd.get("poid_net")?.trim();
  const note = fd.get("note")?.trim();

  if (!Fchauffeur_Vehicule || !poid_Taratone || !poid_brut || !poid_net) {
    Swal.fire({
      icon: "warning",
      text: "Tout les champs sont obligatoire!",
      title: "Attention",
    });
    return;
  }
  Swal.showLoading();

  $.ajax({
    url: "../includes/ajax/Fitor/Taratonage/addTaratonageFitour.php",
    method: "POST",
    dataType: "JSON",
    data: fd,
    processData: false, // <= required for FormData
    contentType: false, // <= required for FormData
    success: function (response) {
      Swal.close();
      Swal.fire({
        icon: response.status === "success" ? "success" : "error",
        title: response.status === "success" ? "Ajouté" : "Erreur",
        text: response.message || "",
      }).then(() => {
        if (response.status === "success") location.reload();
      });
    },
    error: function (xhr) {
      Swal.close();
      Swal.fire({
        icon: "error",
        title: "Erreur réseau",
        text: xhr.responseText || "Impossible d'envoyer la requête.",
      });
    },
  });
});

//FIN TARATONAGE

//Ajouter le versement du Chauffeur de Fitor
$(document).on("submit", "#addVersementChauffeur", function (e) {
  e.preventDefault();

  const form = this;
  const fd = new FormData(form);
  const Fchauffeur = fd.get("List_F_chauffeur")?.trim();
  const montant_Verser = fd.get("montant_verser")?.trim();
  const paiment_methode = fd.get("paiment_methode")?.trim();
  const note = fd.get("note")?.trim();

  if (!Fchauffeur || !montant_Verser || !paiment_methode) {
    Swal.fire({
      icon: "warning",
      text: "Tout les champs sont obligatoire!",
      title: "Attention",
    });
    return;
  }
  Swal.showLoading();

  $.ajax({
    url: "../includes/ajax/Fitor/Versement/addVersement.php",
    method: "POST",
    dataType: "JSON",
    data: fd,
    processData: false, 
    contentType: false, 
success: function (response) {
  Swal.close();

  const ok = response && (response.status === "success" || response.success === true);
  const id  = response?.id || response?.id_versement || "";
  const printUrl =
    response?.print_url ||
    (id ? `/gestionMeknassa/bon-Fitor/print_versement_fitour.php?id=${encodeURIComponent(id)}` : "");

  if (ok) {
    Swal.fire({
      icon: "success",
      title: "Versement enregistré",
      text: response.message || "L'opération a été effectuée avec succès.",
      confirmButtonText: "Imprimer le reçu",
      showCancelButton: true,
      cancelButtonText: "Fermer",
      confirmButtonColor: "green"
    }).then((r) => {
      if (r.isConfirmed) {
        if (printUrl) {
          window.open(printUrl, "_blank");
        } else {
          Swal.fire({
            icon: "info",
            title: "Reçu indisponible",
            text: "Identifiant de versement manquant pour l'impression."
          });
        }
      }
      form.reset();
      form.classList.remove("was-validated");

      const triggerListTab = document.querySelector('[data-bs-target="#tab-vercement"]');
      if (triggerListTab) new bootstrap.Tab(triggerListTab).show();

      if (typeof loadVersements === "function") loadVersements();
      else location.reload();
    });
  } else {
    Swal.fire({
      icon: "error",
      title: "Erreur",
      text: response?.message || "Une erreur est survenue."
    });
  }
},

    error: function (xhr) {
      Swal.close();
      Swal.fire({
        icon: "error",
        title: "Erreur réseau",
        text: xhr.responseText || "Impossible d'envoyer la requête.",
      });
    },
  });
});

// one-time bind, robust to duplicates
$(document)
  .off("click", ".btn-edit-taratonage")
  .on("click", ".btn-edit-taratonage", function (e) {
    e.preventDefault();
    const idT = this.dataset.id_taratonage || $(this).data("id_taratonage");
    if (!idT) return alert("id_taratonage manquant");

    // server check (editable? + fetch data)
    $.post(
      "../includes/ajax/Fitor/Taratonage/check_can_edit_taratonage.php",
      { id_taratonage: idT },
      function (res) {
        if (!res || res.ok !== true)
          return alert(res?.error || "Erreur serveur");

        // fill form + lock if not editable
        fillEditForm(res.data || {}, res.editable === false);
        $("#e_alert")
          .toggleClass("d-none", !!res.editable)
          .text(res.reason || "");
        $("#e_submit").prop("disabled", res.editable === false);

        // Now switch modals safely
        const viewEl = document.getElementById("viewPieceModal");
        const editEl = document.getElementById("editTaratonageModal");

        // safety cleanup in case a backdrop was left over
        function killOrphanBackdrops() {
          document.body.classList.remove("modal-open");
          document.body.style.removeProperty("padding-right");
          document
            .querySelectorAll(".modal-backdrop")
            .forEach((el) => el.remove());
        }

        function openEdit() {
          killOrphanBackdrops();
          const edit = bootstrap.Modal.getOrCreateInstance(
            editEl /*, {backdrop:'static', keyboard:false}*/
          );
          edit.show();
        }

        const view = viewEl ? bootstrap.Modal.getInstance(viewEl) : null;
        if (view && viewEl.classList.contains("show")) {
          // wait for real hide, then open edit — ensure we attach only once
          const onHidden = () => {
            viewEl.removeEventListener("hidden.bs.modal", onHidden);
            openEdit();
          };
          viewEl.addEventListener("hidden.bs.modal", onHidden, { once: true });
          view.hide();
        } else {
          openEdit();
        }
      },
      "json"
    );
  });

// helper used above (unchanged from previous messages)
function fillEditForm(data, readonly) {
  $("#e_id_taratonage").val(data?.id_taratonage ?? "");
  const brut = Number(data?.poid_brut ?? 0);
  const tar = Number(data?.poid_taratone ?? data?.poid_tarat ?? 0);
  const net = Math.max(0, brut - tar);
  $("#e_poid_brut").val(brut.toFixed(2));
  $("#e_poid_taratone").val(tar.toFixed(2));
  $("#e_poid_net").val(net.toFixed(2));
  $("#e_note").val(data?.note ?? "");
  ["#e_poid_brut", "#e_poid_taratone", "#e_note"].forEach((sel) => {
    $(sel).prop("readOnly", !!readonly).prop("disabled", !!readonly);
  });
}
// live recompute net
["e_poid_brut", "e_poid_taratone"].forEach((id) => {
  document.getElementById(id)?.addEventListener("input", () => {
    const brut = parseFloat(
      document.getElementById("e_poid_brut").value || "0"
    );
    const tar = parseFloat(
      document.getElementById("e_poid_taratone").value || "0"
    );
    document.getElementById("e_poid_net").value = Math.max(
      0,
      brut - tar
    ).toFixed(2);
  });
});

// Helper to fill the form and compute net live
function fillEditForm(data, readonly) {
  console.log("[edit] fill form with", data, { readonly });

  const $id = $("#e_id_taratonage").val(data?.id_taratonage ?? "");
  const brut = Number(data?.poid_brut ?? 0);
  const tar = Number(data?.poid_taratone ?? data?.poid_tarat ?? 0);
  const net = Math.max(0, brut - tar);

  $("#e_poid_brut").val(brut.toFixed(2));
  $("#e_poid_taratone").val(tar.toFixed(2));
  $("#e_poid_net").val(net.toFixed(2));
  $("#e_note").val(data?.note ?? "");

  // toggle readonly if needed
  ["#e_poid_brut", "#e_poid_taratone", "#e_note"].forEach((sel) => {
    $(sel).prop("readOnly", !!readonly).prop("disabled", !!readonly);
  });
}

// Live recompute net inside edit modal
["e_poid_brut", "e_poid_taratone"].forEach((id) => {
  document.getElementById(id)?.addEventListener("input", () => {
    const brut = parseFloat(
      document.getElementById("e_poid_brut").value || "0"
    );
    const tar = parseFloat(
      document.getElementById("e_poid_taratone").value || "0"
    );
    const net = Math.max(0, brut - tar);
    document.getElementById("e_poid_net").value = net.toFixed(2);
  });
});

// ===== Liste des Versements =====
document.addEventListener("DOMContentLoaded", () => {
  const tab = document.querySelector("#tab-vercement");
  if (!tab) return;

  const chauffeurSelect = tab.querySelector("#List_F_chauffeur_vercement");
  const tableBody = tab.querySelector("#versementTableBody");
  if (!chauffeurSelect || !tableBody) return;

  const fieldLabels = {
    id_versement: "ID Versement",
    reference_banque: "Référence banque",
    statut: "Statut",
    created_at: "Date de création",
    updated_at: "Date de mise à jour",
    image_Fchauffeur: "Image de Chauffeur",
    image_versement: "Preuve de Versement",
    image_Patron: "Image de Patron",
  };

  const imageKeys = new Set([
    "image_Fchauffeur",
    "image_versement",
    "image_Patron",
  ]);
  const BASE_URL = "../";

  const escapeHtml = (s) =>
    String(s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");

  const isImageUrl = (val) => {
    if (!val) return false;
    const s = String(val).trim();
    return (
      s.startsWith("data:image") ||
      /\.(png|jpe?g|gif|webp|svg)(\?.*)?$/i.test(s)
    );
  };

  const absolutize = (src) => {
    const s = String(src || "").trim();
    if (!s) return s;
    if (/^(data:image|https?:\/\/|\/)/i.test(s)) return s; // already absolute
    return BASE_URL + s;
  };

  const normalizeToList = (val) => {
    if (Array.isArray(val)) return val.filter(Boolean);
    const s = String(val || "").trim();
    if (!s) return [];
    try {
      const parsed = JSON.parse(s);
      if (Array.isArray(parsed)) return parsed.filter(Boolean);
    } catch (_) {}
    return s
      .split(/[;,]/)
      .map((x) => x.trim())
      .filter(Boolean);
  };

  const renderValue = (key, val) => {
    if (imageKeys.has(key)) {
      const list = normalizeToList(val).map(absolutize);
      if (!list.length) return `<em class="text-muted">—</em>`;
      return `
        <div class="d-images">
          ${list
            .map((src) => {
              const safe = escapeHtml(src);
              const isImg = isImageUrl(src);
              if (!isImg)
                return `<a href="${safe}" target="_blank" rel="noopener">Ouvrir</a>`;
              return `
                <a href="${safe}" target="_blank" rel="noopener" class="d-img-link">
                  <img src="${safe}" alt="${escapeHtml(
                fieldLabels[key] || key
              )}" loading="lazy" class="d-img-thumb" />
                </a>`;
            })
            .join("")}
        </div>`;
    }
    return escapeHtml(val);
  };

  // Toggle details (click + keyboard)
  tableBody.addEventListener("click", (e) => {
    const row = e.target.closest("tr.data-row");
    if (!row) return;
    const details = row.nextElementSibling;
    if (!details || !details.classList.contains("details-row")) return;
    const expanded = row.getAttribute("aria-expanded") === "true";
    row.setAttribute("aria-expanded", String(!expanded));
    details.classList.toggle("open", !expanded);
  });

  tableBody.addEventListener("keydown", (e) => {
    if (!(e.key === "Enter" || e.key === " ")) return;
    const row = e.target.closest("tr.data-row");
    if (!row) return;
    e.preventDefault();
    row.click();
  });

  const skeleton = () => `
    <tr>
      <td colspan="6" class="text-center text-muted py-3">Chargement...</td>
    </tr>
  `;

  function renderRows(data) {
    tableBody.innerHTML = "";
    if (!Array.isArray(data) || data.length === 0) {
      tableBody.innerHTML = `<tr><td colspan="6" class="text-center">Aucun versement trouvé</td></tr>`;
      return;
    }

    data.forEach((item, index) => {
      const tr = document.createElement("tr");
      tr.classList.add("align-middle", "data-row");
      tr.setAttribute("tabindex", "0");
      tr.setAttribute("role", "button");
      tr.setAttribute("aria-expanded", "false");
      tr.innerHTML = `
        <td class="text-muted">${index + 1}.</td>
        <td>${escapeHtml(
          (item.nom_patron || "") + " " + (item.prenom_patron || "")
        )}</td>
        <td>${escapeHtml(item.date_versement || "")}</td>
        <td>${escapeHtml(item.montant ?? "")}</td>
        <td>${escapeHtml(item.note || "")}</td>
        <td>${escapeHtml(item.mode_paiement || "")}</td>
          <td>
    <button class="btn btn-sm btn-outline-primary print-btn" data-id="${item.id_versement}">
      <i class="bi bi-printer"></i> 
    </button>
  </td>
      `;

      const detailsTr = document.createElement("tr");
      detailsTr.classList.add("details-row");
      const detailsTd = document.createElement("td");
      detailsTd.colSpan = 6;

      const shownKeys = new Set([
        "nom_patron",
        "prenom_patron",
        "date_versement",
        "montant",
        "note",
        "mode_paiement",
      ]);

      const extras = Object.entries(item).filter(
        ([k, v]) => !shownKeys.has(k) && v != null && String(v).trim() !== ""
      );

      const extrasHtml = extras.length
        ? `<div class="details-grid">
             ${extras
               .map(
                 ([k, v]) => `
                 <div class="d-item">
                   <div class="d-k">${escapeHtml(
                     fieldLabels[k] || k.replaceAll("_", " ")
                   )}</div>
                   <div class="d-v">${renderValue(k, v)}</div>
                 </div>`
               )
               .join("")}
           </div>`
        : `<em class="text-muted">Aucun détail supplémentaire</em>`;

      detailsTd.innerHTML = `
        <div class="details-wrap">
          <div class="details-title">Détails du versement</div>
          ${extrasHtml}
        </div>
      `;
      detailsTr.appendChild(detailsTd);

      tableBody.appendChild(tr);
      tableBody.appendChild(detailsTr);
    });
  }

  async function loadVersements(chauffeurId) {
    tableBody.innerHTML = skeleton();

    let url, body;
    if (!chauffeurId || chauffeurId === "null") {
      // ➜ ALL versements
      url = "../includes/ajax/Fitor/Versement/getListVersement.php";
      body = ""; // GET-like POST with no params
    } else {
      // ➜ Filter by chauffeur
      url = "../includes/ajax/Fitor/Versement/getListVersementByChauffeur.php";
      body = "id_Fchauffeur=" + encodeURIComponent(chauffeurId);
    }

    try {
      const res = await fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body,
      });
      const data = await res.json();
      renderRows(data);
    } catch (err) {
      console.error("Erreur fetch:", err);
      tableBody.innerHTML = `
        <tr><td colspan="6" class="text-center text-danger">Erreur de chargement</td></tr>`;
    }
  }

  // Initial load: ALL versements (before any selection)
  loadVersements(null);

  // Filter when selecting a chauffeur
  chauffeurSelect.addEventListener("change", () => {
    loadVersements(chauffeurSelect.value);
  });
});

document.addEventListener("click", (e) => {
  const printButton = e.target.closest(".print-btn");
  if (printButton) {
    e.stopPropagation(); 
    const id = printButton.dataset.id;
    if (!id) return;

    const url = `/gestionMeknassa/bon-Fitor/print_versement_fitour.php?id=${encodeURIComponent(id)}`;
    window.open(url, "_blank");
  }
});

// Add Transporteur

$("#addTransporteurOlive").on("submit", function (e) {
  e.preventDefault();

  const fd = new FormData(this);

  Swal.fire({
    title: "Ajout en cours...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: "../includes/ajax/Transporteur/addTransporteur.php",
    method: "POST",
    dataType: "json",
    data: fd,
    processData: false,
    contentType: false,
    success: function (response) {
      Swal.close();
      Swal.fire({
        icon: response.status === "success" ? "success" : "error",
        title: response.status === "success" ? "Ajouté" : "Erreur",
        text: response.message || "",
      }).then(() => {
        if (response.status === "success") location.reload();
      });
    },
    error: function (xhr) {
      Swal.close();
      console.error(xhr.responseText);
      Swal.fire({
        icon: "error",
        title: "Erreur réseau",
        text: xhr.responseText || "Impossible d'envoyer la requête.",
      });
    },
  });
});








//add Vehicule Transporteur
$(document).on("submit", "#addVehiculeTransport", function (e) {
  e.preventDefault();

  const form = this;
  const fd = new FormData(form);
  const Fchauffeur = fd.get("List_Transporteur")?.trim();
  const type_vehicule = fd.get("List_type_vehicule")?.trim();
  const nom_vehicule = fd.get("nom_vehicule_transp")?.trim();
  const matricule = fd.get("matricule_vehicule_transp")?.trim();
  const couleur_vehicule = fd.get("List_couleur_vehicule")?.trim();

  if (
    !Fchauffeur ||
    !type_vehicule ||
    !nom_vehicule ||
    !matricule ||
    !couleur_vehicule
  ) {
    Swal.fire({
      icon: "warning",
      text: "Tout les champs sont obligatoire!",
      title: "Attention",
    });
    return;
  }
  Swal.showLoading();

  $.ajax({
    url: "../includes/ajax/Transporteur/addVehiculeTransport.php",
    method: "POST",
    dataType: "JSON",
    data: fd,
    processData: false, // <= required for FormData
    contentType: false, // <= required for FormData
    success: function (response) {
      Swal.close();
      Swal.fire({
        icon: response.status === "success" ? "success" : "error",
        title: response.status === "success" ? "Ajouté" : "Erreur",
        text: response.message || "",
      }).then(() => {
        if (response.status === "success") location.reload();
      });
    },
    error: function (xhr) {
      Swal.close();
      Swal.fire({
        icon: "error",
        title: "Erreur réseau",
        text: xhr.responseText || "Impossible d'envoyer la requête.",
      });
    },
  });
});


// ADD TARATONAGE OLIVE

$(document).on("submit", "#addTaratonageTrans", function (e) {
  e.preventDefault();

  const form = this;
  const fd = new FormData(form);
  const chauffeur_Vehicule = fd.get("List_transporteur")?.trim();
  const Vehicule_Olive = fd.get("Transporteur_Vehicule")?.trim();
  const poid_Taratone = fd.get("poid_taratone")?.trim();
  const poid_brut = fd.get("poid_brut")?.trim();
  const poid_net = fd.get("poid_net")?.trim();
  const note = fd.get("note")?.trim();

  if (!chauffeur_Vehicule || !poid_Taratone || !poid_brut || !poid_net || !Vehicule_Olive) {
    Swal.fire({
      icon: "warning",
      text: "Tout les champs sont obligatoire!",
      title: "Attention",
    });
    return;
  }
  Swal.showLoading();

  $.ajax({
    url: "../includes/ajax/Transporteur/addTaratonageTrans.php",
    method: "POST",
    dataType: "JSON",
    data: fd,
    processData: false, // <= required for FormData
    contentType: false, // <= required for FormData
    success: function (response) {
      Swal.close();
      Swal.fire({
        icon: response.status === "success" ? "success" : "error",
        title: response.status === "success" ? "Ajouté" : "Erreur",
        text: response.message || "",
      }).then(() => {
        if (response.status === "success") location.reload();
      });
    },
    error: function (xhr) {
      Swal.close();
      Swal.fire({
        icon: "error",
        title: "Erreur réseau",
        text: xhr.responseText || "Impossible d'envoyer la requête.",
      });
    },
  });
});


// === Soumission du formulaire d'ajout de client olive ===
  $("#addClientOlive").on("submit", function (e) {
    e.preventDefault();

    const fd = new FormData(this);

    Swal.fire({
      title: "Ajout en cours...",
      text: "Veuillez patienter pendant l'enregistrement du client.",
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });

    $.ajax({
      url: "../includes/ajax/ClientOlive/addClientOlive.php",
      method: "POST",
      dataType: "json",
      data: fd,
      processData: false,
      contentType: false,

      success: function (response) {
        Swal.close();

        Swal.fire({
          icon: response.status === "success" ? "success" : "error",
          title: response.status === "success" ? "Ajouté avec succès" : "Erreur",
          text: response.message || "",
          confirmButtonText: "OK"
        }).then(() => {
          if (response.status === "success") {
            // Rafraîchir la page pour recharger la liste
            location.reload();
          }
        });
      },

      error: function (xhr) {
        Swal.close();
        console.error("Erreur AJAX:", xhr.responseText);

        Swal.fire({
          icon: "error",
          title: "Erreur réseau",
          text: xhr.responseText || "Impossible d'envoyer la requête au serveur.",
        });
      }
    });
  });


  // ADD VERSEMENT CLIENT OLIVE
$("#addVersementClientOlive").on("submit", function (e) {
  e.preventDefault();

  const fd = new FormData(this);

  Swal.fire({
    title: "Ajout du versement en cours...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: "../includes/ajax/ClientOlive/addVersementClientOlive.php",
    method: "POST",
    data: fd,
    dataType: "json",
    processData: false,
    contentType: false,

    success: function (response) {
      Swal.close();

      if (response.status === "success") {
        const id = response.id || response.id_versement || response.last_id;

        Swal.fire({
          icon: "success",
          title: "Versement ajouté avec succès",
          text: response.message || "Le versement du client a bien été enregistré.",
          showCancelButton: true,
          confirmButtonText: "Imprimer le reçu",
          cancelButtonText: "Fermer",
          confirmButtonColor: "green",
          cancelButtonColor: "#6c757d",
        }).then((r) => {
          if (r.isConfirmed && id) {
          const printUrl = `/gestionMeknassa/bon-client-crediteur/print_versement_clientolive.php?id_versement=${encodeURIComponent(id)}`;
window.open(printUrl, "_blank");

          }
        });


        $("#addVersementClientOlive")[0].reset();
        $(".js-choices").val("").trigger("change");

      } else {
        Swal.fire({
          icon: "error",
          title: "Erreur lors de l’ajout",
          text: response.message || "Une erreur est survenue lors de l’enregistrement.",
        });
      }
    },

    error: function (xhr, status, error) {
      Swal.close();
      console.error("Erreur AJAX :", error, xhr.responseText);
      Swal.fire({
        icon: "error",
        title: "Erreur réseau",
        text: xhr.responseText || "Impossible d'envoyer la requête.",
      });
    },
  });
});


