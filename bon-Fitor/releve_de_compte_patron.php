<?php
header('Content-Type: text/html; charset=utf-8');

// Paramètres passés à la page (optionnels)
$id_patron = isset($_GET['id_patron']) ? (int)$_GET['id_patron'] : 0;


// Entête "banque"
$bank = [
  'name'  => 'Meknassa Olive',
  'phone' => '+212 7 01 08 52 61',
  'bic'   => 'Meknassa el gharbia',
  'logo'  => '../assets/img/logo_meknassa.png',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Relevé de compte — Patron <?= htmlspecialchars($id_patron) ?></title>
  <style>
    :root { --ink:#111; --muted:#6b7280; --line:#E5E7EB; --bg:#fff; }
    * { box-sizing:border-box; }
    body { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial; color:var(--ink); margin:0; background:var(--bg); }
    .sheet { width:100%; max-width:1000px; margin:24px auto; padding:24px; border:1px solid var(--line); background:#fff; }
    header {
      display:grid; grid-template-columns: 1fr auto 1fr; align-items:center; gap:24px;
      border-bottom:2px solid var(--ink); padding-bottom:12px;
    }
    .brand h1 { margin:0; font-size:22px; letter-spacing:.3px; }
    .brand small { color:var(--muted); }
    .logo-wrap { display:flex; justify-content:center; }
    .logo-wrap img { max-height:120px; width:auto; display:block; }
    .meta { text-align:right; font-size:14px; }
    .meta .big { font-weight:600; }
    @media (max-width:640px){
      header { grid-template-columns: 1fr; text-align:center; }
      .meta { text-align:center; }
      .brand small { display:block; margin-top:4px; }
    }
    .grid { display:grid; grid-template-columns: 1.2fr .8fr; gap:18px; margin-top:18px; }
    @media (max-width:640px){ .grid { grid-template-columns:1fr; } }
    .box { border:1px solid var(--line); padding:12px; }
    .box h3 { margin:0 0 6px; font-size:14px; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); }
    .addr { white-space:pre-line; }
    .controls { display:flex; gap:8px; align-items:end; flex-wrap:wrap; margin-top:12px; }
    .controls label { font-size:12px; color:var(--muted); display:block; margin-bottom:4px; }
    .controls input { padding:6px 8px; border:1px solid var(--line); border-radius:6px; }
    .controls button { padding:8px 12px; border:1px solid var(--ink); background:#111; color:#fff; border-radius:6px; cursor:pointer; }
    .controls button:disabled { opacity:.6; cursor:not-allowed; }
    table { width:100%; border-collapse:collapse; margin-top:0; }
    th, td { padding:4px 5px; border-bottom:1px solid var(--line); font-size:14px; }
    th { text-align:left; background:#F3F4F6; }
    .num { text-align:right; white-space:nowrap; }
    tfoot td { font-weight:700; }
    .muted { color:var(--muted); }
    .small { font-size:12px; }
    .right { text-align:right; }
    .error { color:#b91c1c; background:#FEF2F2; border:1px solid #FCA5A5; padding:10px; margin-top:12px; border-radius:6px; }
    .loading { font-size:14px; color:#374151; margin-top:12px; }
  </style>
</head>
<body>
  <div class="sheet">
    <header>
      <div class="brand">
        <h1><?= htmlspecialchars($bank['name']) ?></h1>
        <small>
          Adress: <?= htmlspecialchars($bank['bic']) ?><BR/>
          Tél: <?= htmlspecialchars($bank['phone']) ?>
        </small>
      </div>
      <div class="logo-wrap">
        <?php if (!empty($bank['logo'])): ?>
          <img src="<?= htmlspecialchars($bank['logo']) ?>" alt="Logo" />
        <?php endif; ?>
      </div>
      <div class="meta">
        <div class="big">RELEVÉ DE COMPTE</div>
        <div>en Dirham</div>
        <!-- <div id="period" class="small">—</div> -->
      </div>
    </header>

    <section class="grid">
      <div class="box">
        <h3>Titulaire du compte</h3>
        <div id="owner" class="addr">—</div>
      </div>

      <div>
        <div>
       
          <div class="controls">
            <div>
              <input style="display:none;" type="number" id="id_patron" value="<?= (int)$id_patron ?>" min="1" step="1" />
            </div>
           
          </div>
         
        </div>
      </div>
    </section>

    <table>
      <thead>
        <tr>
          <th style="width:10%">Date</th>
          <th style="width:36%">Nature de l'opération</th>
          <th class="right" style="width:9%">Qté (Kg)</th>
          <th class="right" style="width:12%">Prix init. (Dh)</th>
          <th class="right" style="width:12%">Crédit (Dh)</th>
          <th class="right" style="width:12%">Débit (Dh)</th>
      
          <th class="right" style="width:9%">Solde</th>
        </tr>
      </thead>
      <tbody id="tx-body">
        <tr><td colspan="7" class="muted">Aucune donnée.</td></tr>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="4" class="right">Totaux période</td>
          
          <td class="num" id="tot-credit">0,00</td>
          <td class="num" id="tot-debit">0,00</td>
          <td class="num" id="tot-solde">0,00</td>
        </tr>
      </tfoot>
    </table>
     <div class="signatures" style="display:flex; justify-content:space-between; margin-top:20px; padding-top:20px; border-top:2px solid #000;">
      <div class="signature-block" style="text-align:center; flex:1;">
        <div style="font-weight:600; margin-bottom:60px;">Signature Client</div>
        <div style="border-top:1px solid #555; width:70%; margin:0 auto;"></div>
      </div>
      <div class="signature-block" style="text-align:center; flex:1;">
        <div style="font-weight:600; margin-bottom:60px;">Signature Administrative</div>
        <div style="border-top:1px solid #555; width:70%; margin:0 auto;"></div>
      </div>
    </div>
  </div>

  <script>
    const fmtDH = new Intl.NumberFormat('fr-FR', {minimumFractionDigits:2, maximumFractionDigits:2});
    const dmy = iso => {
      if (!iso) return '—';
      const d = new Date(iso);
      if (isNaN(d.getTime())) return iso;
      const dd = String(d.getDate()).padStart(2,'0');
      const mm = String(d.getMonth()+1).padStart(2,'0');
      const yyyy = d.getFullYear();
      return `${dd}/${mm}/${yyyy}`;
    };

    const $ = sel => document.querySelector(sel);
    const $body = $('#tx-body');
    const $totDebit = $('#tot-debit');
    const $totCredit = $('#tot-credit');
    const $totSolde = $('#tot-solde');
    const $owner = $('#owner');
   // const $period = $('#period');
    
    

    async function loadData() {
     
     
    
      $body.innerHTML = `<tr><td colspan="7" class="muted">Chargement…</td></tr>`;

      const id_patron = Number($('#id_patron').value || 0);
      const qs = new URLSearchParams({ id_patron });
      try {
        const res = await fetch('../includes/ajax/Fitor/Patron/getReleveBank.php?' + qs.toString(), {cache:'no-store'});
        if (!res.ok) {
          const txt = await res.text();
          throw new Error(txt || `HTTP ${res.status}`);
        }
        const data = await res.json();
        if (data.error) throw new Error(data.error);

        // Header: owner + period
        const o = data.owner || {};
        const adr0 = (o.address && o.address[0]) ? o.address[0] : '';
        $owner.textContent = `${(o.title||'')+' '+(o.name||'—')}\nTéléphone : ${(o.telephone||'')}\n${adr0}`;
        //$period.textContent = `du ${dmy(data.period?.from)} au ${dmy(data.period?.to)}`;

        // Table
        const opening = Number(data.opening_balance || 0);
        const tx = Array.isArray(data.transactions) ? data.transactions : [];

        let html = `
          <tr>
            <td class="muted">—</td>
            <td class="muted">Solde précédent</td>
            <td class="num muted">—</td>
            <td class="num muted">—</td>
            <td class="num muted">—</td>
            <td class="num muted">—</td>
            <td class="num"><strong>${fmtDH.format(opening)}</strong></td>
          </tr>
        `;

        let running = opening;
        for (const t of tx) {
      
          
          const qte = Number(t.quantite || 0);
          const pin = Number(t.prix_init || 0);
          const deb = Number(t.debit || 0);
          const cre = Number(t.credit || 0);
          running += (cre - deb);
          html += `
            <tr>
              <td>${dmy(t.date)}</td>
              <td>${escapeHtml(t.libelle || '')}&nbsp;${escapeHtml(t.matricule || '')}</td>
              <td class="num">${qte ? fmtDH.format(qte) : '—'}</td>
              <td class="num">${pin ? fmtDH.format(pin) : '—'}</td>
               <td class="num">${cre ? fmtDH.format(cre) : '—'}</td>
              <td class="num">${deb ? fmtDH.format(deb) : '—'}</td>
             
              <td class="num">${fmtDH.format(running)}</td>
            </tr>
          `;
        }

        $body.innerHTML = html;

        // Totaux
        const debitTot = Number(data.totals?.debit || 0);
        const creditTot = Number(data.totals?.credit || 0);
        const closing = Number(data.totals?.closing_balance || (running||0));

        $totDebit.textContent = fmtDH.format(debitTot);
        $totCredit.textContent = fmtDH.format(creditTot);
        $totSolde.textContent = fmtDH.format(closing);

      } catch (e) {
        console.error(e);
        // $err.textContent = e.message || 'Erreur de chargement.';
        // $err.style.display = 'block';
        $body.innerHTML = `<tr><td colspan="7" class="muted">Erreur.</td></tr>`;
      } finally {
       // $msg.style.display = 'none';
      }
    }

    function escapeHtml(s){
      return String(s)
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'","&#039;");
    }

    

    // Auto-chargement si id_patron est fourni dans l’URL
    window.addEventListener('DOMContentLoaded', () => {
      const initialId = Number(<?= (int)$id_patron ?>);
      if (initialId > 0) loadData();
    });
  </script>
</body>
</html>
