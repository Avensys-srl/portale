<?php
require 'db.php';
require 'functions.php';
require 'layout.php';

page_header('Console prodotto','home');
?>
<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(320px,1fr));">
  <section style="border:1px solid var(--border);border-radius:8px;padding:12px;background:var(--bg);">
    <h3>Area tecnica (CLDC)</h3>
    <p class="muted">Gestione accessori, matrice, modelli e documenti.</p>
    <div class="row" style="gap:8px;flex-wrap:wrap;">
      <a class="btn" href="cldc_portal.php">Entra nell&#39;area CLDC</a>
    </div>
  </section>

  <section style="border:1px solid var(--border);border-radius:8px;padding:12px;background:var(--bg);">
    <h3>Area economica / strategica (AHR)</h3>
    <p class="muted">Interfaccia ERP (solo lettura): articoli, clienti, fornitori, costi, BOM.</p>
    <div class="row" style="gap:8px;flex-wrap:wrap;">
      <a class="btn" href="ahr_portal.php">Entra nell&#39;area AHR</a>
    </div>
  </section>
</div>

<?php page_footer(); ?>
