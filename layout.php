<?php
function page_header($title, $section='home'){
  $section = strtolower($section);
  echo '<!doctype html><html lang="it"><head><meta charset="utf-8">';
  echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
  echo '<title>'.h($title).'</title>';
  echo '<style>
    :root{
      color-scheme: light dark;
      --bg:#eef1f5; --fg:#222631; --muted:#5b6372; --border:#c7ced8; --th-bg:#e0e4eb;
      --btn-bg:#f9f9fb; --btn-border:#8f98a6; --btn-primary:#0b63ce; --btn-danger:#b43434;
      --pill-on-bg:#dfece5; --pill-on-border:#4caf50; --pill-on-text:#1a3e2b;
      --pill-off-bg:#f7dfdf; --pill-off-border:#d9534f; --pill-off-text:#5c1b1b;
      --tag-bg:#303743; --tag-border:#4c5667; --tag-text:#f2f4f7;
    }
    @media (prefers-color-scheme: dark){
      :root{
        --bg:#161a1d; --fg:#e5e7eb; --muted:#b0b7c3; --border:#3a414d; --th-bg:#232832;
        --btn-bg:#242a32; --btn-border:#7a8797; --btn-primary:#89b4ff; --btn-danger:#f28b82;
        --pill-on-bg:#23432e; --pill-on-border:#62c571; --pill-on-text:#ffffff;
        --pill-off-bg:#3a2424; --pill-off-border:#f28b82; --pill-off-text:#ffffff;
        --tag-bg:#2a3138; --tag-border:#5b6675; --tag-text:#f1f3f5;
      }
      body{ background:var(--bg); }
    }
    body{font-family:Arial,Helvetica,sans-serif;font-size:14px;color:var(--fg);background:var(--bg);margin:24px}
    header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
    .btn{padding:6px 12px;border:1px solid var(--btn-border);border-radius:6px;background:var(--btn-bg);color:var(--fg);cursor:pointer}
    .btn.primary{border-color:var(--btn-primary); color:var(--btn-primary)}
    .btn.danger{border-color:var(--btn-danger); color:var(--btn-danger)}
    .grid{display:grid;gap:12px}
    table{border-collapse:collapse;width:100%; background:var(--bg)}
    th,td{border:1px solid var(--border);padding:6px 8px;vertical-align:top}
    th{background:var(--th-bg)}
    .tag{display:inline-block;padding:2px 6px;background:var(--tag-bg);border:1px solid var(--tag-border);border-radius:6px;font-size:12px;color:var(--tag-text)}
    .pill{padding:4px 10px;border-radius:999px;font-size:12px;border:1px solid #c9c9c9ff;color:var(--fg)}
    .pill.on{background:var(--pill-on-bg);border-color:var(--pill-on-border);color:var(--pill-on-text)}
    .pill.off{background:var(--pill-off-bg);border-color:var(--pill-off-border);color:var(--pill-off-text)}
    .badge{font-size:11px;color:#b36;padding:0 4px}
    .toolbar{display:flex;gap:8px;align-items:center;margin:12px 0}
    .muted{color:var(--muted)}
    .row{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
    input[type=text],select{padding:6px 8px;border:1px solid #bbb;border-radius:6px;background:var(--bg);color:var(--fg)}
    .sticky{position:sticky;top:0;background:var(--bg);padding:8px 0}
  </style></head><body>';
  echo '<header><h2>'.h($title).'</h2><nav>';
  // Nav dinamica per separare aree CLDC vs AHR
  echo '<a class="btn" href="index.php">Home</a>';

  if ($section === 'cldc') {
    // area tecnica: link operativi CLDC + short link all'altra area
    echo '<a class="btn" href="matrix.php">Relazioni U/A</a>';
    echo '<a class="btn" href="models.php">Modelli</a>';
    echo '<a class="btn" href="accessories.php">Accessori</a>';
    echo '<a class="btn" href="series.php">Serie</a>';
    echo '<a class="btn" href="layouts.php">Layout</a>';
    echo '<a class="btn" href="docs_audit.php">Documenti</a>';
    echo '<a class="btn" href="ahr_portal.php">Area AHR</a>';
  } elseif ($section === 'ahr') {
    // area economica: bottone area AHR e short link a CLDC
    echo '<a class="btn" href="ahr_clients.php">Clienti</a>';
    echo '<a class="btn" href="ahr_suppliers.php">Fornitori</a>';
    echo '<a class="btn" href="ahr_articles.php">Articoli</a>';
    echo '<a class="btn" href="ahr_costs.php">Costi</a>';
    echo '<a class="btn" href="ahr_orders.php">Ordini</a>';
    echo '<a class="btn" href="ahr_ddt.php">DDT</a>';
    echo '<a class="btn" href="cldc_portal.php">Area CLDC</a>';
  } else {
    // home: solo ingressi principali
    echo '<a class="btn" href="cldc_portal.php">Area CLDC</a>';
    echo '<a class="btn" href="ahr_portal.php">Area AHR</a>';
  }

  echo '</nav></header>';
}
function page_footer(){ echo '</body></html>'; }
