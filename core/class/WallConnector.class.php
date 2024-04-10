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
			           
              	//Get all other data
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
	           	$json = json_decode($response, true);

         
              	// Get WallConnector Temperature
				$temp = $json['handle_temp_c'];
				$this->checkAndUpdateCmd('EVSE_Temp', $temp);
              
              	// Get WallConnector Actual Amperes
				$amperes = $json['currentA_a'];
          		$this->checkAndUpdateCmd('EVSE_Amperes', $amperes);
          
          		// Get WallConnector Volts
				$volts = $json['grid_v'];
				$this->checkAndUpdateCmd('EVSE_Volts', $volts);
              

				//Get WallConnector Plug State
				$connectstate = $json['vehicle_connected'];          
				if ($connectstate == false) {
					$this->checkAndUpdateCmd('EVSE_Plug', 'Déconnectée');
				} elseif ($connectstate == true) {
					$this->checkAndUpdateCmd('EVSE_Plug', 'Connectée');
				}
              
          		//Get WallConnector  State
				$state = $json['contactor_closed'];          
				if ($state == false) {
					$this->checkAndUpdateCmd('EVSE_State', 'Déconnectée');
				} elseif ($state == true) {
					$this->checkAndUpdateCmd('EVSE_State', 'Connectée');
				}
          
				// Get WallConnector Charge Session in Kwh
				$chargesession = $json['session_energy_wh'];
				$this->checkAndUpdateCmd('EVSE_ChargeSession', round($chargesession/1000,2));
              

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
		$info = $this->getCmd(null, 'EVSE_Volts');
		if (!is_object($info)) {
			$info = new WallConnectorCmd();
			$info->setName(__('Tension : ', __FILE__));
		}
		$info->setLogicalId('EVSE_Volts');
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('numeric');
		$info->setTemplate('dashboard','line');
      	$info->setTemplate('mobile','line');
		$info->setIsHistorized(1);
		$info->setUnite('V');
		$info->setOrder(1);
		$info->save();
		
		$info = $this->getCmd(null, 'EVSE_Amperes');
		if (!is_object($info)) {
			$info = new WallConnectorCmd();
			$info->setName(__('Intensité : ', __FILE__));
		}
		$info->setLogicalId('EVSE_Amperes');
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('numeric');
		$info->setTemplate('dashboard','line');
      	$info->setTemplate('mobile','line');
		$info->setConfiguration('minValue', 0);
		$info->setConfiguration('maxValue', 32);
		$info->setIsHistorized(1);
		$info->setUnite('A');
		$info->setOrder(2);
		$info->save();
		
		$info = $this->getCmd(null, 'EVSE_ChargeSession');
		if (!is_object($info)) {
			$info = new WallConnectorCmd();
			$info->setName(__('Charge Session : ', __FILE__));
		}
		$info->setLogicalId('EVSE_ChargeSession');
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('numeric');
		$info->setTemplate('dashboard','line');
      	$info->setTemplate('mobile','line');
		$info->setIsHistorized(1);
		$info->setUnite('Kwh');
		$info->setOrder(3);
		$info->save();
		
		$info = $this->getCmd(null, 'EVSE_Temp');
		if (!is_object($info)) {
			$info = new WallConnectorCmd();
			$info->setName(__('Température : ', __FILE__));
		}
		$info->setLogicalId('EVSE_Temp');
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('numeric');
		$info->setTemplate('dashboard','line');
      	$info->setTemplate('mobile','line');
		$info->setConfiguration('minValue', 0);
		$info->setConfiguration('maxValue', 80);
		$info->setIsHistorized(1);
		$info->setUnite('°C');
		$info->setOrder(4);
		$info->save();
		
		$info = $this->getCmd(null, 'EVSE_Plug');
		if (!is_object($info)) {
			$info = new WallConnectorCmd();
			$info->setName(__('Prise : ', __FILE__));
		}
		$info->setLogicalId('EVSE_Plug');
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('string');
		$info->setTemplate('dashboard','default');
      	$info->setTemplate('mobile','default');
		$info->setIsHistorized(0);
		$info->setIsVisible(1);
		$info->setOrder(5);
		$info->save();
		
					
		$info = $this->getCmd(null, 'EVSE_State');
		if (!is_object($info)) {
			$info = new WallConnectorCmd();
			$info->setName(__('Etat : ', __FILE__));
		}
		$info->setLogicalId('EVSE_State');
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('string');
		$info->setTemplate('dashboard','default');
      	$info->setTemplate('mobile','default');
		$info->setIsHistorized(0);
		$info->setIsVisible(1);
		$info->setOrder(8);
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