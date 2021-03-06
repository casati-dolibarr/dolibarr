<?php
/* Copyright (C) 2012      Christophe Battarel  <christophe.battarel@altairis.fr>
 * Copyright (C) 2015      Francis Appels      <francis.appels@z-application.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       /htdocs/fourn/ajax/getSupplierPrices.php
 *	\brief      File to return an Ajax response to get a supplier prices
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';

$idprod=GETPOST('idprod','int');

$prices = array();

$langs->load('stocks');

/*
 * View
*/

top_httphead();

//print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";

if ($idprod > 0)
{
	$producttmp=new ProductFournisseur($db);
	$producttmp->fetch($idprod);
	$productSupplierArray = $producttmp->list_product_fournisseur_price($idprod, 's.nom, pfp.quantity, pfp.price');    // We list all price per supplier, and then firstly with the lower quantity. So we can choose first one with enough quantity into list.
	if ( is_array($productSupplierArray))
	{
		foreach ($productSupplierArray as $productSupplier)
		{
			$price = $productSupplier->fourn_price * (1 - $productSupplier->fourn_remise_percent / 100);
			$unitprice = $productSupplier->fourn_unitprice * (1 - $productSupplier->fourn_remise_percent / 100);
			
			$title = $productSupplier->fourn_name.' - '.$productSupplier->fourn_ref.' - ';
			
			if ($productSupplier->fourn_qty == 1)
			{
				$title.= price($price,0,$langs,0,0,-1,$conf->currency)."/";
			}
			$title.= $productSupplier->fourn_qty.' '.($productSupplier->fourn_qty == 1 ? $langs->trans("Unit") : $langs->trans("Units"));
			
			if ($productSupplier->fourn_qty > 1)
			{
				$title.=" - ";
				$title.= price($unitprice,0,$langs,0,0,-1,$conf->currency)."/".$langs->trans("Unit");
				$price = $unitprice;
			}
			if ($productSupplier->fourn_unitcharges > 0 && ($conf->global->MARGIN_TYPE == "2"))
			{
				$title.=" + ";
				$title.= price($productSupplier->fourn_unitcharges,0,$langs,0,0,-1,$conf->currency);
				$price += $productSupplier->fourn_unitcharges;
			}
			
			$label = price($price,0,$langs,0,0,-1,$conf->currency)."/".$langs->trans("Unit");
			if ($productSupplier->fourn_ref) $label.=' ('.$productSupplier->fourn_ref.')';
			
			$prices[] = array("id" => $productSupplier->product_fourn_price_id, "price" => price($price,0,'',0), "label" => $label, "title" => $title);
		}
	}
	
	// Add price for pmp
	$price=$producttmp->pmp;
	$prices[] = array("id" => 'pmpprice', "price" => $price, "label" => $langs->trans("PMPValueShort").': '.price($price,0,$langs,0,0,-1,$conf->currency), "title" => $langs->trans("PMPValueShort").': '.price($price,0,$langs,0,0,-1,$conf->currency));
}

echo json_encode($prices);

