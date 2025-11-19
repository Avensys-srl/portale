<?php
include_once "functions.php";

// Lettura .env locale senza aprire altre connessioni
if (!function_exists('env')) {
  function env($key, $default = null) {
    static $cache = null;
    if ($cache === null) {
      $cache = [];
      $path = __DIR__ . '/.env';
      if (is_file($path)) {
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
          if (strpos(ltrim($line), '#') === 0) continue;
          $parts = explode('=', $line, 2);
          if (count($parts) === 2) {
            $cache[$parts[0]] = $parts[1];
          }
        }
      }
    }
    return array_key_exists($key, $cache) ? $cache[$key] : $default;
  }
}

// Accesso read-only al gestionale AHR (ERP)
class AHR
{
    private $conn;

    public function __construct($conn = null)
    {
        if ($conn) {
            $this->conn = $conn;
        } else {
            $this->conn = $this->connect();
        }
    }

    private function connect()
    {
        $host = env('AHR_HOST', 'SERVER01\\sqlserver');
        $port = env('AHR_PORT', '1433');
        $db   = env('AHR_DB', 'AHR');
        $user = env('AHR_USER', 'sa');
        $pass = env('AHR_PASS', 'P4ssword');
        $charset = env('AHR_CHARSET', 'UTF-8');

        $serverName = $host . ($port ? ", $port" : '');
        $connectionInfo = [
            "Database" => $db,
            "UID" => $user,
            "PWD" => $pass,
            "CharacterSet" => $charset,
        ];

        $c = sqlsrv_connect($serverName, $connectionInfo);
        if (!$c) {
            throw new RuntimeException('Connessione AHR fallita: ' . print_r(sqlsrv_errors(), true));
        }
        return $c;
    }

    private function fetchResults($sqlquery, array $params = [])
    {
        $stmt = sqlsrv_query($this->conn, $sqlquery, $params, ["Scrollable" => SQLSRV_CURSOR_KEYSET]);
        if (!$stmt) {
            throw new RuntimeException(print_r(sqlsrv_errors(), true));
        }
        $data = [];
        while ($record = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $data[] = array_map(function($v){ return is_string($v) ? trim($v) : $v; }, $record);
        }
        sqlsrv_free_stmt($stmt);
        return $data;
    }

    // --- Articoli (solo lettura)
    private function fetchArticles(array $where = [], array $params = [])
    {
        $sql = "SELECT [ARCODART], [ARDESART], [ARDESSUP], [ARUNMIS1], [ARCODFAM]
                  FROM [dbo].[AVSYSART_ICOL]
                 WHERE LEN([ARCODART]) > 5";
        if ($where) {
            $sql .= " AND " . implode(' AND ', $where);
        }
        $results = $this->fetchResults($sql, $params);
        $articles = [];
        foreach ($results as $r) {
            $articles[] = [
                'codice' => $r['ARCODART'],
                'descr'  => $r['ARDESART'],
                'descr2' => $r['ARDESSUP'],
                'um'     => $r['ARUNMIS1'],
                'fam'    => $r['ARCODFAM'],
            ];
        }
        return $articles;
    }

    public function articoli($codice = null, $famiglia = null)
    {
        $where = [];
        $params = [];
        if ($codice !== null) {
            $where[] = "[ARCODART] LIKE ?";
            $params[] = $codice;
        }
        if ($famiglia !== null) {
            $where[] = "[ARCODFAM] LIKE ?";
            $params[] = $famiglia;
        }
        return $this->fetchArticles($where, $params);
    }

    public function totart()
    {
        return count($this->articoli());
    }

    public function articoli_vendita()
    {
        return array_merge($this->articoli("F%"), $this->articoli("E%"));
    }

    public function spare_parts()
    {
        return $this->articoli("SPR%");
    }

    public function lavorazioni()
    {
        return $this->articoli("SMCLAV%");
    }

    public function metallo()
    {
        return array_merge($this->articoli("SMCSMK%"), $this->articoli("SMCFM%"));
    }

    public function elettroniche()
    {
        return $this->articoli("AELEBD%");
    }

    public function distinte()
    {
        return $this->articoli("SMCASM%");
    }

    // --- Anagrafiche clienti/fornitori (read-only)
    public function clienti($codeLike = null, $descrLike = null, $limit = 500)
    {
        $limit = max(1, min((int)$limit, 2000));
        $conds = ["antipcon = 'C'"];
        $params = [];
        if ($codeLike !== null && $codeLike !== '') { $conds[] = "ancodice LIKE ?"; $params[] = $codeLike; }
        if ($descrLike !== null && $descrLike !== '') { $conds[] = "andescri LIKE ?"; $params[] = $descrLike; }
        $sql = "SELECT TOP $limit ancodice, andescri FROM AVSYSCONTI WHERE ".implode(' AND ', $conds)." ORDER BY ancodice";
        $rows = $this->fetchResults($sql, $params);
        return array_map(function($r){
            return ['codice'=>$r['ancodice'], 'descr'=>$r['andescri']];
        }, $rows);
    }

    public function fornitori($codeLike = null, $descrLike = null, $limit = 500)
    {
        $limit = max(1, min((int)$limit, 2000));
        $conds = ["antipcon = 'F'"];
        $params = [];
        if ($codeLike !== null && $codeLike !== '') { $conds[] = "ancodice LIKE ?"; $params[] = $codeLike; }
        if ($descrLike !== null && $descrLike !== '') { $conds[] = "andescri LIKE ?"; $params[] = $descrLike; }
        $sql = "SELECT TOP $limit ancodice, andescri FROM AVSYSCONTI WHERE ".implode(' AND ', $conds)." ORDER BY ancodice";
        $rows = $this->fetchResults($sql, $params);
        return array_map(function($r){
            return ['codice'=>$r['ancodice'], 'descr'=>$r['andescri']];
        }, $rows);
    }

    public function cliente_dettaglio($codice)
    {
        $rows = $this->fetchResults(
            "SELECT ancodice, andescri, anindiri, an___cap, anlocali, anprovin, annazion, ANINDWEB, ANNUMCEL
               FROM AVSYSCONTI
              WHERE antipcon = 'C' AND ancodice = ?",
            [$codice]
        );
        return $rows ? $rows[0] : null;
    }

    // Storico costi di acquisto (ordini fornitore)
    public function costo_articolo_storico($codice)
    {
        $sql = "SELECT mvcodice, mvdesart, mvunimis, mvqtamov, mvprezzo, mvcodcon, mvcodval, mvcaoval, mvdatdoc, andescri, mvflevas, ANINDWEB, ANNUMCEL
                  FROM AVSYSDOC_DETT
                  INNER JOIN AVSYSDOC_MAST ON AVSYSDOC_DETT.mvserial = AVSYSDOC_MAST.mvserial
                  LEFT JOIN AVSYSCONTI ON AVSYSCONTI.ANTIPCON = AVSYSDOC_MAST.MVTIPCON AND AVSYSCONTI.ANCODICE = AVSYSDOC_MAST.MVCODCON
                 WHERE mvcodice = ? AND mvtipdoc='ORDFO' AND LEN(mvcodice) > 5
                 ORDER BY mvdatdoc DESC";
        $rows = $this->fetchResults($sql, [$codice]);
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'codice'     => $r['mvcodice'],
                'descr'      => $r['mvdesart'],
                'um'         => $r['mvunimis'],
                'quantita'   => $r['mvqtamov'],
                'prezzo'     => $r['mvprezzo'],
                'codice_f'   => $r['mvcodcon'],
                'valuta'     => $r['mvcodval'],
                'cambio'     => $r['mvcaoval'],
                'data'       => $r['mvdatdoc'],
                'fornitore'  => $r['andescri'],
                'evaso'      => $r['mvflevas'],
                'mail'       => $r['ANINDWEB'],
                'telefono'   => $r['ANNUMCEL'],
            ];
        }
        return $out;
    }

    public function costo_articolo($codice)
    {
        $storico = $this->costo_articolo_storico($codice);
        return $storico ? $storico[0] : null;
    }

    // Ordini clienti (ODE) in un intervallo, filtro fornitore/codice
    public function ode($cliente = null, $filtro_codice = null, $dal = null, $al = null)
    {
        $dal = $dal ?? date('Y-m-d', strtotime('-6 months'));
        $al  = $al  ?? date('Y-m-d');
        $conds = ["mvtipdoc='ORDCL'", "mvflevas NOT LIKE 'S'", "mvdateva >= ?", "mvdateva <= ?", "LEN(mvcodice) > 10"];
        $params = [$dal, $al];
        if ($cliente !== null) { $conds[] = "mvcodcon = ?"; $params[] = $cliente; }
        if ($filtro_codice !== null) { $conds[] = "mvcodice LIKE ?"; $params[] = $filtro_codice; }

        $sql = "SELECT mvcodice AS codice, mvdesart AS descrizione, mvunimis AS um, mvqtamov AS quantita,
                       mvprezzo AS prezzo, mvcodcon AS cliente, mvcodval AS valuta, mvcaoval AS cambio,
                       mvdatdoc AS data_doc, mvdateva AS data_evasa, andescri AS descr_cliente
                  FROM AVSYSDOC_DETT
                  INNER JOIN AVSYSDOC_MAST ON AVSYSDOC_DETT.mvserial = AVSYSDOC_MAST.mvserial
                  LEFT JOIN AVSYSCONTI ON AVSYSCONTI.ANTIPCON = AVSYSDOC_MAST.MVTIPCON AND AVSYSCONTI.ANCODICE = AVSYSDOC_MAST.MVCODCON
                 WHERE " . implode(' AND ', $conds) . "
                 ORDER BY mvdatdoc DESC";

        return $this->fetchResults($sql, $params);
    }

    // Analisi S8 (deriva da ODE)
    public function analisiS8($dal = null, $al = null)
    {
        $orders = $this->ode('035498', 'E%8%', $dal, $al);
        $totale = [];
        foreach ($orders as $order) {
            $codice = trim($order['codice']);
            if (!isset($totale[$codice])) $totale[$codice] = 0;
            $totale[$codice] += $order['quantita'];
        }

        $tabellaS8 = [];
        foreach ($totale as $key => $value) {
            $model = substr($key, 4, 2) . " " . substr($key, 1, 3);
            if (!isset($tabellaS8[$model])) {
                $tabellaS8[$model] = [
                    'UNITA TOTALI' => 0,
                    'CAP_CAF' => 0,
                    'PEHD' => 0,
                    'EHD' => 0,
                    'PEHD_EHD' => 0,
                ];
            }
            $tabellaS8[$model]['UNITA TOTALI'] += $value;
            if (substr($key, 6, 1) === 'F' && substr($key, 8, 1) === 'X') $tabellaS8[$model]['CAP_CAF'] += $value;
            if (substr($key, 7, 1) === 'P' && substr($key, 8, 1) === 'X') $tabellaS8[$model]['PEHD'] += $value;
            if (substr($key, 8, 1) === 'E' && substr($key, 7, 1) === 'X') $tabellaS8[$model]['EHD'] += $value;
            if (substr($key, 8, 1) === 'E' && substr($key, 7, 1) === 'P') $tabellaS8[$model]['PEHD_EHD'] += $value;
        }
        return $tabellaS8;
    }

    public function totaliS8($tabs8)
    {
        $totaliS8 = [
            'TRIANGOLI' => 0,
            'QUADRATI' => 0,
            'CERCHI' => 0,
            'KIT_ELETTRONICA' => 0,
            'KIT_PEHD' => 0,
            'KIT_EHD' => 0,
            'KIT_CAPCAF' => 0,
        ];

        foreach ($tabs8 as $key => $values) {
            $totaliS8['KIT_ELETTRONICA'] += $values['UNITA TOTALI'];

            if (in_array($key, ['38 OSC', '48 OSC'])) {
                $totaliS8['TRIANGOLI'] += $values['UNITA TOTALI'];
                $totaliS8['KIT_EHD'] += $values['EHD'] + $values['PEHD_EHD'];
                $totaliS8['KIT_PEHD'] += $values['PEHD'] + $values['PEHD_EHD'];
            }

            if ($key === '68 OSC') {
                $totaliS8['QUADRATI'] += $values['UNITA TOTALI'];
                $totaliS8['KIT_EHD'] += $values['EHD'] + $values['PEHD_EHD'];
                $totaliS8['KIT_PEHD'] += $values['PEHD'] + $values['PEHD_EHD'];
                $totaliS8['KIT_CAPCAF'] = $values['CAP_CAF'];
            }

            if (in_array($key, ['38 SSC', '48 SSC'])) {
                $totaliS8['CERCHI'] += $values['UNITA TOTALI'];
                $totaliS8['KIT_EHD'] += $values['EHD'] + $values['PEHD_EHD'];
                $totaliS8['KIT_PEHD'] += $values['PEHD'] + $values['PEHD_EHD'];
            }

            if ($key === '68 SSC') {
                $totaliS8['CERCHI'] += $values['UNITA TOTALI'];
                $totaliS8['KIT_EHD'] += $values['EHD'] + $values['PEHD_EHD'];
                $totaliS8['KIT_PEHD'] += $values['PEHD'] + $values['PEHD_EHD'];
                $totaliS8['KIT_CAPCAF'] = $values['CAP_CAF'];
            }
        }

        return [$totaliS8];
    }

    public function ddt($docnum, $ese = 2023)
    {
        $sql = "SELECT mvnumdoc as [DDT Number],
                       CASE WHEN (LEN(X2.mvserrif) > 0) THEN
                         (SELECT MAX(mvnumdoc)
                            FROM [dbo].[AVSYSDOC_DETT] AS X1
                            INNER JOIN [dbo].[AVSYSDOC_MAST] ON X1.mvserial = [AVSYSDOC_MAST].mvserial
                            LEFT OUTER JOIN [dbo].[AVSYSCONTI] ON [AVSYSCONTI].ANTIPCON = [AVSYSDOC_MAST].MVTIPCON AND [AVSYSCONTI].ANCODICE = [AVSYSDOC_MAST].MVCODCON
                           WHERE X1.mvserial LIKE X2.mvserrif)
                       ELSE NULL END AS [Order Number],
                       X2.mvcodart as [Item Code],
                       (SELECT CACODICE FROM AVSYSKEY_ARTI WHERE CACODART = X2.mvcodart AND CACODCON = mvcodcon ) as [Customer Code],
                       X2.mvdesart as [Description],
                       X2.mvqtamov as [Quantity]
                  FROM [dbo].[AVSYSDOC_DETT] AS X2
                  INNER JOIN [dbo].[AVSYSDOC_MAST] ON X2.mvserial = [AVSYSDOC_MAST].mvserial
                  LEFT OUTER JOIN [dbo].[AVSYSCONTI] ON [AVSYSCONTI].ANTIPCON = [AVSYSDOC_MAST].MVTIPCON AND [AVSYSCONTI].ANCODICE = [AVSYSDOC_MAST].MVCODCON
                 WHERE mvtipdoc = 'DDTVE' AND mvcodese = ? AND mvnumdoc = ? AND LEN(mvcodart) > 10";

        $results = $this->fetchResults($sql, [$ese, $docnum]);
        $out = [];
        foreach ($results as $r) {
            $out[] = [
                'DDT Number'    => $r['DDT Number'],
                'Order Number'  => $r['Order Number'],
                'Item Code'     => $r['Item Code'],
                'Customer Code' => $r['Customer Code'] ?? $r['Item Code'],
                'Description'   => $r['Description'],
                'Quantity'      => is_numeric($r['Quantity']) ? number_format($r['Quantity'], 0) : $r['Quantity'],
            ];
        }
        return $out;
    }
}
//---------------- EOF -------------------//
