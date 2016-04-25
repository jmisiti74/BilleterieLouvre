<?php
  function construit_url_paypal()
  {
	$api_paypal = 'https://api-3t.sandbox.paypal.com/nvp?'; // Site de l'API PayPal. On ajoute déjà le ? afin de concaténer directement les paramètres.
	$version = 56.0; // Version de l'API
	
	$user = 'vendeur_1236594550_biz_api1.siteduzero.com'; // Utilisateur API
	$pass = 'SEFYITJFG8QRHN1S'; // Mot de passe API
	$signature = 'ZWg4tSHZZ0GQIK8U6VKGWO1mxrtOAJzAGFNRnFpDWRKX-fv8q5Tuj64n'; // Signature de l'API

	$api_paypal = $api_paypal.'VERSION='.$version.'&USER='.$user.'&PWD='.$pass.'&SIGNATURE='.$signature; // Ajoute tous les paramètres

	return 	$api_paypal; // Renvoie la chaîne contenant tous nos paramètres.
  }
?>