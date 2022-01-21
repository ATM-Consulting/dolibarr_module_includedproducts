<?php
/* Copyright (C) 2022 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/triggers/interface_99_modIncludedproducts_IncludedproductsTriggers.class.php
 * \ingroup includedproducts
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modIncludedproducts_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 * - The constructor method must be named InterfaceMytrigger
 * - The name property name must be MyTrigger
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for Includedproducts module
 */
class InterfaceIncludedproductsTriggers extends DolibarrTriggers
{
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "demo";
		$this->description = "Includedproducts triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';
		$this->picto = 'includedproducts@includedproducts';
	}

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc()
	{
		return $this->description;
	}


	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (empty($conf->includedproducts) || empty($conf->includedproducts->enabled)) {
			return 0; // If module is not enabled, we do nothing
		}

		// En cas de mise à jour de ligne ou de création de facture depuis commande ou propal, ou de création de commande depuis propal, une ligne
		// avec l'extrafield options_includedproducts_isincludedproduct à 1 doit conserver cette information
		if(in_array($object->element, array('propaldet', 'commandedet', 'facturedet'))
			&& empty($object->array_options['options_includedproducts_isincludedproduct'])
			&& isset($object->oldline)) {
			$object->array_options['options_includedproducts_isincludedproduct'] = $object->oldline->array_options['options_includedproducts_isincludedproduct'];
		}

		if (!empty($object->array_options['options_includedproducts_isincludedproduct']) && in_array($action, array('LINEPROPAL_INSERT', 'LINEPROPAL_UPDATE', 'LINEORDER_INSERT', 'LINEORDER_UPDATE', 'LINEBILL_INSERT', 'LINEBILL_UPDATE', 'LINEBILL_SUPPLIER_CREATE', 'LINEBILL_SUPPLIER_UPDATE'))) {
			$doli_action = GETPOST('action', 'none');

			if ( (in_array($doli_action, array('updateligne', 'updateline', 'addline', 'add', 'create', 'confirm_clone'))) && in_array($object->element, array('propaldet', 'commandedet', 'facturedet'))) {
				$object->total_ht = $object->total_tva = $object->total_ttc = $object->total_localtax1 = $object->total_localtax2 =
				$object->multicurrency_total_ht = $object->multicurrency_total_tva = $object->multicurrency_total_ttc = 0;
				$object->pa_ht = '0';

				if ($object->element == 'propaldet') $res = $object->update(1);
				else $res = $object->update($user, 1);
			}
		}

		dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

		return 0;
	}
}
