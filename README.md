# WebAvensys Portale

Applicazione PHP (SQL Server) per la gestione tecnica (CLDC) e líintegrazione ERP (AHR).

## Requisiti
- PHP 7.2+ con estensione SQLSRV
- SQL Server
- Web server (Apache/IIS) con document root puntato a `portale/`

## Configurazione
1. Copia `.env.example` in `.env` (o crea `.env`) e imposta:
   - DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, DB_CHARSET
   - DOCS_DIR, DOCS_ARCHIVE_DIR, DOCS_INBOX_DIR (percorsi assoluti o relativi alla cartella del progetto)
   - DOCS_URL, DOCS_ARCHIVE_URL, DOCS_INBOX_URL (URL pubblici corrispondenti ai percorsi sopra)
   - FREECURRENCYAPI_KEY (per il cambio USD/EUR)
2. Assicurati che le cartelle di documento esistano e siano scrivibili.

## Struttura principale
- `index.php`              : Home con accesso alle aree
- `cldc_portal.php`        : Area tecnica CLDC
- `ahr_portal.php`         : Area economica AHR (read-only da ERP)
- `docs_audit.php`         : Audit e gestione documenti accessori
- `matrix.php`             : Relazioni Unit¿/Accessori
- `models.php`, `series.php`, `layouts.php` : Tabelle di anagrafiche CLDC
- `api_*`                  : Endpoint applicativi (upload documenti, scansioni, bulk, cambio USD/EUR, ecc.)
- `AHR_class.php`, `CLDC_class.php` : Layer di accesso dati
- `functions.php`, `db.php`: Helper generali e bootstrap DB/env

## Endpoint utile (nuovo)
- `api_accessories_for_model.php?model_id=ID&lang=DA`
  - Restituisce JSON con lista accessori abilitati per un modello e link al documento nella lingua richiesta o, se assente, in EN (campo `doc_lang`).

## Note sui documenti
- Convenzione file: `CODE__LANG__vYYYYMMDD_HHMMSS.pdf`
- Struttura cartelle consigliata: `docs/<CODE>/<LANG>/`
- Le versioni precedenti vengono archiviate in `DOCS_ARCHIVE_DIR`.

## Cambio USD/EUR
- `api_USDEUR.php` usa freecurrencyapi e cache locale (24h) in `usdeur_cache.json`.

## Build/Deploy
- Nessuna build: PHP puro. Carica i file in `portale/` e configura `.env`.
- Verifica permessi di scrittura per upload/scan documenti.

## Licenza
Progetto interno WebAvensys.
