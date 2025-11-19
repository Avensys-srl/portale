<?php
require 'db.php';
require 'functions.php';
require 'layout.php';

page_header('Area tecnica (CLDC)','cldc');
?>
<section class="toolbar">
  <p class="muted">Gestione tecnica prodotto: accessori, Relazioni U/A, modelli, serie/layout e documenti.</p>
</section>

<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:12px;">
  <section style="border:1px solid var(--border);border-radius:8px;padding:12px;background:var(--bg);">
    <h4>Prodotto</h4>
    <div class="row" style="flex-direction:column;align-items:flex-start;gap:8px;">
      <a class="btn" href="matrix.php">Relazioni U/A</a>
      <a class="btn" href="models.php">Modelli</a>
      <a class="btn" href="accessories.php">Accessori</a>
      <a class="btn" href="series.php">Serie</a>
      <a class="btn" href="layouts.php">Layout</a>
    </div>
  </section>
  <section style="border:1px solid var(--border);border-radius:8px;padding:12px;background:var(--bg);">
    <h4>Documenti</h4>
    <div class="row" style="flex-direction:column;align-items:flex-start;gap:8px;">
      <a class="btn" href="docs_audit.php">Audit documenti</a>
    </div>
  </section>
</div>

<?php page_footer(); ?>
