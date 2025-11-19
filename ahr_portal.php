<?php
require 'functions.php';
require 'layout.php';
require 'db.php';
require 'AHR_class.php';

page_header('Area economica (ERP AHR)','ahr');
?>
<section class="toolbar">
  <p class="muted">Accesso in sola lettura a ERP AHR: articoli, clienti/fornitori, costi. Le nuove tabelle di gestione costi/BOM verranno create in CLDC.</p>
</section>

<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:12px;">
  <section style="border:1px solid var(--border);border-radius:8px;padding:12px;background:var(--bg);">
    <h4>Dati anagrafici</h4>
    <div class="row" style="flex-direction:column;align-items:flex-start;gap:8px;">
      <a class="btn" href="ahr_clients.php">Clienti e dettagli</a>
      <a class="btn" href="ahr_suppliers.php">Fornitori</a>
      <a class="btn" href="ahr_articles.php">Articoli (catalogo, categorie)</a>
    </div>
  </section>
  <section style="border:1px solid var(--border);border-radius:8px;padding:12px;background:var(--bg);">
    <h4>Costi e ordini</h4>
    <div class="row" style="flex-direction:column;align-items:flex-start;gap:8px;">
      <a class="btn" href="ahr_costs.php">Storico costi articoli (ORDFO)</a>
      <a class="btn" href="ahr_orders.php">Ordini clienti (ORDCL)</a>
      <a class="btn" href="ahr_ddt.php">DDT vendita</a>
    </div>
  </section>
  <section style="border:1px solid var(--border);border-radius:8px;padding:12px;background:var(--bg);">
    <h4>Plan</h4>
    <ul>
      <li>Crea tabelle CLDC per BOM/costi (write su CLDC)</li>
      <li>API di sync read-only da AHR verso CLDC</li>
      <li>UI di gestione costi/strategia</li>
    </ul>
  </section>
</div>

<?php page_footer(); ?>
