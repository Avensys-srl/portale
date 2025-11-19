<?php
include_once 'functions.php';

// CLDC data access layer: richiede una connessione sqlsrv già aperta (iniettata)
class CLDC
{
	private $conn;

	public function __construct($conn)
	{
		if (!$conn) {
			throw new InvalidArgumentException("CLDC richiede una connessione SQLSRV valida");
		}
		$this->conn = $conn;
	}

	private function fetchAll($sql, array $params = [])
	{
		$stmt = sqlsrv_query($this->conn, $sql, $params, ["Scrollable" => "forward"]);
		if (!$stmt) {
			throw new RuntimeException(print_r(sqlsrv_errors(), true));
		}
		$out = [];
		while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
			$out[] = $row;
		}
		sqlsrv_free_stmt($stmt);
		return $out;
	}

	private function exec($sql, array $params = [])
	{
		$stmt = sqlsrv_query($this->conn, $sql, $params);
		if (!$stmt) {
			throw new RuntimeException(print_r(sqlsrv_errors(), true));
		}
		sqlsrv_free_stmt($stmt);
		return true;
	}

	// Estrazione serie di recuperatori
	public function serie($is_SSW = null)
	{
		$sql = "SELECT [Id],[Code],[Name],[Description] FROM [dbo].[CLSeries]";
		$params = [];
		if ($is_SSW !== null) {
			$sql .= " WHERE [Code] <= '9' AND [Code] >= '0' AND [Code] NOT LIKE '31'";
		}

		$rows = $this->fetchAll($sql, $params);
		$out = [];
		foreach ($rows as $r) {
			$out[] = [
				'id' => $r['Id'],
				'code' => $r['Code'],
				'name' => $r['Name'],
				'feature' => $r['Description'],
			];
		}
		return $out;
	}

	// Estrazione spigots layout
	public function layout()
	{
		$rows = $this->fetchAll("SELECT [Id],[TextCode] FROM [dbo].[CLEnumItems] WHERE IdEnum = 4");
		$out = [];
		foreach ($rows as $r) {
			$out[] = [
				'id' => $r['Id'],
				'TextCode' => $r['TextCode'],
			];
		}
		return $out;
	}

	// Estrazione modelli prodotti - filtro su serie e layout disponibile
	public function modelli($idserie = null, $idlayout = null, $moredata = false)
	{
		$where = ["m.ModRec IS NOT NULL"];
		$params = [];
		if ($idlayout !== null) {
			$where[] = "m.IdAeraulicConnection = ?";
			$params[] = $idlayout;
		}
		if ($idserie !== null) {
			$where[] = "m.IdSerie = ?";
			$params[] = $idserie;
		}

		$sql = "SELECT m.[Id], m.[Code], m.[IdSerie], m.[Name], ei.[TextCode], m.[NominalAirflow], m.[StaticPressure]
				  FROM [dbo].[CLHeatRecoveryModels] m
				  INNER JOIN CLEnumItems ei ON ei.Id = m.IdAeraulicConnection
				 WHERE " . implode(' AND ', $where) . "
				 ORDER BY ei.TextCode DESC, m.Name ASC";

		$rows = $this->fetchAll($sql, $params);
		$out = [];
		foreach ($rows as $r) {
			$item = [
				'id' => $r['Id'],
				'code' => $r['Code'],
				'name' => $r['Name'],
				'layout' => $r['TextCode'],
			];
			if ($moredata) {
				$item['airflow'] = $r['NominalAirflow'];
				$item['pressure'] = $r['StaticPressure'];
			}
			$out[] = $item;
		}
		return $out;
	}

	// Dati completi di un modello
	public function data_model($id)
	{
		$sql = "SELECT m.*
				  FROM [dbo].[CLHeatRecoveryModels] m
				  INNER JOIN CLEnumItems ei ON ei.Id = m.IdAeraulicConnection
				 WHERE m.ModRec IS NOT NULL AND m.Id = ?";
		return $this->fetchAll($sql, [$id]);
	}

	// Duplica un modello esistente con nuovo codice/serie/layout (se passati)
	public function add_newmodel($newmodel, $oldmodel, $idSerie = null, $idLayout = null)
	{
		$sql = "
			INSERT INTO [CLHeatRecoveryModels] (
				[Code],[IdSerie],[Name],[IdAeraulicConnection],
				[ModRec],[LenRec],[FilterArea],[MotorType],[Airflows],[Pressures],[Powers],
				[SoundData_Inlet_63hz],[SoundData_Inlet_125hz],[SoundData_Inlet_250hz],[SoundData_Inlet_500hz],
				[SoundData_Inlet_1000hz],[SoundData_Inlet_2000hz],[SoundData_Inlet_4000hz],[SoundData_Inlet_8000hz],[SoundData_Inlet_Total],
				[SoundData_Outlet_63hz],[SoundData_Outlet_125hz],[SoundData_Outlet_250hz],[SoundData_Outlet_500hz],
				[SoundData_Outlet_1000hz],[SoundData_Outlet_2000hz],[SoundData_Outlet_4000hz],[SoundData_Outlet_8000hz],[SoundData_Outlet_Total],
				[PDFCommercialSheets],
				[CWD_IdFinsStep],[CWD_Length],[CWD_Height],[CWD_NumerOfRows],[CWD_NumerOfCircuits],[CWD_IdHeaderType],
				[HWD_IdFinsStep],[HWD_Length],[HWD_Height],[HWD_NumerOfRows],[HWD_NumerOfCircuits],[HWD_IdHeaderType],
				[Size],[MotorsNumbers],[PulseForRoundNumbers],[Weakening],[NumbersNTC],
				[TFreshPosition],[TReturnPosition],[TSupplyPosition],[TExaustPosition],
				[VirtualCAF],[VirtualCAP],[StaticPressure],[NominalAirflow],[Power],[PowerWithIPEHD],[IdCommercialLine],
				[HorVariants],[VerVariants],[IND_VarVer_Caption],[IND_VarVer_EW_Img],[IND_VarVer_NS_Img],
				[IND_VarHor_Caption],[IND_VarHor_Floor_Img],[IND_VarHor_Ceiling_Img],
				[Voltage],[Phase],[Frequency],
				[HeatRecovered_Winter],[HeatRecovered_Summer],
				[InternationalProtection],
				[Dimension_A_Ver],[Dimension_A_Hor],[Dimension_B_Ver],[Dimension_B_Hor],
				[Dimension_C_Ver],[Dimension_C_Hor],[Dimension_D_Ver],[Dimension_D_Hor],
				[Weight],
				[IND_Logo_Img],[IND_Photo_Hor_Img],[IND_Photo_Ver1_Img],[IND_Photo_Ver1_Caption],
				[IND_Photo_Ver2_Img],[IND_Photo_Ver2_Caption],[IND_Performance_Img],
				[NomimalCurrent],
				[LoudPressure_Inlet],[LoudPressure_Outlet],
				[Exaust_X],[Exaust_Y],[Exaust_K],[Exaust_Z],
				[Fresh_X],[Fresh_Y],[Fresh_K],[Fresh_Z],
				[Return_X],[Return_Y],[Return_K],[Return_Z],
				[Supply_X],[Supply_Y],[Supply_K],[Supply_Z],
				[Efficiency],
				[SYSIdRevision],[IdProcessingTaskType],
				[PDFInstallationOperationManuals]
			)
			SELECT 
				?,                                       -- Code nuovo
				COALESCE(?, [IdSerie]),                  -- Serie (override opzionale)
				?,                                       -- Name nuovo (uguale al codice)
				COALESCE(?, [IdAeraulicConnection]),     -- Layout (override opzionale)
				[ModRec],[LenRec],[FilterArea],[MotorType],[Airflows],[Pressures],[Powers],
				[SoundData_Inlet_63hz],[SoundData_Inlet_125hz],[SoundData_Inlet_250hz],[SoundData_Inlet_500hz],
				[SoundData_Inlet_1000hz],[SoundData_Inlet_2000hz],[SoundData_Inlet_4000hz],[SoundData_Inlet_8000hz],[SoundData_Inlet_Total],
				[SoundData_Outlet_63hz],[SoundData_Outlet_125hz],[SoundData_Outlet_250hz],[SoundData_Outlet_500hz],
				[SoundData_Outlet_1000hz],[SoundData_Outlet_2000hz],[SoundData_Outlet_4000hz],[SoundData_Outlet_8000hz],[SoundData_Outlet_Total],
				[PDFCommercialSheets],
				[CWD_IdFinsStep],[CWD_Length],[CWD_Height],[CWD_NumerOfRows],[CWD_NumerOfCircuits],[CWD_IdHeaderType],
				[HWD_IdFinsStep],[HWD_Length],[HWD_Height],[HWD_NumerOfRows],[HWD_NumerOfCircuits],[HWD_IdHeaderType],
				[Size],[MotorsNumbers],[PulseForRoundNumbers],[Weakening],[NumbersNTC],
				[TFreshPosition],[TReturnPosition],[TSupplyPosition],[TExaustPosition],
				[VirtualCAF],[VirtualCAP],[StaticPressure],[NominalAirflow],[Power],[PowerWithIPEHD],[IdCommercialLine],
				[HorVariants],[VerVariants],[IND_VarVer_Caption],[IND_VarVer_EW_Img],[IND_VarVer_NS_Img],
				[IND_VarHor_Caption],[IND_VarHor_Floor_Img],[IND_VarHor_Ceiling_Img],
				[Voltage],[Phase],[Frequency],
				[HeatRecovered_Winter],[HeatRecovered_Summer],
				[InternationalProtection],
				[Dimension_A_Ver],[Dimension_A_Hor],[Dimension_B_Ver],[Dimension_B_Hor],
				[Dimension_C_Ver],[Dimension_C_Hor],[Dimension_D_Ver],[Dimension_D_Hor],
				[Weight],
				[IND_Logo_Img],[IND_Photo_Hor_Img],[IND_Photo_Ver1_Img],[IND_Photo_Ver1_Caption],
				[IND_Photo_Ver2_Img],[IND_Photo_Ver2_Caption],[IND_Performance_Img],
				[NomimalCurrent],
				[LoudPressure_Inlet],[LoudPressure_Outlet],
				[Exaust_X],[Exaust_Y],[Exaust_K],[Exaust_Z],
				[Fresh_X],[Fresh_Y],[Fresh_K],[Fresh_Z],
				[Return_X],[Return_Y],[Return_K],[Return_Z],
				[Supply_X],[Supply_Y],[Supply_K],[Supply_Z],
				[Efficiency],
				[SYSIdRevision],[IdProcessingTaskType],
				[PDFInstallationOperationManuals]
			  FROM [dbo].[CLHeatRecoveryModels]
			 WHERE [Code] = ?";

		$params = [
			$newmodel,
			$idSerie,
			$newmodel,
			$idLayout,
			$oldmodel
		];

		return $this->exec($sql, $params);
	}
}
//---------------- EOF -------------------//
