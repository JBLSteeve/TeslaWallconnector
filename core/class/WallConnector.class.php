<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class WallConnector extends eqLogic {
    /*     * *************************Attributs****************************** */

    /*     * ***********************Methode static*************************** */

   	//Fonction exécutée automatiquement toutes les minutes par Jeedom
    public static function cron() {
		foreach (self::byType('WallConnector') as $WallConnector) {//parcours tous les équipements du plugin WallConnector
			if ($WallConnector->getIsEnable() == 1) {//vérifie que l'équipement est actif
				$cmd = $WallConnector->getCmd(null, 'refresh');//retourne la commande "refresh si elle existe
				if (!is_object($cmd)) {//Si la commande n'existe pas
					continue; //continue la boucle
				}
				$cmd->execCmd(); // la commande existe on la lance
			}
		}
    }
  
  	public static function templateWidget(){
		$return = array('info' => array('string' => array()));
		return $return;
	}
	
	public function get_string_between($string, $start, $end){
		$string = ' ' . $string;
		$ini = strpos($string, $start);
		if ($ini == 0) return '';
		$ini += strlen($start);
		$len = strpos($string, $end, $ini) - $ini;
		return substr($string, $ini, $len);
	}
	
	public function GetData() {
		
		try {
          	$Mode = $this->getConfiguration("Mode");
                    
			$WallConnector_IP = $this->getConfiguration("IP");
			$ch = curl_init();
			           
              	//Getjson vital
              	curl_setopt_array($ch, [
  					CURLOPT_URL => 'http://'.$WallConnector_IP.'/api/1/vitals',
  					CURLOPT_RETURNTRANSFER => true,
  					CURLOPT_ENCODING => "",
  					CURLOPT_MAXREDIRS => 10,
  					CURLOPT_TIMEOUT => 10,
  					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  	CURLOPT_IGNORE_CONTENT_LENGTH => 136,
  					CURLOPT_CUSTOMREQUEST => 'GET',
  					CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                ]);
				$response = curl_exec($ch);
	           	$json_vital = json_decode($response, true);

              	//Getjson lifetime
              	curl_setopt_array($ch, [
  					CURLOPT_URL => 'http://'.$WallConnector_IP.'/api/1/lifetime',
  					CURLOPT_RETURNTRANSFER => true,
  					CURLOPT_ENCODING => "",
  					CURLOPT_MAXREDIRS => 10,
  					CURLOPT_TIMEOUT => 10,
  					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  	CURLOPT_IGNORE_CONTENT_LENGTH => 136,
  					CURLOPT_CUSTOMREQUEST => 'GET',
  					CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                ]);
				$response = curl_exec($ch);
	           	$json_lifetime = json_decode($response, true);

         
              	// Get WallConnector Temperature
				$handle_temp_c = $json_vital['handle_temp_c'];
				$this->checkAndUpdateCmd('handle_temp_c', $handle_temp_c);
              
              	// Get WallConnector current in A
				$currentA_a = $json_vital['currentA_a'];
          		$this->checkAndUpdateCmd('currentA_a', $currentA_a);
          
          		// Get WallConnector voltage in V
				$voltageA_v = $json_vital['voltageA_v'];
				$this->checkAndUpdateCmd('voltageA_v', $voltageA_v);
              
          		// Get WallConnector voltage input in V
				$grid_v = $json_vital['grid_v'];
				$this->checkAndUpdateCmd('grid_v', $grid_v);
				
				
				//Get WallConnector Plug State
				$vehicle_connected = $json_vital['vehicle_connected'];          
				if ($vehicle_connected == false) {
					$this->checkAndUpdateCmd('vehicle_connected', 'Déconnectée');
				} elseif ($vehicle_connected == true) {
					$this->checkAndUpdateCmd('vehicle_connected', 'Connectée');
				}
              
          		//Get WallConnector  State
				$contactor_closed = $json_vital['contactor_closed'];          
				if ($contactor_closed == false) {
					$this->checkAndUpdateCmd('contactor_closed', 'Déconnectée');
				} elseif ($contactor_closed == true) {
					$this->checkAndUpdateCmd('contactor_closed', 'Connectée');
				}
          
				// Get WallConnector Charge Session in Kwh
				$session_energy_wh = $json_vital['session_energy_wh'];
				$this->checkAndUpdateCmd('session_energy_wh', round($session_energy_wh/1000,2));
              
				// Get WallConnector total Charge in Kwh
				$energy_wh = $json_lifetime['energy_wh'];
				$this->checkAndUpdateCmd('energy_wh', round($energy_wh/1000,2));
				
				// Get WallConnector total time in Charge in s
				$charging_time_s = $json_lifetime['charging_time_s'];
				$this->checkAndUpdateCmd('charging_time_s', $charging_time_s);
				
				// Get WallConnector time in Charge in s
				$session_s = $json_vital['session_s'];
				$this->checkAndUpdateCmd('session_s', $session_s);
				
				// Get WallConnector car current in A
				$vehicle_current_a = $json_vital['vehicle_current_a'];
          			$this->checkAndUpdateCmd('vehicle_current_a', $vehicle_current_a);
			
				// Get WallConnector status
				$evse_state = $json_vital['evse_state'];
          			$this->checkAndUpdateCmd('evse_state', evse_state);
			
			log::add('WallConnector', 'debug','Fonction GetData : Récupération des données WallConnector OK !' );
			return;
		} catch (Exception $e) {
			log::add('WallConnector', 'error', __('Erreur lors de l\'éxecution de GetData ' . ' ' . $e->getMessage()));
		}
	}
  

    
    /*     * *********************Méthodes d'instance************************* */

    public function preInsert() {
    	$setMode = $this->setConfiguration("Mode",1); //Les nouveaux objets sont de type WIFI API par defaut.
    }

    public function postInsert() {

    }

    public function preSave() {

    }

    public function postSave() {
		$info = $this->getCmd(null, 'grid_v');
		if (!is_object($info)) {
			$info = new WallConnectorCmd();
			$info->setName(__('Tension entrée', __FILE__));
		}
		$info->setLogicalId('grid_v');
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('numeric');
		$info->setTemplate('dashboard','line');
      		$info->setTemplate('mobile','line');
		$info->setIsHistorized(1);
		$info->setUnite('V');
		$info->setOrder(1);
		$info->save();
		
		$info = $this->getCmd(null, 'voltageA_v');
		if (!is_object($info)) {
			$info = new WallConnectorCmd();
			$info->setName(__('Tension', __FILE__));
		}
		$info->setLogicalId('voltageA_v');
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('numeric');
		$info->setTemplate('dashboard','line');
      		$info->setTemplate('mobile','line');
		$info->setIsHistorized(1);
		$info->setUnite('V');
		$info->setOrder(2);
		$info->save();
		
		$info = $this->getCmd(null, 'currentA_a');
		if (!is_object($info)) {
			$info = new WallConnectorCmd();
			$info->setName(__('Intensité', __FILE__));
		}
		$info->setLogicalId('currentA_a');
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('numeric');
		$info->setTemplate('dashboard','line');
      		$info->setTemplate('mobile','line');
		$info->setConfiguration('minValue', 0);
		$info->setConfiguration('maxValue', 32);
		$info->setIsHistorized(1);
		$info->setUnite('A');
		$info->setOrder(3);
		$info->save();
		
				$info = $this->getCmd(null, 'vehicle_current_a');
		if (!is_object($info)) {
			$info = new WallConnectorCmd();
			$info->setName(__('Intensité cosommée par la voiture', __FILE__));
		}
		$info->setLogicalId('vehicle_current_a');
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('numeric');
		$info->setTemplate('dashboard','line');
      		$info->setTemplate('mobile','line');
		$info->setConfiguration('minValue', 0);
		$info->setConfiguration('maxValue', 32);
		$info->setIsHistorized(1);
		$info->setUnite('A');
		$info->setOrder(4);
		$info->save();
		
		$info = $this->getCmd(null, 'session_energy_wh');
		if (!is_object($info)) {
			$info = new WallConnectorCmd();
			$info->setName(__('Energie Consommée', __FILE__));
		}
		$info->setLogicalId('session_energy_wh');
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('numeric');
		$info->setTemplate('dashboard','line');
      		$info->setTemplate('mobile','line');
		$info->setIsHistorized(1);
		$info->setUnite('Kwh');
		$info->setOrder(5);
		$info->save();
		
		$info = $this->getCmd(null, 'session_s');
		if (!is_object($info)) {
			$info = new WallConnectorCmd();
			$info->setName(__('Temps de charge', __FILE__));
		}
		$info->setLogicalId('session_s');
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('numeric');
		$info->setTemplate('dashboard','line');
      		$info->setTemplate('mobile','line');
		$info->setIsHistorized(1);
		$info->setUnite('s');
		$info->setOrder(6);
		$info->save();
		
		$info = $this->getCmd(null, 'charging_time_s');
		if (!is_object($info)) {
			$info = new WallConnectorCmd();
			$info->setName(__('Temps de charge total', __FILE__));
		}
		$info->setLogicalId('charging_time_s');
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('numeric');
		$info->setTemplate('dashboard','line');
      		$info->setTemplate('mobile','line');
		$info->setIsHistorized(1);
		$info->setUnite('s');
		$info->setOrder(7);
		$info->save();
		
		$info = $this->getCmd(null, 'energy_wh');
		if (!is_object($info)) {
			$info = new WallConnectorCmd();
			$info->setName(__('Energie consommée totale', __FILE__));
		}
		$info->setLogicalId('energy_wh');
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('numeric');
		$info->setTemplate('dashboard','line');
      		$info->setTemplate('mobile','line');
		$info->setIsHistorized(1);
		$info->setUnite('Kwh');
		$info->setOrder(8);
		$info->save();
		
		$info = $this->getCmd(null, 'handle_temp_c');
		if (!is_object($info)) {
			$info = new WallConnectorCmd();
			$info->setName(__('Température poignée', __FILE__));
		}
		$info->setLogicalId('handle_temp_c');
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('numeric');
		$info->setTemplate('dashboard','line');
      		$info->setTemplate('mobile','line');
		$info->setConfiguration('minValue', 0);
		$info->setConfiguration('maxValue', 80);
		$info->setIsHistorized(1);
		$info->setUnite('°C');
		$info->setOrder(9);
		$info->save();
		
		$info = $this->getCmd(null, 'vehicle_connected');
		if (!is_object($info)) {
			$info = new WallConnectorCmd();
			$info->setName(__('Voiture branchée', __FILE__));
		}
		$info->setLogicalId('vehicle_connected');
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('string');
		$info->setTemplate('dashboard','default');
      		$info->setTemplate('mobile','default');
		$info->setIsHistorized(0);
		$info->setIsVisible(1);
		$info->setOrder(10);
		$info->save();
		
					
		$info = $this->getCmd(null, 'contactor_closed');
		if (!is_object($info)) {
			$info = new WallConnectorCmd();
			$info->setName(__('Voiture en charge', __FILE__));
		}
		$info->setLogicalId('contactor_closed');
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('string');
		$info->setTemplate('dashboard','default');
      		$info->setTemplate('mobile','default');
		$info->setIsHistorized(0);
		$info->setIsVisible(1);
		$info->setOrder(11);
		$info->save();

	    	$info = $this->getCmd(null, 'evse_state');
		if (!is_object($info)) {
			$info = new WallConnectorCmd();
			$info->setName(__('Etat du WallConnector', __FILE__));
		}
		$info->setLogicalId('evse_state');
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('numeric');
		$info->setTemplate('dashboard','line');
      		$info->setTemplate('mobile','line');
		$info->setIsHistorized(1);
		$info->setIsVisible(1);
		$info->setOrder(12);
		$info->save();
	    
		$refresh = $this->getCmd(null, 'refresh');
		if (!is_object($refresh)) {
			$refresh = new WallConnectorCmd();
			$refresh->setName(__('Rafraîchir', __FILE__));
		}
		$refresh->setEqLogic_id($this->getId());
		$refresh->setLogicalId('refresh');
		$refresh->setType('action');
		$refresh->setSubType('other');
		$refresh->setOrder(50);
		$refresh->save();
      
     	foreach (self::byType('WallConnector') as $WallConnector) {//parcours tous les équipements du plugin WallConnector
			if ($WallConnector->getIsEnable() == 1) {//vérifie que l'équipement est actif
				$cmd = $WallConnector->getCmd(null, 'refresh');//retourne la commande "refresh si elle existe
				if (!is_object($cmd)) {//Si la commande n'existe pas
					continue; //continue la boucle
				}
				$cmd->execCmd(); // la commande existe on la lance
			}
		}
      
    }

    public function preUpdate() {

    }

    public function postUpdate() {

    }

    public function preRemove() {
       
    }

    public function postRemove() {
        
    }
  
  	
	
    /*
     * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
      public function toHtml($_version = 'dashboard') {
      }
     */

    /*
     * Non obligatoire mais ca permet de déclencher une action après modification de variable de configuration
    public static function postConfig_<Variable>() {
    }
     */

    /*
     * Non obligatoire mais ca permet de déclencher une action avant modification de variable de configuration
    public static function preConfig_<Variable>() {
    }
     */

    /*     * **********************Getteur Setteur*************************** */
}

class WallConnectorCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

    public function execute($_options = array()) {
		$eqlogic = $this->getEqLogic();
		switch ($this->getLogicalId()) {		
			case 'refresh':
				$info = $eqlogic->GetData();
				break;					
		}
    }
    /*     * **********************Getteur Setteur*************************** */
}
