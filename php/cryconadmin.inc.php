<?php
include_once 'crycon.inc.php';
class CryconAdmin extends Crycon				// Classe: connessione criptata da amministratore
	{
	var $utenti = array();
	
	public function __construct($dbname = "")
		{
		$msgecho = "";
		$msgecho .= "<b>Derived constructor</b><br>";
		$msgecho .= "<p>Derived arg dbname: ".$dbname."</p>";
		parent::constructName($dbname);
		
		$msgecho .= "<p>Derived dbname: ".$this->dbnm."</p>";
		
		array_push($this->utenti, array("pippo", sha1("antani"),"12345678901234567890123456789012","apQ127"));
		array_push($this->utenti, array("pluto", sha1("blinda"),"21098765432109876543210987654321","98zpR15"));
		array_push($this->utenti, array("a", sha1("a"),"21098765432109876543210987654321","98zpR15"));
		
		$msgecho .= "<b>Utenti</b><p>";
		foreach ($this->utenti as $x)
			{
			$msgecho .= $x[0]."\t:\t".$x[1]."\t:\t".$x[2]."\t:\t".$x[3]."<br>";
			}
		$msgecho .= "</p>";
		if($this->Connect())								// Apre la connessione
			{			
			try
				{
				$this->db->exec($this->cmd["udroptb"]);		// Elimina la tabella users
				$this->db->exec($this->cmd["ldroptb"]);		// Elimina la tabella logged
				$this->db->exec($this->cmd["utable"]);		// Crea la tabella users
				$this->db->exec($this->cmd["ltable"]);		// Crea la tabella logged
				$msgecho .= "<p>Database</p>";				// Intestazione

				foreach($this->utenti as $x)				// Inserisce gli utenti
					{
					$stmt = $this->db->prepare($this->cmd["insert"]);
					$stmt->bindParam(':'.$this->usrname,$x[0]);
					$stmt->bindParam(':passwd',$x[1]);
					$stmt->bindParam(':keystr',$x[2]);
					$stmt->bindParam(':pwddb',$x[3]);
					$stmt -> execute();
					$msgecho .= "Inserito: ".$x[0]."\t:\t".$x[1]."\t:\t".$x[2]."\t:\t".$x[3]."<br>";
					}
				$stmt = $this->db->query($this->cmd["count"]);	// Esegue la query del conteggio
				$cnt = $stmt->fetchColumn();					// Estrae i risultati della query
				$msgecho .= "<p>Righe <i>users</i>: ".$cnt."</p>";
				$stmt = $this->db->query($this->cmd["users"]);	// Esegue la query del contenuto
				$msgecho .= "<b>Correggere fetch():</b><br>";
				foreach($stmt as $row)
					{
					$msgecho .= implode(":",$row)."<br>";
					$msgecho .= count($row)."<br>";
					}
				
				$stmt = $this->db->query($this->cmd["lcount"]);	// Esegue la query del conteggio
				$cnt = $stmt->fetchColumn();					// Estrae i risultati della query
				$msgecho .= "<p>Righe <i>logged</i>: ".$cnt."</p>";
				
				$stmt = $this->db->query($this->cmd["logged"]);	// Esegue la query del contenuto
				foreach($stmt as $row)
					{
					$msgecho .= implode(":",$row)."<br>";
					}

				if($this->debugMode === true)
					echo $msgecho; 

				$this->Disconnect();
				$ok = true;
				}
			catch(PDOException $e)
				{
				$this->errore .= "<p><b>"."Error: ".$e->getMessage()."</b></p>";
				}
			
			}
		}
	
	}



?>